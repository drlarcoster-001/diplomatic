<?php
/**
 * MÓDULO: ACADÉMICO / IMPORTADOR
 * ARCHIVO: app/models/AcademicImportadorModel.php
 * PROPÓSITO: Clona la configuración de un período origen a un nuevo período destino.
 *            Clona: cohortes, ofertas, grupos, profesores, horarios teóricos y modalidades.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AcademicImportadorModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // PERÍODOS
    // =========================================================================

    public function getPeriodos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, periodo_code, nombre, estado, fecha_inicio, fecha_fin
             FROM tbl_periodos_cohorte
             WHERE is_active = 1
             ORDER BY id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPeriodoById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_periodos_cohorte WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function existePeriodoCode(string $code): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_periodos_cohorte WHERE periodo_code = ?"
        );
        $stmt->execute([$code]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function insertPeriodo(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_periodos_cohorte
             (periodo_code, nombre, fecha_inicio, fecha_fin,
              apertura_inscripcion, cierre_inscripcion, descripcion,
              estado, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Planificado', 1, ?)"
        );
        $stmt->execute([
            $data['periodo_code'],
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['apertura_inscripcion'] ?: null,
            $data['cierre_inscripcion']   ?: null,
            $data['descripcion']          ?: null,
            $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    // =========================================================================
    // COHORTES DEL PERÍODO ORIGEN
    // =========================================================================

    public function getCohortesByPeriodo(int $periodoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_cohortes
             WHERE periodo_id = ? AND is_active = 1
             ORDER BY id ASC"
        );
        $stmt->execute([$periodoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // OFERTAS DE UNA COHORTE
    // =========================================================================

    public function getOfertasByCohorte(int $cohortId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_academic_offerings
             WHERE cohort_id = ? AND is_active = 1
             ORDER BY id ASC"
        );
        $stmt->execute([$cohortId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // IMPORTAR — PROCESO PRINCIPAL
    // =========================================================================

    public function importar(int $periodoOrigenId, int $periodoDestinoId, array $periodoDestino, int $userId): array
    {
        $log = [];
        $this->db->beginTransaction();

        try {
            $cohortes = $this->getCohortesByPeriodo($periodoOrigenId);

            foreach ($cohortes as $cohorte) {
                // Generar nuevo cohort_code
                $newCode = $this->generarNuevoCohortCode(
                    $cohorte['cohort_code'],
                    $periodoDestino['periodo_code']
                );

                // Evitar duplicados con numeración romana
                if ($this->existeCohortCode($newCode)) {
                    $roman = ['II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                    foreach ($roman as $r) {
                        $candidate = $newCode . '-' . $r;
                        if (!$this->existeCohortCode($candidate)) {
                            $newCode = $candidate;
                            break;
                        }
                    }
                }

                // Insertar nueva cohorte
                $newCohortId = $this->insertCohorte($cohorte, $newCode, $periodoDestinoId, $periodoDestino, $userId);
                $log[] = "✅ Cohorte clonada: {$cohorte['name']} → #{$newCohortId}";
                $this->clonarCohortCampuses($cohorte['id'], $newCohortId);
                $log[] = "  ✅ Sedes de cohorte clonadas";

                // Clonar ofertas
                $ofertas = $this->getOfertasByCohorte($cohorte['id']);
                foreach ($ofertas as $oferta) {
                    $newOfertaId = $this->insertOferta($oferta, $newCohortId, $periodoDestino, $userId);
                    $log[] = "  ✅ Oferta clonada: #{$oferta['id']} → #{$newOfertaId}";

                    // Clonar grupos y mapear IDs
                    $mapaGrupos = $this->clonarGrupos($oferta['id'], $newOfertaId);
                    if (!empty($mapaGrupos)) $log[] = "    ✅ Grupos clonados: " . count($mapaGrupos);

                    // Clonar profesores de la oferta
                    $this->clonarProfesores($oferta['id'], $newOfertaId);
                    $log[] = "    ✅ Profesores clonados";

                    // Clonar horarios teóricos
                    $this->clonarHorariosTeoricos($oferta['id'], $newOfertaId, $userId);
                    $log[] = "    ✅ Horarios teóricos clonados";

                    // Clonar modalidades de profesor
                    $this->clonarProfesorModalidad($oferta['id'], $newOfertaId, $mapaGrupos, $userId);
                    $log[] = "    ✅ Modalidades de profesor clonadas";

                    // Clonar sedes
                    $this->clonarCampuses($oferta['id'], $newOfertaId);
                    $log[] = "    ✅ Sedes clonadas";

                    // Clonar planes de pago
                    $this->clonarPaymentPlans($oferta['id'], $newOfertaId);
                    $log[] = "    ✅ Planes de pago clonados";
                }
            }

            $this->db->commit();
            return ['success' => true, 'log' => $log];

        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'log' => $log, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function generarNuevoCohortCode(string $oldCode, string $nuevoPeriodoCode): string
        {
            $sinPrefijo = preg_replace('/^\d{4}-COHORTE-\d+-/i', '', $oldCode);
            $sinPrefijo = preg_replace('/\s+/', '', $sinPrefijo); // Elimina espacios
            return $nuevoPeriodoCode . '-' . trim($sinPrefijo);
        }

    private function existeCohortCode(string $code): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_cohortes WHERE cohort_code = ?"
        );
        $stmt->execute([$code]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function insertCohorte(array $c, string $newCode, int $periodoDestinoId, array $periodoDestino, int $userId): int
    {
        // Reemplaza año/cohorte en el nombre
        $newName = preg_replace('/Cohorte\s+\d+/i', 'Cohorte ' . $this->extractCohorteNum($periodoDestino['periodo_code']), $c['name']);

        $stmt = $this->db->prepare(
            "INSERT INTO tbl_cohortes
             (periodo_id, diplomado_id, cohort_code, name,
              start_date, end_date, enrollment_start, enrollment_end,
              description, base_campus, cohort_status, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Planificada', 1, ?)"
        );
        $stmt->execute([
            $periodoDestinoId,
            $c['diplomado_id'],
            $newCode,
            $newName,
            $periodoDestino['fecha_inicio'],
            $periodoDestino['fecha_fin'],
            $periodoDestino['apertura_inscripcion'] ?? null,
            $periodoDestino['cierre_inscripcion']   ?? null,
            $c['description'],
            $c['base_campus'],
            $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function insertOferta(array $o, int $newCohortId, array $periodoDestino, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_academic_offerings
             (diploma_id, cohort_id, total_capacity, enrolled_count,
              registration_start, registration_end, class_start, class_end,
              general_modality, total_cost, currency_code,
              status, description, is_active, created_by)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, 'BORRADOR', ?, 1, ?)"
        );
        $stmt->execute([
            $o['diploma_id'],
            $newCohortId,
            $o['total_capacity'],
            $periodoDestino['apertura_inscripcion'] ?? $periodoDestino['fecha_inicio'],
            $periodoDestino['cierre_inscripcion']   ?? $periodoDestino['fecha_fin'],
            $periodoDestino['fecha_inicio'],
            $periodoDestino['fecha_fin'],
            $o['general_modality'],
            $o['total_cost'],
            $o['currency_code'],
            $o['description'],
            $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function clonarGrupos(int $oldOfertaId, int $newOfertaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_academic_offering_groups WHERE offering_id = ?"
        );
        $stmt->execute([$oldOfertaId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($grupos as $g) {
            $ins = $this->db->prepare(
                "INSERT INTO tbl_academic_offering_groups
                 (offering_id, group_id, schedule_info, is_enabled)
                 VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$newOfertaId, $g['group_id'], $g['schedule_info'], $g['is_enabled']]);
            $mapa[$g['id']] = (int) $this->db->lastInsertId();
        }
        return $mapa;
    }

    private function clonarProfesores(int $oldOfertaId, int $newOfertaId): void
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_academic_offering_professors WHERE offering_id = ?"
        );
        $stmt->execute([$oldOfertaId]);
        $profs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($profs as $p) {
            $ins = $this->db->prepare(
                "INSERT INTO tbl_academic_offering_professors
                 (offering_id, professor_id, role)
                 VALUES (?, ?, ?)"
            );
            $ins->execute([$newOfertaId, $p['professor_id'], $p['role']]);
        }
    }

    private function clonarHorariosTeoricos(int $oldOfertaId, int $newOfertaId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_horarios_teoricos
             WHERE offering_id = ? AND is_active = 1"
        );
        $stmt->execute([$oldOfertaId]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($horarios as $h) {
            $ins = $this->db->prepare(
                "INSERT INTO tbl_horarios_teoricos
                 (offering_id, dia_semana, hora_inicio, hora_fin, is_active, created_by)
                 VALUES (?, ?, ?, ?, 1, ?)"
            );
            $ins->execute([$newOfertaId, $h['dia_semana'], $h['hora_inicio'], $h['hora_fin'], $userId]);
        }
    }

    private function clonarProfesorModalidad(int $oldOfertaId, int $newOfertaId, array $mapaGrupos, int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_profesor_modalidad WHERE offering_id = ?"
        );
        $stmt->execute([$oldOfertaId]);
        $modalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($modalidades as $m) {
            $newGroupId = $m['offering_group_id']
                ? ($mapaGrupos[$m['offering_group_id']] ?? null)
                : null;

            $ins = $this->db->prepare(
                "INSERT INTO tbl_profesor_modalidad
                 (offering_id, offering_group_id, professor_id, modalidad, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $ins->execute([$newOfertaId, $newGroupId, $m['professor_id'], $m['modalidad'], $userId]);
        }
    }

    private function extractCohorteNum(string $periodoCode): string
    {
        preg_match('/COHORTE-(\d+)/i', $periodoCode, $matches);
        return $matches[1] ?? '16';
    }

    // =========================================================================
// REGISTRAR IMPORTACIÓN
// =========================================================================

public function registrarImportacion(int $origenId, int $destinoId, int $userId): void
{
    $this->db->prepare(
        "INSERT INTO tbl_importaciones
         (periodo_origen_id, periodo_destino_id, importado_por)
         VALUES (?, ?, ?)"
    )->execute([$origenId, $destinoId, $userId]);
}

public function getImportaciones(): array
{
    $stmt = $this->db->query(
        "SELECT i.id, i.created_at,
                po.nombre AS origen_nombre,
                pd.nombre AS destino_nombre,
                pd.id AS periodo_destino_id,
                pd.estado AS destino_estado,
                u.first_name, u.last_name
         FROM tbl_importaciones i
         INNER JOIN tbl_periodos_cohorte po ON po.id = i.periodo_origen_id
         INNER JOIN tbl_periodos_cohorte pd ON pd.id = i.periodo_destino_id
         INNER JOIN tbl_users u ON u.id = i.importado_por
         ORDER BY i.created_at DESC"
    );
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

// =========================================================================
// REVERSAR IMPORTACIÓN
// =========================================================================

public function reversarImportacion(int $periodoDestinoId): array
{
    $log = [];
    $this->db->beginTransaction();

    // Verificar que el período esté en estado Planificado
    $periodo = $this->getPeriodoById($periodoDestinoId);
    if (!$periodo || $periodo['estado'] !== 'Planificado') {
        return [
            'success' => false,
            'error'   => 'Solo se puede reversar un período en estado Planificado.',
            'log'     => []
        ];
    }

    try {
        // 1. Obtener cohortes del período destino
        $cohortes = $this->getCohortesByPeriodo($periodoDestinoId);

        foreach ($cohortes as $cohorte) {
            $ofertas = $this->getOfertasByCohorte($cohorte['id']);

            foreach ($ofertas as $oferta) {
                $oid = $oferta['id'];

                // Eliminar sedes
$this->db->prepare("DELETE FROM tbl_academic_offering_campuses WHERE offering_id = ?")->execute([$oid]);
// Eliminar planes de pago
$this->db->prepare("DELETE FROM tbl_academic_offering_payment_plans WHERE offering_id = ?")->execute([$oid]);
                // Eliminar modalidades
                $this->db->prepare("DELETE FROM tbl_profesor_modalidad WHERE offering_id = ?")->execute([$oid]);
                // Eliminar horarios teóricos
                $this->db->prepare("DELETE FROM tbl_horarios_teoricos WHERE offering_id = ?")->execute([$oid]);
                // Eliminar profesores
                $this->db->prepare("DELETE FROM tbl_academic_offering_professors WHERE offering_id = ?")->execute([$oid]);
                // Eliminar grupos
                $this->db->prepare("DELETE FROM tbl_academic_offering_groups WHERE offering_id = ?")->execute([$oid]);
                // Eliminar oferta
                $this->db->prepare("DELETE FROM tbl_academic_offerings WHERE id = ?")->execute([$oid]);

                $log[] = "🗑 Oferta #{$oid} eliminada";
            }

            // Eliminar sedes de cohorte
            $this->db->prepare("DELETE FROM tbl_cohort_campuses WHERE cohort_id = ?")->execute([$cohorte['id']]);
            $log[] = "🗑 Cohorte #{$cohorte['id']} eliminada";
        }

        // Eliminar período
        $this->db->prepare("DELETE FROM tbl_periodos_cohorte WHERE id = ?")->execute([$periodoDestinoId]);
        $log[] = "🗑 Período #{$periodoDestinoId} eliminado";

        // Eliminar registro de importación
        $this->db->prepare("DELETE FROM tbl_importaciones WHERE periodo_destino_id = ?")->execute([$periodoDestinoId]);

        $this->db->commit();
        return ['success' => true, 'log' => $log];

    } catch (\Throwable $e) {
        $this->db->rollBack();
        return ['success' => false, 'error' => $e->getMessage(), 'log' => $log];
    }
}

private function clonarCampuses(int $oldOfertaId, int $newOfertaId): void
{
    $stmt = $this->db->prepare(
        "SELECT * FROM tbl_academic_offering_campuses WHERE offering_id = ?"
    );
    $stmt->execute([$oldOfertaId]);
    $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($campuses as $c) {
        $this->db->prepare(
            "INSERT INTO tbl_academic_offering_campuses
             (offering_id, campus_id) VALUES (?, ?)"
        )->execute([$newOfertaId, $c['campus_id']]);
    }
}

private function clonarPaymentPlans(int $oldOfertaId, int $newOfertaId): void
{
    $stmt = $this->db->prepare(
        "SELECT * FROM tbl_academic_offering_payment_plans WHERE offering_id = ?"
    );
    $stmt->execute([$oldOfertaId]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($plans as $p) {
        $this->db->prepare(
            "INSERT INTO tbl_academic_offering_payment_plans
             (offering_id, name, amount, due_date, notes)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$newOfertaId, $p['name'], $p['amount'], $p['due_date'], $p['notes']]);
    }
}

private function clonarCohortCampuses(int $oldCohortId, int $newCohortId): void
{
    $stmt = $this->db->prepare(
        "SELECT * FROM tbl_cohort_campuses WHERE cohort_id = ?"
    );
    $stmt->execute([$oldCohortId]);
    $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($campuses as $c) {
        $this->db->prepare(
            "INSERT IGNORE INTO tbl_cohort_campuses
             (cohort_id, campus_id) VALUES (?, ?)"
        )->execute([$newCohortId, $c['campus_id']]);
    }
}

public function existeImportacionOrigen(int $origenId): bool
{
    $stmt = $this->db->prepare(
        "SELECT COUNT(*) FROM tbl_importaciones WHERE periodo_origen_id = ?"
    );
    $stmt->execute([$origenId]);
    return (int)$stmt->fetchColumn() > 0;
}
}