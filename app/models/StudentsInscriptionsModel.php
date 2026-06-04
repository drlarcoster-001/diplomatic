<?php
/**
 * MÓDULO: ESTUDIANTES / INSCRIPCIONES
 * ARCHIVO: app/models/StudentsInscriptionsModel.php
 * PROPÓSITO: Extracción relacional de ofertas con soporte para planes de pago, cupos dinámicos y cuerpo docente.
 * VERSIÓN: 1.6.4 - FIX: Inclusión de Calculadora de Monto Mínimo Inicial (Inscripción + Cuota 1).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class StudentsInscriptionsModel
{
    protected PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

public function getEffectiveRate(string $date): array|false
    {
        $sql = "SELECT dolar_bcv, euro_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE rate_date <= :target_date 
                AND status = 'ACTIVE' 
                ORDER BY rate_date DESC, id DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':target_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * PASO 2: Obtiene los datos de base del perfil del estudiante.
     */
    public function getStudentProfileData(int $userId): array {
        $sql = "SELECT undergraduate_degree, provenance 
                FROM tbl_users 
                WHERE id = ? LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Consulta el plan de cuotas configurado para una oferta específica.
     * Fuente de datos para el popup de "Ver Plan de Pagos".
     */
    public function getOfferingPaymentPlan(int $offeringId): array {
        $sql = "SELECT name, amount, due_date 
                FROM tbl_academic_offering_payment_plans 
                WHERE offering_id = ? 
                ORDER BY id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * NUEVO FIX (PASO 4): Calculadora del Monto Mínimo Inicial (Inscripción + Cuota 1)
     * Extrae de forma segura los dos primeros montos para blindar la inscripción.
     */
    public function getInitialPaymentDetails(int $offeringId): array {
        $plan = $this->getOfferingPaymentPlan($offeringId);
        
        $inscripcion = 0.00;
        $cuota1 = 0.00;
        
        // Asumimos por regla de negocio que la fila 0 es Inscripción y la fila 1 es Cuota 1
        if (isset($plan[0])) $inscripcion = (float)$plan[0]['amount'];
        if (isset($plan[1])) $cuota1 = (float)$plan[1]['amount'];
        
        return [
            'monto_inscripcion' => $inscripcion,
            'monto_cuota1'      => $cuota1,
            'total_minimo_usd'  => $inscripcion + $cuota1
        ];
    }

    /**
     * Obtiene una oferta específica por ID (Wizard y Detalles).
     * Restaurada la carga de professors_list con el nombre de tabla correcto.
     */
    public function getOfferingById(int $offeringId): array {
        $sql = "SELECT o.id as offering_id, d.name as diplomado_name, c.name as cohort_name, 
                       o.general_modality, o.total_cost, o.currency_code,
                       o.total_capacity, o.enrolled_count
                FROM tbl_academic_offerings o
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.id = ? AND o.is_active = 1 LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offeringId]);
        $offering = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ($offering) {
            // CÁLCULO DE CUPOS REALES: Conteo directo en tbl_enrollments
            $stC = $this->db->prepare("SELECT COUNT(id) FROM tbl_enrollments WHERE offering_id = ? AND status NOT IN ('RECHAZADA', 'CANCELADA')");
            $stC->execute([$offeringId]);
            $realEnrolled = (int)$stC->fetchColumn();

            $offering['available_seats'] = max(0, (int)$offering['total_capacity'] - $realEnrolled);
            $offering['enrolled_count'] = $realEnrolled;

            // Sedes
            $qS = "SELECT c.id, c.name FROM tbl_campuses c 
                   JOIN tbl_academic_offering_campuses oc ON c.id = oc.campus_id 
                   WHERE oc.offering_id = ?";
            $stS = $this->db->prepare($qS);
            $stS->execute([$offeringId]);
            $offering['sedes_list'] = $stS->fetchAll(PDO::FETCH_ASSOC);

            // Inyectar plan de pagos y el desglose inicial (Calculadora)
            $offering['payment_plans'] = $this->getOfferingPaymentPlan($offeringId);
            $offering['initial_payment'] = $this->getInitialPaymentDetails($offeringId);

            // Inyectar cuerpo docente (Fix nombre de tabla: tbl_professors)
            $stP = $this->db->prepare("
                SELECT p.first_name, p.last_name, p.photo_path 
                FROM tbl_professors p
                JOIN tbl_academic_offering_professors aop ON p.id = aop.professor_id
                WHERE aop.offering_id = ?
            ");
            $stP->execute([$offeringId]);
            $offering['professors_list'] = $stP->fetchAll(PDO::FETCH_ASSOC);
        }

        return $offering;
    }

/**
     * Obtiene todas las ofertas disponibles para el listado inicial.
     * Inyecta 'payment_plans' y 'professors_list' para el popup del index.
     */
    public function getAvailableOfferings(): array {
        // CIRUGÍA: Inyectamos grupos_nombres y grupos_descripciones en el SELECT principal
        $sql = "SELECT o.id as offering_id, d.name as diplomado_name, c.name as cohort_name, 
                       o.registration_start, o.registration_end, o.class_start, o.class_end,
                       o.general_modality, o.total_cost, o.currency_code, 
                       o.total_capacity, o.enrolled_count,
                       (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') 
                        FROM tbl_academic_offering_groups og 
                        INNER JOIN tbl_grupos g ON og.group_id = g.id 
                        WHERE og.offering_id = o.id) AS grupos_nombres,
                       (SELECT GROUP_CONCAT(g.description SEPARATOR ' | ') 
                        FROM tbl_academic_offering_groups og 
                        INNER JOIN tbl_grupos g ON og.group_id = g.id 
                        WHERE og.offering_id = o.id) AS grupos_descripciones
                FROM tbl_academic_offerings o
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.status = 'ABIERTA' AND o.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $offerings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($offerings as &$off) {
            $id = (int)$off['offering_id'];

            // CÁLCULO DE CUPOS REALES: Conteo directo en tbl_enrollments para el listado
            $stC = $this->db->prepare("SELECT COUNT(id) FROM tbl_enrollments WHERE offering_id = ? AND status NOT IN ('RECHAZADA', 'CANCELADA')");
            $stC->execute([$id]);
            $realEnrolled = (int)$stC->fetchColumn();

            $off['available_seats'] = max(0, (int)$off['total_capacity'] - $realEnrolled);
            $off['enrolled_count'] = $realEnrolled;

            // Sedes
            $stS = $this->db->prepare("SELECT c.name FROM tbl_campuses c 
                                       JOIN tbl_academic_offering_campuses oc ON c.id = oc.campus_id 
                                       WHERE oc.offering_id = ?"); 
            $stS->execute([$id]);
            $off['sedes_list'] = $stS->fetchAll(PDO::FETCH_COLUMN);

            // Grupos (Mantenemos esta por si acaso el JS o un popup la necesita como array)
            $stG = $this->db->prepare("SELECT g.name FROM tbl_grupos g 
                                       JOIN tbl_academic_offering_groups og ON g.id = og.group_id 
                                       WHERE og.offering_id = ?"); 
            $stG->execute([$id]);
            $off['grupos_list'] = $stG->fetchAll(PDO::FETCH_COLUMN);

            // Inyección del plan de pagos y desglose
            $off['payment_plans'] = $this->getOfferingPaymentPlan($id);
            $off['initial_payment'] = $this->getInitialPaymentDetails($id);

            // Inyección del cuerpo docente para el listado
            $stP = $this->db->prepare("
                SELECT p.first_name, p.last_name, p.photo_path 
                FROM tbl_professors p
                JOIN tbl_academic_offering_professors aop ON p.id = aop.professor_id
                WHERE aop.offering_id = ?
            ");
            $stP->execute([$id]);
            $off['professors_list'] = $stP->fetchAll(PDO::FETCH_ASSOC);
        }
        return $offerings;
    }


    /**
     * Historial de inscripciones del estudiante.
     */
    public function getStudentEnrollmentsStatus(int $studentId): array {
        $sql = "SELECT e.offering_id, d.name as diplomado_name, c.name as cohort_name, e.status, e.created_at
                FROM tbl_enrollments e
                JOIN tbl_academic_offerings o ON e.offering_id = o.id
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE e.user_id = ?
                ORDER BY e.created_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Error en getStudentEnrollmentsStatus: " . $e->getMessage());
            return [];
        }
    }
}