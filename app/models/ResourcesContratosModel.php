<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/models/ResourcesContratosModel.php
 * PROPÓSITO: Persistencia de contratos generados, búsqueda de personal y sustitución de variables.
 * VERSIÓN: 2.0.0 - Agrega update() para reasignar personal/plantilla y
 *          regenerar el contenido, syncFieldValues() para sincronizar
 *          campos personalizados en la edición, y delete() para
 *          eliminación permanente (contrato + valores de campos + PDF
 *          físico, este último se borra desde el controller).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ResourcesContratosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todos los contratos generados con datos del personal y plantilla.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c.*,
                p.first_name, p.last_name, p.document_id, p.expediente,
                t.nombre as tipo_nombre, t.siglas as tipo_siglas,
                ct.nombre as tipo_contrato_nombre, ct.siglas as tipo_contrato_siglas,
                tpl.nombre as plantilla_nombre
                FROM tbl_contracts c
                JOIN tbl_personal p ON c.personal_id = p.id
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                JOIN tbl_contract_templates tpl ON c.template_id = tpl.id
                LEFT JOIN tbl_contract_types ct ON tpl.tipo_contrato_id = ct.id
                WHERE c.is_active = 1
                AND (
                    p.first_name LIKE ? OR p.last_name LIKE ? OR
                    p.document_id LIKE ? OR c.numero_contrato LIKE ? OR
                    tpl.nombre LIKE ?
                )
                ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un contrato por ID con sus valores de campos personalizados.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT c.*,
                p.first_name, p.last_name, p.document_id, p.expediente,
                p.fecha_nacimiento, p.direccion, p.estado_civil, p.email,
                p.telefono_local, p.telefono_celular, p.grado_instruccion,
                p.tipo_personal_id, p.fecha_inicio, p.fecha_fin,
                tp.nombre as tipo_personal_nombre,
                ct.siglas as tipo_contrato_siglas,
                tpl.nombre as plantilla_nombre, tpl.contenido as plantilla_contenido,
                tpl.tipo_contrato_id
                FROM tbl_contracts c
                JOIN tbl_personal p ON c.personal_id = p.id
                JOIN tbl_personal_tipos tp ON p.tipo_personal_id = tp.id
                JOIN tbl_contract_templates tpl ON c.template_id = tpl.id
                LEFT JOIN tbl_contract_types ct ON tpl.tipo_contrato_id = ct.id
                WHERE c.id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$contrato) return null;

        // Cargar valores de campos personalizados
        $stmtV = $this->db->prepare("
            SELECT f.etiqueta, f.nombre_campo, f.tipo, v.field_id, v.valor
            FROM tbl_contract_field_values v
            JOIN tbl_contract_template_fields f ON v.field_id = f.id
            WHERE v.contract_id = ?
            ORDER BY f.orden ASC
        ");
        $stmtV->execute([$id]);
        $contrato['campos_valores'] = $stmtV->fetchAll(PDO::FETCH_ASSOC);

        return $contrato;
    }

    /**
     * Busca personal activo para el selector.
     */
    public function buscarPersonal(string $term): array
    {
        $sql = "SELECT p.id, p.first_name, p.last_name, p.document_id,
                p.expediente, t.nombre as tipo_nombre, t.siglas
                FROM tbl_personal p
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                WHERE p.is_active = 1
                AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.document_id LIKE ? OR p.expediente LIKE ?)
                ORDER BY p.last_name ASC, p.first_name ASC
                LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $t = "%$term%";
        $stmt->execute([$t, $t, $t, $t]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene datos completos del personal para la vista previa.
     */
    public function getPersonalById(int $id): ?array
    {
        $sql = "SELECT p.*, t.nombre as tipo_personal_nombre, t.siglas
                FROM tbl_personal p
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                WHERE p.id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);
        return $persona ?: null;
    }

    /**
     * Obtiene plantillas activas para el selector con sus campos.
     */
    public function getPlantillas(): array
    {
        $sql = "SELECT t.id, t.nombre, ct.nombre as tipo_nombre, ct.siglas as tipo_siglas
                FROM tbl_contract_templates t
                LEFT JOIN tbl_contract_types ct ON t.tipo_contrato_id = ct.id
                WHERE t.is_active = 1
                ORDER BY t.nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una plantilla con sus campos personalizados.
     */
    public function getPlantillaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT t.*, ct.siglas as tipo_siglas FROM tbl_contract_templates t LEFT JOIN tbl_contract_types ct ON t.tipo_contrato_id = ct.id WHERE t.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$plantilla) return null;

        $stmtF = $this->db->prepare("SELECT * FROM tbl_contract_template_fields WHERE template_id = ? ORDER BY orden ASC");
        $stmtF->execute([$id]);
        $plantilla['campos'] = $stmtF->fetchAll(PDO::FETCH_ASSOC);

        return $plantilla;
    }

    /**
     * Genera el número de contrato único.
     * Formato: DIP-{SIGLAS_TIPO_CONTRATO}-{CEDULA}-{AÑO}-{CORRELATIVO}
     */
    public function generarNumeroContrato(string $siglasTipo, string $cedula): string
    {
        $año = date('Y');

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_contracts WHERE numero_contrato LIKE ?");
        $stmt->execute(["DIP-{$siglasTipo}-%-{$año}-%"]);
        $count       = (int)$stmt->fetchColumn();
        $correlativo = str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

        return "DIP-{$siglasTipo}-{$cedula}-{$año}-{$correlativo}";
    }

    /**
     * Sustituye las variables del sistema en el contenido de la plantilla.
     */
    public function sustituirVariablesSistema(string $contenido, array $persona, string $numeroContrato): string
    {
        $meses = [
            1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
            7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
        ];

        $vars = [
            '{nombre_completo}'   => trim(($persona['first_name'] ?? '') . ' ' . ($persona['last_name'] ?? '')),
            '{primer_nombre}'     => $persona['first_name']           ?? '',
            '{apellido}'          => $persona['last_name']            ?? '',
            '{cedula}'            => $persona['document_id']          ?? '',
            '{fecha_nacimiento}'  => !empty($persona['fecha_nacimiento']) ? date('d/m/Y', strtotime($persona['fecha_nacimiento'])) : '',
            '{direccion}'         => $persona['direccion']            ?? '',
            '{estado_civil}'      => $persona['estado_civil']         ?? '',
            '{email}'             => $persona['email']                ?? '',
            '{telefono_local}'    => $persona['telefono_local']       ?? '',
            '{telefono_celular}'  => $persona['telefono_celular']     ?? '',
            '{grado_instruccion}' => $persona['grado_instruccion']    ?? '',
            '{tipo_personal}'     => $persona['tipo_personal_nombre'] ?? '',
            '{fecha_inicio}'      => !empty($persona['fecha_inicio']) ? date('d/m/Y', strtotime($persona['fecha_inicio'])) : '',
            '{fecha_fin}'         => !empty($persona['fecha_fin'])    ? date('d/m/Y', strtotime($persona['fecha_fin']))    : '',
            '{expediente}'        => $persona['expediente']           ?? '',
            '{fecha_contrato}'    => date('d/m/Y'),
            '{año_contrato}'      => date('Y'),
            '{mes_contrato}'      => $meses[(int)date('m')],
            '{numero_contrato}'   => $numeroContrato,
            '{logo_diplomado}'    => $this->logoComoBase64('logo-medicina.jpg', 'image/jpeg', 'margin-right:40px;'),
            '{logo_ucla}'         => $this->logoComoBase64('logo-ucla.png', 'image/png', 'margin-left:40px;'),
            '{membrete_institucional}' => $this->getMembreteHtml(),
            '{membrete_institucional_2}' => $this->getMembreteHtml2(),
        ];

        return str_replace(array_keys($vars), array_values($vars), $contenido);
    }

    /**
     * Bloque fijo del membrete institucional: logo UCLA a la izquierda,
     * "UNIVERSIDAD CENTRO OCCIDENTAL" al centro y logo del diplomado a la
     * derecha (los 3 en la misma fila mediante tabla, que DomPDF sí
     * respeta de forma confiable), seguido de las 2 líneas centradas
     * debajo. Se inserta como variable de texto ({membrete_institucional})
     * en Quill, así el layout nunca se arma dentro del editor.
     */
    public function getMembreteHtml(): string
    {
        $logoUcla      = $this->logoComoBase64('logo-ucla.png', 'image/png');
        $logoDiplomado = $this->logoComoBase64('logo-medicina.jpg', 'image/jpeg');

        return '<table style="width:100%;border:none;margin-bottom:6mm;" cellpadding="0" cellspacing="0">
<tr>
<td style="width:15%;text-align:left;vertical-align:middle;">' . $logoUcla . '</td>
<td style="width:70%;text-align:center;vertical-align:middle;">
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">UNIVERSIDAD CENTRO OCCIDENTAL</div>
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">LISANDRO ALVARADO</div>
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">DECANATO DE CIENCIAS DE LA SALUD</div>
</td>
<td style="width:15%;text-align:right;vertical-align:middle;">' . $logoDiplomado . '</td>
</tr>
</table>';
    }

    public function getMembreteHtml2(): string
    {
        $logoUcla      = $this->logoComoBase64('logo-ucla-documentos.png', 'image/png');
        $logoDiplomado = $this->logoComoBase64('logo-medicina.jpg', 'image/jpeg');

        return '<table style="width:100%;border:none;margin-bottom:6mm;" cellpadding="0" cellspacing="0">
<tr>
<td style="width:15%;text-align:left;vertical-align:middle;">' . $logoUcla . '</td>
<td style="width:70%;text-align:center;vertical-align:middle;">
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">UNIVERSIDAD CENTRO OCCIDENTAL</div>
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">LISANDRO ALVARADO</div>
<div style="font-weight:bold;font-size:12pt;line-height:1.5;color:#1a1a4e;">DECANATO DE CIENCIAS DE LA SALUD</div>
</td>
<td style="width:15%;text-align:right;vertical-align:middle;">' . $logoDiplomado . '</td>
</tr>
</table>';
    }

    /**
     * Convierte un logo fijo del disco a una etiqueta <img> con la imagen
     * incrustada en base64. Evita el problema de rutas de archivo (válidas
     * para DomPDF) vs URLs web (válidas para el navegador) — el base64
     * funciona igual en ambos contextos sin depender de ninguna ruta.
     */
    private function logoComoBase64(string $nombreArchivo, string $mimeType, string $margenExtra = ''): string
    {
        $ruta = dirname(__DIR__, 2) . '/public/assets/uploads/logos/' . $nombreArchivo;
        if (!is_file($ruta)) return '';

        $base64 = base64_encode(file_get_contents($ruta));
        return '<img src="data:' . $mimeType . ';base64,' . $base64 . '" style="height:70px;' . $margenExtra . '">';
    }

    /**
     * Sustituye los campos personalizados en el contenido.
     */
    public function sustituirCamposPersonalizados(string $contenido, array $campos): string
    {
        foreach ($campos as $campo) {
            $contenido = str_replace(
                '{' . $campo['nombre_campo'] . '}',
                $campo['valor'] ?? '',
                $contenido
            );
        }
        return $contenido;
    }

    /**
     * Guarda el contrato generado en la base de datos.
     */
    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_contracts
                (numero_contrato, template_id, personal_id, contenido_final, estado, fecha_emision, is_active, created_by)
                VALUES (?, ?, ?, ?, 'Activo', CURDATE(), 1, ?)";

        $this->db->prepare($sql)->execute([
            $data['numero_contrato'],
            $data['template_id'],
            $data['personal_id'],
            $data['contenido_final'],
            $userId
        ]);

        $contratoId = (int)$this->db->lastInsertId();

        // Guardar valores de campos personalizados
        if (!empty($data['campos'])) {
            $stmt = $this->db->prepare("INSERT INTO tbl_contract_field_values (contract_id, field_id, valor) VALUES (?, ?, ?)");
            foreach ($data['campos'] as $campo) {
                $stmt->execute([$contratoId, $campo['field_id'], $campo['valor']]);
            }
        }

        return $contratoId;
    }

    /**
     * Actualiza un contrato existente: permite reasignar personal y/o
     * plantilla, regenerando el número de contrato y el contenido final.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_contracts SET
                numero_contrato  = ?,
                template_id      = ?,
                personal_id      = ?,
                contenido_final  = ?,
                updated_at       = NOW()
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            $data['numero_contrato'],
            $data['template_id'],
            $data['personal_id'],
            $data['contenido_final'],
            $id
        ]);
    }

    /**
     * Sincroniza los valores de campos personalizados de un contrato al
     * editarlo (borra los anteriores e inserta los nuevos).
     */
    public function syncFieldValues(int $contratoId, array $campos): void
    {
        $this->db->prepare("DELETE FROM tbl_contract_field_values WHERE contract_id = ?")->execute([$contratoId]);

        if (empty($campos)) return;

        $stmt = $this->db->prepare("INSERT INTO tbl_contract_field_values (contract_id, field_id, valor) VALUES (?, ?, ?)");
        foreach ($campos as $campo) {
            $stmt->execute([$contratoId, $campo['field_id'], $campo['valor']]);
        }
    }

    /**
     * Cambia el estado de un contrato.
     */
    public function changeStatus(int $id, string $estado): bool
    {
        return $this->db->prepare("UPDATE tbl_contracts SET estado = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$estado, $id]);
    }

    /**
     * Guarda la ruta del PDF generado.
     */
    public function savePdfPath(int $id, string $path): bool
    {
        return $this->db->prepare("UPDATE tbl_contracts SET pdf_path = ? WHERE id = ?")
                        ->execute([$path, $id]);
    }

    /**
     * Elimina un contrato de forma PERMANENTE (registro + valores de
     * campos personalizados). Devuelve la ruta del PDF asociado para que
     * el controller borre también el archivo físico, o null si no existía
     * el contrato.
     */
    public function delete(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT pdf_path FROM tbl_contracts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $this->db->prepare("DELETE FROM tbl_contract_field_values WHERE contract_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM tbl_contracts WHERE id = ?")->execute([$id]);

        return $row['pdf_path'] ?? null;
    }

    /**
     * Contratos de un personal específico.
     */
    public function getByPersonal(int $personalId): array
    {
        $sql = "SELECT c.*, tpl.nombre as plantilla_nombre, ct.nombre as tipo_contrato_nombre
                FROM tbl_contracts c
                JOIN tbl_contract_templates tpl ON c.template_id = tpl.id
                LEFT JOIN tbl_contract_types ct ON tpl.tipo_contrato_id = ct.id
                WHERE c.personal_id = ? AND c.is_active = 1
                ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$personalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}