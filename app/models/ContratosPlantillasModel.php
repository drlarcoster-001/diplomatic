<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/models/ContratosPlantillasModel.php
 * PROPÓSITO: Persistencia del catálogo de plantillas de contratos institucionales y sus campos personalizados.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ContratosPlantillasModel
{
    private PDO $db;

    // Siglas por tipo de contrato para generación de número
    public const TIPO_SIGLAS = [
        'Prestación de Servicios'   => 'PSS',
        'Honorarios Profesionales'  => 'HON',
        'Contrato de Trabajo'       => 'TRB',
        'Contrato de Obra'          => 'OBR',
        'Convenio de Colaboración'  => 'CON',
    ];

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todas las plantillas activas.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM tbl_contract_template_fields f WHERE f.template_id = t.id) as total_campos,
                (SELECT COUNT(*) FROM tbl_contracts c WHERE c.template_id = t.id) as total_contratos
                FROM tbl_contract_templates t
                WHERE t.is_active = 1
                AND (t.nombre LIKE ? OR t.tipo_contrato LIKE ?)
                ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una plantilla por ID con sus campos personalizados.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_contract_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$template) return null;

        // Cargar campos personalizados
        $stmtF = $this->db->prepare("SELECT * FROM tbl_contract_template_fields WHERE template_id = ? ORDER BY orden ASC");
        $stmtF->execute([$id]);
        $template['campos'] = $stmtF->fetchAll(PDO::FETCH_ASSOC);

        return $template;
    }

    /**
     * Lista de plantillas activas para selectores.
     */
    public function getActivasParaSelector(): array
    {
        $sql = "SELECT id, nombre, tipo_contrato FROM tbl_contract_templates WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta una nueva plantilla.
     */
    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_contract_templates (nombre, tipo_contrato, contenido, is_active, created_by)
                VALUES (?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            trim($data['tipo_contrato']),
            $data['contenido'] ?? '',
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza una plantilla existente.
     */
    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE tbl_contract_templates SET
                nombre        = ?,
                tipo_contrato = ?,
                contenido     = ?,
                updated_by    = ?,
                updated_at    = NOW()
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            trim($data['tipo_contrato']),
            $data['contenido'] ?? '',
            $userId,
            $id
        ]);
    }

    /**
     * Inactivación lógica de plantilla.
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_contract_templates SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$userId, $id]);
    }

    /**
     * Sincroniza los campos personalizados de una plantilla.
     * Elimina los existentes y los reinserta en el orden correcto.
     */
    public function syncCampos(int $templateId, array $campos): void
    {
        $this->db->prepare("DELETE FROM tbl_contract_template_fields WHERE template_id = ?")->execute([$templateId]);

        if (empty($campos)) return;

        $stmt = $this->db->prepare("INSERT INTO tbl_contract_template_fields (template_id, etiqueta, nombre_campo, tipo, orden) VALUES (?, ?, ?, ?, ?)");

        foreach ($campos as $orden => $campo) {
            if (empty(trim($campo['etiqueta'] ?? ''))) continue;

            $nombreCampo = strtolower(preg_replace('/\s+/', '_', trim($campo['etiqueta'])));

            $stmt->execute([
                $templateId,
                trim($campo['etiqueta']),
                $nombreCampo,
                $campo['tipo'] ?? 'texto',
                $orden
            ]);
        }
    }

    /**
     * Obtiene los campos de sistema disponibles (variables reservadas de tbl_personal).
     */
    public function getCamposSistema(): array
    {
        return [
            '{nombre_completo}'  => 'Nombre completo del personal',
            '{primer_nombre}'    => 'Primer nombre',
            '{apellido}'         => 'Apellido',
            '{cedula}'           => 'Cédula de identidad',
            '{fecha_nacimiento}' => 'Fecha de nacimiento',
            '{direccion}'        => 'Dirección',
            '{estado_civil}'     => 'Estado civil',
            '{email}'            => 'Correo electrónico',
            '{telefono_local}'   => 'Teléfono local',
            '{telefono_celular}' => 'Teléfono celular',
            '{grado_instruccion}'=> 'Grado de instrucción',
            '{tipo_personal}'    => 'Tipo de personal',
            '{fecha_inicio}'     => 'Fecha de inicio',
            '{fecha_fin}'        => 'Fecha de fin',
            '{expediente}'       => 'Código de expediente',
            '{fecha_contrato}'   => 'Fecha de generación del contrato',
            '{año_contrato}'     => 'Año del contrato',
            '{mes_contrato}'     => 'Mes del contrato',
            '{numero_contrato}'  => 'Número de contrato',
        ];
    }
}