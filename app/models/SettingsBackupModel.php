<?php
/**
 * MÓDULO: CONFIGURACIÓN / RESPALDO DEL SISTEMA
 * ARCHIVO: app/models/SettingsBackupModel.php
 * PROPÓSITO: Genera dump SQL troceado (por tamaño, cortando solo entre
 *            sentencias completas) y calcula particiones dinámicas de la
 *            carpeta enrollments (por carpeta de inscripción, sin cortar
 *            ninguna a la mitad) para que cada ZIP quepa cómodamente
 *            dentro del max_execution_time de 300s en cPanel.
 * VERSIÓN: 11.0.0 - Troceo dinámico por tamaño (80MB/parte). Excluye
 *          /storage y /db del respaldo (contenían backups manuales
 *          antiguos, depurados el 03-07-2026). Separa "Sistema" (app+tools)
 *          de "Público" (public sin uploads) y agrega componente "Raíz".
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SettingsBackupModel
{
    private PDO    $db;
    private array  $config;
    private string $host;
    private string $user;
    private string $pass;
    private string $dbname;

    private const LOTE = 200;
    private const TABLAS_SIN_DATOS = ['tbl_audit_logs'];

    /** Umbral de corte por parte: 80MB. Deja margen amplio dentro de los 300s de cPanel. */
    public const CHUNK_MAX_BYTES = 83886080; // 80 * 1024 * 1024

    public static function getRoot(): string
    {
        return rtrim(dirname(__DIR__, 2), '/');
    }

    /** Logos, profesores, recursos web (dentro de public/, pequeño) */
    public static function getCarpetasAssets(): array
    {
        $pub = self::getRoot() . '/public/assets/uploads';
        return [
            'logos'           => $pub . '/logos',
            'news_web'        => $pub . '/news_web',
            'profesores'      => $pub . '/profesores',
            'profesores_docs' => $pub . '/profesores_docs',
            'profesores_web'  => $pub . '/profesores_web',
        ];
    }

    /** Documentos generales de estudiantes (SIN enrollments, se trocea aparte) */
    public static function getCarpetasUploadsGenerales(): array
    {
        $pub = self::getRoot() . '/public/uploads';
        return [
            'constancias'     => $pub . '/constancias',
            'contratos'       => $pub . '/contratos',
            'correspondencia' => $pub . '/correspondencia',
            'personal'        => $pub . '/personal',
            'tesoreria'       => $pub . '/tesoreria',
        ];
    }

    public static function getEnrollmentsPath(): string
    {
        return self::getRoot() . '/public/uploads/enrollments';
    }

    /** Archivos sueltos de la raíz del proyecto que sí se respaldan */
    public static function getArchivosRaiz(): array
    {
        $root = self::getRoot();
        return [
            'README.md'           => $root . '/README.md',
            'estructura.txt'      => $root . '/estructura.txt',
            'modulos parte 2.txt' => $root . '/modulos parte 2.txt',
        ];
    }

    public static function getTamanoCarpeta(string $path): string
    {
        return self::formatBytes(self::getTamanoCarpetaBytes($path));
    }

    public static function getTamanoCarpetaBytes(string $path): int
    {
        if (!is_dir($path)) return 0;
        $size = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if ($f->isFile()) $size += $f->getSize();
        }
        return $size;
    }

    public static function getTamanoMultiple(array $carpetas): string
    {
        $size = 0;
        foreach ($carpetas as $ruta) {
            $size += self::getTamanoCarpetaBytes($ruta);
        }
        return self::formatBytes($size);
    }

    /** Tamaño de public/ EXCLUYENDO uploads (para el componente "Público") */
    public static function getTamanoPublicoSinUploads(): string
    {
        $root      = self::getRoot();
        $publicDir = $root . '/public';
        $uploadsDir = $publicDir . '/uploads';

        if (!is_dir($publicDir)) return '—';

        $totalPublic = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($publicDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            if (str_starts_with($f->getRealPath(), $uploadsDir)) continue;
            $totalPublic += $f->getSize();
        }
        return self::formatBytes($totalPublic);
    }

    public static function formatBytes(int $size): string
    {
        if ($size < 1024)        return $size . ' B';
        if ($size < 1048576)     return round($size / 1024, 1) . ' KB';
        if ($size < 1073741824)  return round($size / 1048576, 1) . ' MB';
        return round($size / 1073741824, 2) . ' GB';
    }

    /**
     * Lista cada carpeta dentro de enrollments con su tamaño real en bytes,
     * ordenadas de forma natural (166, 349, 361... como IDs numéricos).
     */
    public static function getEnrollmentFolders(): array
    {
        $base = self::getEnrollmentsPath();
        if (!is_dir($base)) return [];

        $folders = [];
        foreach (scandir($base) as $item) {
            if ($item === '.' || $item === '..') continue;
            $ruta = $base . '/' . $item;
            if (!is_dir($ruta)) continue;
            $folders[] = [
                'nombre' => $item,
                'ruta'   => $ruta,
                'size'   => self::getTamanoCarpetaBytes($ruta),
            ];
        }

        usort($folders, fn($a, $b) => strnatcmp($a['nombre'], $b['nombre']));
        return $folders;
    }

    /**
     * Agrupa las carpetas de enrollments en particiones de hasta
     * CHUNK_MAX_BYTES, sin cortar NUNCA una carpeta de inscripción a la
     * mitad (aunque eso haga que una parte se pase un poco del umbral,
     * como ocurre con la carpeta 361 de 39MB).
     *
     * @return array<int, array<int, array{nombre:string, ruta:string, size:int}>>
     */
    public static function calcularParticionesEnrollments(): array
    {
        $folders = self::getEnrollmentFolders();
        $partes  = [];
        $actual  = [];
        $acumulado = 0;

        foreach ($folders as $f) {
            if ($acumulado > 0 && ($acumulado + $f['size']) > self::CHUNK_MAX_BYTES) {
                $partes[] = $actual;
                $actual = [];
                $acumulado = 0;
            }
            $actual[] = $f;
            $acumulado += $f['size'];
        }
        if (!empty($actual)) $partes[] = $actual;

        return $partes;
    }

    public function __construct()
    {
        $this->db     = (new Database())->getConnection();
        $this->config = require self::getRoot() . '/app/config/database.php';
        $this->host   = $this->config['host']   ?? 'localhost';
        $this->user   = $this->config['user']   ?? 'root';
        $this->pass   = $this->config['pass']   ?? '';
        $this->dbname = $this->config['dbname'] ?? '';
    }

    public function getDbName(): string
    {
        return $this->dbname;
    }

    /** Estimado (via information_schema) del tamaño de la BD, para el plan inicial. */
    public function getEstimatedDbSize(): int
    {
        $stmt = $this->db->query(
            "SELECT SUM(data_length + index_length) AS total
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /** Número estimado de partes SQL, usado solo para el popup antes de generar el dump real. */
    public function calcularParticionesSqlEstimado(): int
    {
        $size = $this->getEstimatedDbSize();
        if ($size <= 0) return 1;
        // El SQL en texto pesa más que los datos crudos (escapes, formato), factor conservador x1.3
        $estimadoTexto = (int) ceil($size * 1.3);
        return max(1, (int) ceil($estimadoTexto / self::CHUNK_MAX_BYTES));
    }

    /**
     * Genera el dump completo troceado en archivos parte_1.sql, parte_2.sql...
     * dentro de $tmpDir. Corta SOLO entre sentencias completas (nunca a mitad
     * de un INSERT ni de un CREATE TABLE), por lo que el orden de importación
     * secuencial (parte_1, parte_2, ...) siempre es válido.
     *
     * @return string[] rutas absolutas de los archivos generados, en orden
     */
    public function generarDumpParticionado(string $tmpDir): array
    {
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        $parteNum   = 1;
        $rutaActual = $tmpDir . '/parte_' . $parteNum . '.sql';
        $fh = fopen($rutaActual, 'w');
        if (!$fh) throw new \RuntimeException('No se pudo crear el archivo de dump.');
        $archivos = [$rutaActual];

        $escribirCabecera = function ($fh, $parteNum) {
            fwrite($fh, "-- DIPLOMATIC SQL Dump - Parte {$parteNum}\n-- Generado: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fh, "-- Base de datos: {$this->dbname}\n\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        };
        $escribirCabecera($fh, $parteNum);

        $rotarSiNecesario = function () use (&$fh, &$parteNum, &$rutaActual, &$archivos, $tmpDir, $escribirCabecera) {
            if (ftell($fh) >= self::CHUNK_MAX_BYTES) {
                fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
                fclose($fh);
                $parteNum++;
                $rutaActual = $tmpDir . '/parte_' . $parteNum . '.sql';
                $fh = fopen($rutaActual, 'w');
                $archivos[] = $rutaActual;
                $escribirCabecera($fh, $parteNum);
            }
        };

        $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $tablas = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tablas as $tabla) {
            $create = $this->db->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($fh, "DROP TABLE IF EXISTS `{$tabla}`;\n");
            fwrite($fh, $create['Create Table'] . ";\n\n");
            $rotarSiNecesario();

            if (in_array($tabla, self::TABLAS_SIN_DATOS, true)) continue;

            $count = (int) $this->db->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
            if ($count === 0) continue;

            $pdoUnbuffered = $this->conectar();
            $pdoUnbuffered->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $stmt = $pdoUnbuffered->query("SELECT * FROM `{$tabla}`");

            $cols = null; $buffer = []; $contador = 0;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                }
                $vals = implode(', ', array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    return $this->db->quote((string) $v);
                }, array_values($row)));
                $buffer[] = "({$vals})";
                $contador++;

                if ($contador % self::LOTE === 0) {
                    fwrite($fh, "INSERT INTO `{$tabla}` ({$cols}) VALUES " . implode(",\n", $buffer) . ";\n");
                    $buffer = [];
                    $rotarSiNecesario();
                }
            }
            if (!empty($buffer)) {
                fwrite($fh, "INSERT INTO `{$tabla}` ({$cols}) VALUES " . implode(",\n", $buffer) . ";\n");
                $rotarSiNecesario();
            }
            fwrite($fh, "\n");
            $stmt = null; $pdoUnbuffered = null;
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);

        return $archivos;
    }

    private function conectar(): PDO
    {
        return new PDO(
            "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
            $this->user, $this->pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}