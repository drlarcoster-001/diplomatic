<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/models/ResourcesContratosPlantillasModel.php
 * PROPÓSITO: Persistencia del catálogo de plantillas de contratos institucionales y sus campos personalizados.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ResourcesContratosPlantillasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todas las plantillas activas con conteo de campos y contratos.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT t.*, ct.nombre as tipo_nombre, ct.siglas as tipo_siglas,
                (SELECT COUNT(*) FROM tbl_contract_template_fields f WHERE f.template_id = t.id) as total_campos,
                (SELECT COUNT(*) FROM tbl_contracts c WHERE c.template_id = t.id) as total_contratos
                FROM tbl_contract_templates t
                LEFT JOIN tbl_contract_types ct ON t.tipo_contrato_id = ct.id
                WHERE t.is_active = 1
                AND (t.nombre LIKE ? OR ct.nombre LIKE ?)
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
        $sql = "SELECT t.*, ct.nombre as tipo_nombre, ct.siglas as tipo_siglas
                FROM tbl_contract_templates t
                LEFT JOIN tbl_contract_types ct ON t.tipo_contrato_id = ct.id
                WHERE t.id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$template) return null;

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
        $sql = "SELECT t.id, t.nombre, ct.nombre as tipo_nombre, ct.siglas as tipo_siglas
                FROM tbl_contract_templates t
                LEFT JOIN tbl_contract_types ct ON t.tipo_contrato_id = ct.id
                WHERE t.is_active = 1
                ORDER BY t.nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista de tipos de contrato activos para el selector del formulario.
     */
    public function getTiposContrato(): array
    {
        $sql = "SELECT id, nombre, siglas FROM tbl_contract_types WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta una nueva plantilla.
     */
    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_contract_templates (nombre, tipo_contrato_id, contenido, is_active, created_by)
                VALUES (?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            (int)$data['tipo_contrato_id'],
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
                nombre          = ?,
                tipo_contrato_id = ?,
                contenido       = ?,
                updated_by      = ?,
                updated_at      = NOW()
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            (int)$data['tipo_contrato_id'],
            $data['contenido'] ?? '',
            $userId,
            $id
        ]);
    }

    /**
     * Inactivación lógica de plantilla.
     * No se elimina físicamente si tiene contratos generados.
     */
    public function smartDelete(int $id, int $userId): string
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_contracts WHERE template_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) return 'referenced';

        $res = $this->db->prepare("UPDATE tbl_contract_templates SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$userId, $id]);

        // Limpiar campos huérfanos si se inactiva
        if ($res) {
            $this->db->prepare("DELETE FROM tbl_contract_template_fields WHERE template_id = ?")->execute([$id]);
        }

        return $res ? 'deleted' : 'error';
    }

    /**
     * Sincroniza los campos personalizados de una plantilla.
     */
    public function syncCampos(int $templateId, array $campos): void
    {
        $this->db->prepare("DELETE FROM tbl_contract_template_fields WHERE template_id = ?")->execute([$templateId]);

        if (empty($campos)) return;

        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $stmt = $this->db->prepare("INSERT INTO tbl_contract_template_fields (template_id, etiqueta, nombre_campo, tipo, orden) VALUES (?, ?, ?, ?, ?)");

        foreach ($campos as $orden => $campo) {
            if (empty(trim($campo['etiqueta'] ?? ''))) continue;

            $nombreCampo = strtolower(preg_replace('/[\s\-]+/', '_', trim($campo['etiqueta'])));
            $nombreCampo = preg_replace('/[^a-z0-9_]/', '', $nombreCampo);
            $tipo        = in_array($campo['tipo'], ['texto','numero','fecha','moneda']) ? $campo['tipo'] : 'texto';

            $this->db->exec("INSERT INTO tbl_contract_template_fields 
                (template_id, etiqueta, nombre_campo, tipo, orden) 
                VALUES ($templateId, '" . addslashes(trim($campo['etiqueta'])) . "', '$nombreCampo', '$tipo', $orden)");
        }
    }

    /**
     * Campos de sistema disponibles (variables reservadas de tbl_personal).
     */
    public function getCamposSistema(): array
    {
        return [
            '{nombre_completo}'   => 'Nombre completo del personal',
            '{primer_nombre}'     => 'Primer nombre',
            '{apellido}'          => 'Apellido',
            '{cedula}'            => 'Cédula de identidad',
            '{fecha_nacimiento}'  => 'Fecha de nacimiento',
            '{direccion}'         => 'Dirección',
            '{estado_civil}'      => 'Estado civil',
            '{email}'             => 'Correo electrónico',
            '{telefono_local}'    => 'Teléfono local',
            '{telefono_celular}'  => 'Teléfono celular',
            '{grado_instruccion}' => 'Grado de instrucción',
            '{tipo_personal}'     => 'Tipo de personal',
            '{fecha_inicio}'      => 'Fecha de inicio',
            '{fecha_fin}'         => 'Fecha de fin',
            '{expediente}'        => 'Código de expediente',
            '{fecha_contrato}'    => 'Fecha de generación del contrato',
            '{año_contrato}'      => 'Año del contrato',
            '{mes_contrato}'      => 'Mes del contrato',
            '{numero_contrato}'   => 'Número de contrato',
        ];
    }
}