<?php
/**
 * MÓDULO: CORRESPONDENCIA / PLANTILLAS
 * ARCHIVO: app/models/OperationalCorrespondenciaPlantillasModel.php
 * PROPÓSITO: CRUD de plantillas de correspondencia. Cada plantilla se asocia
 *            a UNA tabla objetivo (Estudiantes, Profesores, Centros Médicos,
 *            Departamentos UCLA), de la cual se ofrecen variables del sistema
 *            ya conocidas, más campos personalizados definidos libremente.
 *            El contenido se redacta con Quill (mismo patrón que Plantillas
 *            de Contrato).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class OperationalCorrespondenciaPlantillasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // CATÁLOGOS ESTÁTICOS
    // =========================================================================

    public function getTiposDocumento(): array
    {
        return [
            'CARTA'          => 'Carta',
            'MEMO'           => 'Memo',
            'OFICIO'         => 'Oficio',
            'ACTA'           => 'Acta',
            'RECONOCIMIENTO' => 'Reconocimiento',
            'CONSTANCIA'     => 'Constancia',
        ];
    }

    public function getTablasObjetivo(): array
    {
        return [
            'ESTUDIANTES'        => 'Estudiantes',
            'PROFESORES'         => 'Profesores',
            'CENTROS_MEDICOS'    => 'Centros Médicos',
            'DEPARTAMENTOS_UCLA' => 'Departamentos UCLA',
        ];
    }

    public function getCamposSistema(string $tablaObjetivo): array
    {
        $mapa = [
            'ESTUDIANTES' => [
                '{estudiante_codigo}'         => 'Código de Estudiante',
                '{estudiante_nombre}'         => 'Nombre',
                '{estudiante_apellido}'       => 'Apellido',
                '{estudiante_cedula}'         => 'Cédula / Documento',
                '{estudiante_email}'          => 'Correo Electrónico',
                '{estudiante_titulo_pregrado}'=> 'Título de Pregrado',
                '{estudiante_fecha_admision}' => 'Fecha de Admisión',
                '{estudiante_estado}'         => 'Estado',
            ],
            'PROFESORES' => [
                '{profesor_cedula}'          => 'Cédula / Identificación',
                '{profesor_nombre}'          => 'Nombre',
                '{profesor_apellido}'        => 'Apellido',
                '{profesor_nombre_completo}' => 'Nombre Completo',
                '{profesor_tipo}'            => 'Tipo de Profesor',
            ],
            'CENTROS_MEDICOS' => [
                '{centro_nombre}'    => 'Nombre del Centro',
                '{centro_direccion}' => 'Dirección',
            ],
            'DEPARTAMENTOS_UCLA' => [
                '{departamento_nombre}' => 'Nombre del Departamento',
            ],
        ];

        return $mapa[$tablaObjetivo] ?? [];
    }

    // =========================================================================
    // LISTADO (INDEX)
    // =========================================================================

    public function getPlantillas(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        $sql = "SELECT id, nombre, tipo_documento, tabla_objetivo, created_at
                FROM tbl_correspondencia_plantillas
                {$where}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPlantillas(string $search = ''): int
    {
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_correspondencia_plantillas {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // DETALLE / CRUD
    // =========================================================================

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

    public function crearPlantilla(string $nombre, string $tipo, string $tabla, string $contenido, array $camposPersonalizados, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_correspondencia_plantillas
                (nombre, tipo_documento, tabla_objetivo, contenido, campos_personalizados, created_by)
             VALUES (:nombre, :tipo, :tabla, :contenido, :campos, :uid)"
        );
        $stmt->execute([
            ':nombre' => $nombre, ':tipo' => $tipo, ':tabla' => $tabla,
            ':contenido' => $contenido, ':campos' => json_encode($camposPersonalizados, JSON_UNESCAPED_UNICODE),
            ':uid' => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarPlantilla(int $id, string $nombre, string $tipo, string $tabla, string $contenido, array $camposPersonalizados, int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE tbl_correspondencia_plantillas
             SET nombre = :nombre, tipo_documento = :tipo, tabla_objetivo = :tabla,
                 contenido = :contenido, campos_personalizados = :campos, updated_by = :uid
             WHERE id = :id"
        );
        $stmt->execute([
            ':nombre' => $nombre, ':tipo' => $tipo, ':tabla' => $tabla,
            ':contenido' => $contenido, ':campos' => json_encode($camposPersonalizados, JSON_UNESCAPED_UNICODE),
            ':uid' => $userId, ':id' => $id,
        ]);
    }

    public function countDocumentosGenerados(int $plantillaId): int
    {
        // Se activa cuando exista la tabla de documentos generados (Parte 2 del módulo).
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = 'tbl_correspondencia_documentos'"
        );
        $stmt->execute();
        if ((int) $stmt->fetchColumn() === 0) return 0;

        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM tbl_correspondencia_documentos WHERE plantilla_id = :id");
        $stmt2->execute([':id' => $plantillaId]);
        return (int) $stmt2->fetchColumn();
    }

    public function eliminarPlantilla(int $id): void
    {
        $this->db->prepare("DELETE FROM tbl_correspondencia_plantillas WHERE id = :id")->execute([':id' => $id]);
    }
}