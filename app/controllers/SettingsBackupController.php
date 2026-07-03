<?php
/**
 * MÓDULO: CONFIGURACIÓN / RESPALDO DEL SISTEMA
 * ARCHIVO: app/controllers/SettingsBackupController.php
 * PROPÓSITO: Genera y transmite el respaldo troceado dinámicamente:
 *            Sistema, Público, Uploads, Raíz (1 ZIP cada uno), y
 *            Enrollments + SQL divididos en N partes calculadas en tiempo
 *            real según el tamaño real de los datos, para que cada
 *            descarga complete cómodamente dentro de los 300s de cPanel.
 *            Blindaje de búfer (ob_end_clean) en cada respuesta para
 *            evitar el error "Unexpected token <" al streamear binarios.
 * VERSIÓN: 11.0.0 - Reemplaza el modelo de 4 ZIPs fijos por troceo
 *          dinámico. Nuevo endpoint get-plan (JSON) para que el frontend
 *          calcule los pasos del popup antes de empezar. Separa Sistema
 *          (app+tools) de Público (public sin uploads) y agrega Raíz.
 *
 * RUTAS Bootstrap.php:
 *   GET /settings/database                        → index
 *   GET /settings/database/get-plan                → getPlan
 *   GET /settings/database/download-sql             → downloadSql        (?parte=N)
 *   GET /settings/database/download-sistema          → downloadSistema
 *   GET /settings/database/download-publico          → downloadPublico
 *   GET /settings/database/download-uploads          → downloadUploads
 *   GET /settings/database/download-enrollments       → downloadEnrollments (?parte=N)
 *   GET /settings/database/download-raiz             → downloadRaiz
 *   GET /settings/database/download-instrucciones     → downloadInstrucciones
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SettingsBackupModel;
use Throwable;
use ZipArchive;

class SettingsBackupController extends Controller
{
    private SettingsBackupModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new SettingsBackupModel();
    }

    public function index(): void
    {
        $particionesEnrollments = SettingsBackupModel::calcularParticionesEnrollments();

        $this->view('settings/database/index', [
            'sizeSistema'            => SettingsBackupModel::getTamanoMultiple([
                SettingsBackupModel::getRoot() . '/app',
                SettingsBackupModel::getRoot() . '/tools',
            ]),
            'sizePublico'            => SettingsBackupModel::getTamanoPublicoSinUploads(),
            'sizeUploads'            => SettingsBackupModel::getTamanoMultiple(SettingsBackupModel::getCarpetasUploadsGenerales()),
            'sizeEnrollments'        => SettingsBackupModel::getTamanoCarpeta(SettingsBackupModel::getEnrollmentsPath()),
            'totalPartesEnrollments' => count($particionesEnrollments),
            'dbName'                 => $this->model->getDbName(),
        ]);
    }

    // =========================================================================
    // PLAN: calcula cuántas partes tendrá cada componente (para el popup)
    // =========================================================================
    public function getPlan(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $particionesEnrollments = SettingsBackupModel::calcularParticionesEnrollments();

            echo json_encode([
                'ok'             => true,
                'sistema'        => 1,
                'publico'        => 1,
                'uploads'        => 1,
                'raiz'           => 1,
                'enrollments'    => max(1, count($particionesEnrollments)),
                'sql'            => $this->model->calcularParticionesSqlEstimado(),
                'instrucciones'  => 1,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================
    // DESCARGA: BASE DE DATOS SQL (troceada)
    // =========================================================================
    public function downloadSql(): void
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '1G');

            $parte    = max(1, (int) ($_GET['parte'] ?? 1));
            $batchDir = $this->getSqlBatchDir();
            $marker   = $batchDir . '/completo.marker';

            // El dump completo (todas las partes) solo se genera UNA vez,
            // en la primera solicitud. Las siguientes partes reutilizan
            // los archivos ya generados para no repetir la consulta a la BD.
            if ($parte === 1 || !file_exists($marker)) {
                if (is_dir($batchDir)) $this->limpiarDirectorio($batchDir);
                mkdir($batchDir, 0755, true);
                $this->model->generarDumpParticionado($batchDir);
                touch($marker);
            }

            $archivos = glob($batchDir . '/parte_*.sql');
            natsort($archivos);
            $archivos = array_values($archivos);
            $totalPartes = count($archivos);

            if ($totalPartes === 0 || $parte > $totalPartes) {
                throw new \RuntimeException("Parte SQL {$parte} no existe (total real: {$totalPartes}).");
            }

            $fecha   = date('Y-m-d_H-i-s');
            $zipName = "diplomatic_sql_parte{$parte}_{$fecha}.zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($archivos[$parte - 1], "diplomatic/sql/parte_{$parte}.sql");
            $zip->close();

            // Al terminar la última parte, limpiar los .sql temporales
            if ($parte === $totalPartes) {
                $this->limpiarDirectorio($batchDir);
            }

            $this->streamZip($zipPath, $zipName, ['X-Total-Partes' => (string) $totalPartes]);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: SISTEMA (app/ + tools/)
    // =========================================================================
    public function downloadSistema(): void
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $fecha   = date('Y-m-d_H-i-s');
            $zipName = "diplomatic_sistema_{$fecha}.zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;
            $root    = SettingsBackupModel::getRoot();

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $this->agregarCarpetaAZip($zip, $root . '/app', dirname($root . '/app'), 'diplomatic/app');
            $this->agregarCarpetaAZip($zip, $root . '/tools', dirname($root . '/tools'), 'diplomatic/tools');
            $zip->close();

            $this->streamZip($zipPath, $zipName);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: PÚBLICO (public/ SIN uploads)
    // =========================================================================
    public function downloadPublico(): void
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $fecha   = date('Y-m-d_H-i-s');
            $zipName = "diplomatic_publico_{$fecha}.zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;
            $root    = SettingsBackupModel::getRoot();

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $this->agregarCarpetaFiltrada($zip, $root . '/public', $root, ['public/uploads'], 'diplomatic');
            $zip->close();

            $this->streamZip($zipPath, $zipName);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: UPLOADS GENERALES (SIN enrollments)
    // =========================================================================
    public function downloadUploads(): void
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $fecha    = date('Y-m-d_H-i-s');
            $zipName  = "diplomatic_uploads_{$fecha}.zip";
            $zipPath  = sys_get_temp_dir() . '/' . $zipName;
            $carpetas = SettingsBackupModel::getCarpetasUploadsGenerales();

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach ($carpetas as $nombre => $ruta) {
                if (is_dir($ruta)) {
                    $this->agregarCarpetaAZip($zip, $ruta, dirname($ruta), 'diplomatic/uploads/' . $nombre);
                }
            }

            $zip->close();
            $this->streamZip($zipPath, $zipName);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: ENROLLMENTS (troceado dinámicamente, por carpeta completa)
    // =========================================================================
    public function downloadEnrollments(): void
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $parte        = max(1, (int) ($_GET['parte'] ?? 1));
            $particiones  = SettingsBackupModel::calcularParticionesEnrollments();
            $totalPartes  = count($particiones);

            if ($totalPartes === 0 || $parte > $totalPartes) {
                throw new \RuntimeException("Parte de enrollments {$parte} no existe (total: {$totalPartes}).");
            }

            $fecha   = date('Y-m-d_H-i-s');
            $zipName = "diplomatic_enrollments_parte{$parte}_{$fecha}.zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach ($particiones[$parte - 1] as $folder) {
                $this->agregarCarpetaAZip(
                    $zip,
                    $folder['ruta'],
                    dirname($folder['ruta']),
                    'diplomatic/uploads/enrollments/' . $folder['nombre']
                );
            }

            $zip->close();
            $this->streamZip($zipPath, $zipName, ['X-Total-Partes' => (string) $totalPartes]);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: RAÍZ (README.md, estructura.txt, modulos parte 2.txt)
    // =========================================================================
    public function downloadRaiz(): void
    {
        try {
            $fecha   = date('Y-m-d_H-i-s');
            $zipName = "diplomatic_raiz_{$fecha}.zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach (SettingsBackupModel::getArchivosRaiz() as $nombre => $ruta) {
                if (is_file($ruta)) {
                    $zip->addFile($ruta, 'diplomatic/' . $nombre);
                }
            }

            $zip->close();
            $this->streamZip($zipPath, $zipName);

        } catch (Throwable $e) {
            $this->errorRedirect($e->getMessage());
        }
    }

    // =========================================================================
    // DESCARGA: INSTRUCCIONES TXT
    // =========================================================================
    public function downloadInstrucciones(): void
    {
        $fecha      = date('Y-m-d H:i:s');
        $dbname     = $this->model->getDbName();
        $sqlPartes  = max(1, (int) ($_GET['sql_partes'] ?? 1));
        $enrollPartes = max(1, (int) ($_GET['enrollments_partes'] ?? 1));

        $listaSql = '';
        for ($i = 1; $i <= $sqlPartes; $i++) {
            $listaSql .= "  diplomatic_sql_parte{$i}_FECHA.zip\n";
        }
        $listaEnroll = '';
        for ($i = 1; $i <= $enrollPartes; $i++) {
            $listaEnroll .= "  diplomatic_enrollments_parte{$i}_FECHA.zip\n";
        }

        $txt = <<<TXT
DIPLOMATIC - INSTRUCCIONES DE RESPALDO Y RESTAURACIÓN
======================================================
Generado: {$fecha}
Base de datos: {$dbname}

ARCHIVOS DE ESTE RESPALDO
--------------------------
  diplomatic_sistema_FECHA.zip        -> app/ + tools/
  diplomatic_publico_FECHA.zip        -> public/ (sin uploads)
  diplomatic_uploads_FECHA.zip        -> constancias, contratos, correspondencia, personal, tesoreria
  diplomatic_raiz_FECHA.zip           -> README.md, estructura.txt, modulos parte 2.txt
{$listaEnroll}{$listaSql}  INSTRUCCIONES_RESTAURACION.txt      -> Este archivo

NOTA: Este respaldo NO incluye /storage ni /db (contenian backups
manuales antiguos, no datos operativos) ni /.git (el codigo versionado
vive en GitHub).

PASO A PASO PARA RESTAURAR
---------------------------

PASO 1 - CREAR LA CARPETA EN EL SERVIDOR
  Ingresa a cPanel -> Administrador de Archivos
  Navega hasta /public_html/ y crea una carpeta llamada "diplomatic"

PASO 2 - RESTAURAR EL SISTEMA
  Sube y extrae diplomatic_sistema_FECHA.zip dentro de /public_html/diplomatic/
  Debe quedar: /public_html/diplomatic/app/ y /public_html/diplomatic/tools/

PASO 3 - RESTAURAR EL PUBLICO
  Sube y extrae diplomatic_publico_FECHA.zip dentro de /public_html/diplomatic/
  Debe quedar: /public_html/diplomatic/public/ (sin la carpeta uploads todavia)

PASO 4 - RESTAURAR UPLOADS GENERALES
  Sube y extrae diplomatic_uploads_FECHA.zip dentro de /public_html/diplomatic/
  Debe quedar: /public_html/diplomatic/public/uploads/
    con las subcarpetas: constancias, contratos, correspondencia, personal, tesoreria

PASO 5 - RESTAURAR ENROLLMENTS (documentos de inscripcion, por partes)
  Sube y extrae TODAS las partes ({$enrollPartes} en total) dentro de:
    /public_html/diplomatic/public/uploads/enrollments/
  Todas las partes se combinan en la misma carpeta sin sobrescribirse.
  Extrae las {$enrollPartes} partes antes de continuar al siguiente paso.

PASO 6 - RESTAURAR LA CARPETA RAIZ
  Sube y extrae diplomatic_raiz_FECHA.zip dentro de /public_html/diplomatic/

PASO 7 - RESTAURAR LA BASE DE DATOS
  El dump SQL viene dividido en {$sqlPartes} parte(s), en orden.
  Ve a phpMyAdmin -> crea/selecciona la base de datos -> pestana "Importar"
  Extrae e importa parte_1.sql, luego parte_2.sql, luego parte_3.sql...
  RESPETA EL ORDEN. Si el archivo es grande, usa la terminal:
    mysql -u USUARIO -p NOMBRE_BD < parte_1.sql
    mysql -u USUARIO -p NOMBRE_BD < parte_2.sql
    (y asi sucesivamente)

PASO 8 - CONFIGURAR LA CONEXION
  Abre /public_html/diplomatic/app/config/database.php
  Edita: 'host', 'dbname', 'user', 'pass' con los datos del nuevo servidor

PASO 9 - VERIFICAR
  Abre el sistema en el navegador, inicia sesion como ADMIN
  Verifica que los modulos carguen y que imagenes/documentos se vean bien

REQUISITOS DEL SERVIDOR
------------------------
- PHP 8.2 o superior
- MariaDB 10.6 o superior
- Extension ZipArchive habilitada
- mod_rewrite habilitado (Apache)
- memory_limit minimo 512M
- max_execution_time minimo 300
- upload_max_filesize minimo 100M (para subir cada ZIP)

SOPORTE
-------
Desarrollado por Amarellus
www.amarellus.com

TXT;

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="INSTRUCCIONES_RESTAURACION.txt"');
        header('Content-Length: ' . strlen($txt));
        header('Pragma: no-cache');
        echo $txt;
        exit;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getSqlBatchDir(): string
    {
        return sys_get_temp_dir() . '/dip_sql_batch';
    }

    /** Blindaje de búfer: limpia cualquier salida previa antes de enviar binarios. */
    private function streamZip(string $zipPath, string $zipName, array $extraHeaders = []): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        foreach ($extraHeaders as $nombre => $valor) {
            header($nombre . ': ' . $valor);
        }
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    private function errorRedirect(string $msg): void
    {
        header('Location: /diplomatic/public/settings/database?error=' . urlencode($msg));
        exit;
    }

    private function limpiarDirectorio(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
        @rmdir($dir);
    }

    private function agregarCarpetaAZip(ZipArchive $zip, string $carpeta, string $base, string $prefix = ''): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($carpeta, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $localPath = $prefix
                    ? $prefix . '/' . substr($file->getRealPath(), strlen($carpeta) + 1)
                    : substr($file->getRealPath(), strlen($base) + 1);
                $zip->addFile($file->getRealPath(), $localPath);
            }
        }
    }

    private function agregarCarpetaFiltrada(ZipArchive $zip, string $carpeta, string $base, array $excluir, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($carpeta, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            $realPath  = $file->getRealPath();
            $localPath = substr($realPath, strlen($base) + 1);

            $saltar = false;
            foreach ($excluir as $ex) {
                if (str_starts_with($localPath, $ex)) { $saltar = true; break; }
            }
            if ($saltar) continue;

            $zip->addFile($realPath, $prefix . '/' . $localPath);
        }
    }
}