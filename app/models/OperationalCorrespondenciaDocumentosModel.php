<?php
/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: app/models/OperationalCorrespondenciaDocumentosModel.php
 * PROPÓSITO: A partir de una plantilla guardada, genera uno o varios PDF
 *            (uno por cada registro elegido de la tabla objetivo), con
 *            código único + QR de validación, y los persiste en BD/disco.
 *            Reutiliza DomPDF y phpqrcode exactamente como
 *            StudentsCertificatesController.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Dompdf\Dompdf;

class OperationalCorrespondenciaDocumentosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // PLANTILLAS (para el selector del paso 1)
    // =========================================================================

    public function getPlantillas(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, tipo_documento, tabla_objetivo FROM tbl_correspondencia_plantillas ORDER BY nombre"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlantillaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_correspondencia_plantillas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['campos_personalizados']) {
            $row['campos_personalizados_arr'] = json_decode($row['campos_personalizados'], true) ?: [];
        } else {
            $row['campos_personalizados_arr'] = [];
        }
        return $row ?: null;
    }

    // =========================================================================
    // REGISTROS DE LA TABLA OBJETIVO (selector del paso 2, con checkboxes)
    // =========================================================================

    public function getRegistros(string $tabla, string $search = ''): array
    {
        $like = "%{$search}%";

        switch ($tabla) {
            case 'ESTUDIANTES':
                $sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS etiqueta, student_code AS subtitulo
                        FROM tbl_students WHERE (first_name LIKE :s1 OR last_name LIKE :s2 OR student_code LIKE :s3 OR document_id LIKE :s4)
                        ORDER BY first_name LIMIT 200";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'PROFESORES':
                $sql = "SELECT id, full_name AS etiqueta, identification AS subtitulo
                        FROM tbl_professors WHERE is_active = 1 AND (full_name LIKE :s1 OR identification LIKE :s2)
                        ORDER BY full_name LIMIT 200";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':s1' => $like, ':s2' => $like]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'CENTROS_MEDICOS':
                $sql = "SELECT id, nombre AS etiqueta, direccion AS subtitulo
                        FROM tbl_centros_medicos WHERE is_active = 1 AND nombre LIKE :s1
                        ORDER BY nombre LIMIT 200";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':s1' => $like]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'DEPARTAMENTOS_UCLA':
                $sql = "SELECT id, nombre AS etiqueta, '' AS subtitulo
                        FROM tbl_departamentos_ucla WHERE is_active = 1 AND nombre LIKE :s1
                        ORDER BY nombre LIMIT 200";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':s1' => $like]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            default:
                return [];
        }
    }

    /**
     * Trae los datos reales del registro y los mapea a los mismos tokens
     * usados en OperationalCorrespondenciaPlantillasModel::getCamposSistema().
     */
    public function getDatosRegistro(string $tabla, int $id): array
    {
        switch ($tabla) {
            case 'ESTUDIANTES':
                $stmt = $this->db->prepare("SELECT * FROM tbl_students WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$r) return [];
                return [
                    '{estudiante_codigo}'          => $r['student_code'],
                    '{estudiante_nombre}'          => $r['first_name'],
                    '{estudiante_apellido}'        => $r['last_name'],
                    '{estudiante_cedula}'          => $r['document_id'],
                    '{estudiante_email}'           => $r['email'],
                    '{estudiante_titulo_pregrado}' => $r['undergraduate_degree'] ?? '',
                    '{estudiante_fecha_admision}'  => $r['admission_date'] ? date('d/m/Y', strtotime($r['admission_date'])) : '',
                    '{estudiante_estado}'          => $r['status'] ?? '',
                ];

            case 'PROFESORES':
                $stmt = $this->db->prepare("SELECT * FROM tbl_professors WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$r) return [];
                return [
                    '{profesor_cedula}'          => $r['identification'],
                    '{profesor_nombre}'          => $r['first_name'],
                    '{profesor_apellido}'        => $r['last_name'],
                    '{profesor_nombre_completo}' => $r['full_name'],
                    '{profesor_tipo}'            => $r['professor_type'],
                ];

            case 'CENTROS_MEDICOS':
                $stmt = $this->db->prepare("SELECT * FROM tbl_centros_medicos WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$r) return [];
                return [
                    '{centro_nombre}'    => $r['nombre'],
                    '{centro_direccion}' => $r['direccion'] ?? '',
                ];

            case 'DEPARTAMENTOS_UCLA':
                $stmt = $this->db->prepare("SELECT * FROM tbl_departamentos_ucla WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$r) return [];
                return ['{departamento_nombre}' => $r['nombre']];

            default:
                return [];
        }
    }

    public function getNombreParaArchivo(string $tabla, int $id): string
    {
        $stmt = null;
        switch ($tabla) {
            case 'ESTUDIANTES':
                $stmt = $this->db->prepare("SELECT CONCAT(first_name,'_',last_name) n FROM tbl_students WHERE id=:id"); break;
            case 'PROFESORES':
                $stmt = $this->db->prepare("SELECT full_name n FROM tbl_professors WHERE id=:id"); break;
            case 'CENTROS_MEDICOS':
                $stmt = $this->db->prepare("SELECT nombre n FROM tbl_centros_medicos WHERE id=:id"); break;
            case 'DEPARTAMENTOS_UCLA':
                $stmt = $this->db->prepare("SELECT nombre n FROM tbl_departamentos_ucla WHERE id=:id"); break;
        }
        if (!$stmt) return 'documento';
        $stmt->execute([':id' => $id]);
        $n = $stmt->fetchColumn();
        $n = $n ?: 'documento';
        return preg_replace('/[^A-Za-z0-9_]+/', '_', $n);
    }

    // =========================================================================
    // GENERACIÓN (lote)
    // =========================================================================

    /**
     * Genera un PDF por cada registro_id, los guarda en disco, y registra
     * cada uno en tbl_correspondencia_documentos bajo el mismo lote_id.
     * Devuelve el lote_id generado.
     */
    public function generarLote(int $plantillaId, array $registroIds, array $valoresPersonalizados, int $userId): string
    {
        $plantilla = $this->getPlantillaById($plantillaId);
        if (!$plantilla) throw new \Exception('Plantilla no encontrada.');

        $tabla   = $plantilla['tabla_objetivo'];
        $loteId  = 'LOTE-' . date('YmdHis') . '-' . substr(md5(uniqid((string) rand(), true)), 0, 6);

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/correspondencia/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($registroIds as $registroId) {
            $registroId = (int) $registroId;
            $datosSistema = $this->getDatosRegistro($tabla, $registroId);
            if (empty($datosSistema)) continue;

            $codigo = 'COR-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string) rand(), true)), 0, 8));
            $url    = $this->getFullUrlVerification($codigo);

            // Reemplazar variables del sistema + personalizadas en el HTML de la plantilla
            $html = $plantilla['contenido'];
            foreach ($datosSistema as $token => $valor) {
                $html = str_replace($token, htmlspecialchars((string) $valor), $html);
            }
            foreach ($valoresPersonalizados as $slug => $valor) {
                $html = str_replace('{' . $slug . '}', htmlspecialchars((string) $valor), $html);
            }

            $qrBase64 = $this->generateQrBase64($url);
            $pdfHtml  = $this->envolverPlantilla($html, $qrBase64, $codigo, $plantilla['tipo_documento']);

            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $dompdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'Times-Roman']);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($pdfHtml, 'UTF-8');
            $dompdf->render();

            $nombreArchivo = $codigo . '_' . $this->getNombreParaArchivo($tabla, $registroId) . '.pdf';
            file_put_contents($uploadDir . $nombreArchivo, $dompdf->output());
            $pdfPath = 'uploads/correspondencia/' . $nombreArchivo;

            $stmt = $this->db->prepare(
                "INSERT INTO tbl_correspondencia_documentos
                    (lote_id, plantilla_id, tabla_objetivo, registro_id, codigo, qr_url, valores_sistema, valores_personalizados, pdf_path, generated_by)
                 VALUES (:lote, :pid, :tabla, :rid, :codigo, :url, :vs, :vp, :pdf, :uid)"
            );
            $stmt->execute([
                ':lote' => $loteId, ':pid' => $plantillaId, ':tabla' => $tabla, ':rid' => $registroId,
                ':codigo' => $codigo, ':url' => $url,
                ':vs' => json_encode($datosSistema, JSON_UNESCAPED_UNICODE),
                ':vp' => json_encode($valoresPersonalizados, JSON_UNESCAPED_UNICODE),
                ':pdf' => $pdfPath, ':uid' => $userId,
            ]);
        }

        return $loteId;
    }

    private function getFullUrlVerification(string $code): string
    {
        $protocol   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host       = $_SERVER['HTTP_HOST'];
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir    = str_replace('/index.php', '', $scriptName);
        return "{$protocol}://{$host}{$baseDir}/verificar_correspondencia.php?code={$code}";
    }

    private function generateQrBase64(string $url): string
    {
        require_once dirname(__DIR__, 2) . '/tools/phpqrcode/qrlib.php';
        $tempDir = dirname(__DIR__, 2) . '/public/assets/temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
        $filename = $tempDir . 'qr_' . uniqid() . '.png';
        \QRcode::png($url, $filename, 'L', 4, 2);
        $data = file_get_contents($filename);
        unlink($filename);
        return 'data:image/png;base64,' . base64_encode($data);
    }

    private function envolverPlantilla(string $contenidoHtml, string $qrBase64, string $codigo, string $tipoDocumento): string
    {
        $pathUcla = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $imgUcla  = file_exists($pathUcla) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pathUcla)) : '';

        return "
        <html><head><meta charset='UTF-8'><style>
            body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #1a1a1a; }
            .header { text-align: center; margin-bottom: 25px; }
            .header img { height: 70px; }
            .contenido { text-align: justify; line-height: 1.5; margin-bottom: 40px; }
            .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 8pt; color: #555; border-top: 1px solid #ccc; padding-top: 8px; }
            .footer img { height: 70px; display:block; margin: 0 auto 4px; }
        </style></head><body>
            <div class='header'>" . ($imgUcla ? "<img src='{$imgUcla}'>" : '') . "<br><strong>" . htmlspecialchars($tipoDocumento) . "</strong></div>
            <div class='contenido'>{$contenidoHtml}</div>
            <div class='footer'>
                <img src='{$qrBase64}'>
                Código de verificación: <strong>{$codigo}</strong><br>
                Verifique la autenticidad de este documento escaneando el código QR.
            </div>
        </body></html>";
    }

    // =========================================================================
    // HISTORIAL
    // =========================================================================

    public function getHistorial(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND (d.codigo LIKE :s OR p.nombre LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        $sql = "SELECT d.id, d.lote_id, d.codigo, d.tabla_objetivo, d.registro_id, d.pdf_path, d.generated_at,
                       p.nombre AS plantilla_nombre, p.tipo_documento
                FROM tbl_correspondencia_documentos d
                INNER JOIN tbl_correspondencia_plantillas p ON p.id = d.plantilla_id
                {$where}
                ORDER BY d.generated_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countHistorial(string $search = ''): int
    {
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND (d.codigo LIKE :s OR p.nombre LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_correspondencia_documentos d
             INNER JOIN tbl_correspondencia_plantillas p ON p.id = d.plantilla_id {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getDocumentoById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_correspondencia_documentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function eliminarDocumento(int $id): void
    {
        $this->db->prepare("DELETE FROM tbl_correspondencia_documentos WHERE id = :id")->execute([':id' => $id]);
    }
}