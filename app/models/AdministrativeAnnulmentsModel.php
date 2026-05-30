<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/models/AdministrativeAnnulmentsModel.php
 * PROPÓSITO: Gestión transaccional (ACID) para la anulación de inscripciones, eliminación de registros académicos y reversión de estatus.
 * VERSIÓN: 1.1.2 - Fix: Búsqueda corregida, soporte para popup de detalles y lógica de reverso según método de pago.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AdministrativeAnnulmentsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    


    /**
     * Obtiene el detalle extendido para el popup de confirmación.
     */
    public function getEnrollmentDetail(int $enrollmentId): ?array
    {
        try {
            $sql = "SELECT 
                        e.id as enrollment_id,
                        u.document_id,
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.email,
                        d.name as diplomado,
                        c.name as cohorte,
                        ep.method as payment_method,
                        s.id as student_id,
                        s.status as student_status,
                        sm.academic_status
                    FROM tbl_enrollments e
                    INNER JOIN tbl_users u ON e.user_id = u.id
                    INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    LEFT JOIN tbl_enrollments_payments ep ON e.id = ep.enrollment_id
                    LEFT JOIN tbl_students s ON e.id = s.enrollment_id
                    LEFT JOIN tbl_student_matriculations sm ON s.id = sm.student_id
                    WHERE e.id = :eid LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':eid' => $enrollmentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log("Error en getEnrollmentDetail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene la lista de inscripciones APROBADAS para alimentar la grid inicial.
     * FIX: Se añadió e.status para corregir el badge 'null'
     */

    public function getApprovedEnrollments(
    string $student   = '',
    string $diplomado = '',
    string $dateFrom  = '',
    string $dateTo    = '',
    int    $page      = 1,
    int    $perPage   = 25
): array {
    try {
        $where  = ["e.status = 'APROBADO'"];
        $params = [];

        if ($student !== '') {
    $s = "%{$student}%";
    $where[] = "(u.document_id LIKE '{$s}' OR u.first_name LIKE '{$s}' OR u.last_name LIKE '{$s}')";
}

        if ($diplomado !== '') {
    $where[]              = "d.name = :diplomado";
    $params[':diplomado'] = $diplomado;
}
        if ($dateFrom !== '') {
            $where[]             = "DATE(e.created_at) >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[]           = "DATE(e.created_at) <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $baseSQL = "FROM tbl_enrollments e
                    INNER JOIN tbl_users u ON e.user_id = u.id
                    INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    {$whereSQL}";

        $stmtCount = $this->db->prepare("SELECT COUNT(*) {$baseSQL}");
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT e.id as enrollment_id, u.document_id, u.first_name, u.last_name,
                       d.name as diplomado, c.name as cohorte, e.status, e.created_at
                {$baseSQL}
                ORDER BY e.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
        ];
    } catch (Throwable $e) {
        error_log("Error en getApprovedEnrollments: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    }
}
    /**
     * Ejecuta la lógica de cancelación condicional y limpieza de registros.
     * FIX: Ahora permite anular aunque no exista el registro en tbl_students
     */
    public function cancelIncription(int $enrollmentId): array
    {
        try {
            // 1. Obtener datos necesarios para validación
            $data = $this->getEnrollmentDetail($enrollmentId);

            if (!$data) {
                return ['success' => false, 'message' => 'No se encontraron datos de la inscripción.'];
            }

            $hasStudent = !empty($data['student_id']);

            // 2. Validaciones solo si existe un registro de estudiante
            if ($hasStudent) {
                // Validar estatus académico
                if ($data['student_status'] !== 'ACTIVO' || $data['academic_status'] !== 'CURSANDO') {
                    return ['success' => false, 'message' => 'Solo se pueden anular inscripciones de estudiantes con estatus ACTIVO y CURSANDO.'];
                }

                // Verificar pagos de cuotas
                $sqlPayments = "SELECT COUNT(*) FROM tbl_financial_payments WHERE student_id = :sid";
                $stmt = $this->db->prepare($sqlPayments);
                $stmt->execute([':sid' => $data['student_id']]);
                
                if ((int)$stmt->fetchColumn() > 0) {
                    return [
                        'success' => false, 
                        'message' => 'Este estudiante ya tiene registros de pago de cuotas. Debe comunicarse con el departamento de diplomados.'
                    ];
                }
            }

            // 3. Determinar nuevo estatus basado en el método de pago original
            $method = strtoupper($data['payment_method'] ?? '');
            $newStatus = (in_array($method, ['PAGOMOVIL', 'BINANCE', 'ZELLE'])) ? 'REVISION' : 'COMPROMISO';

            // --- INICIO DE TRANSACCIÓN ATÓMICA ---
            $this->db->beginTransaction();

            if ($hasStudent) {
                // A. Eliminar Matrícula
                $stmt = $this->db->prepare("DELETE FROM tbl_student_matriculations WHERE student_id = ?");
                $stmt->execute([$data['student_id']]);

                // B. Eliminar registro de Estudiante
                $stmt = $this->db->prepare("DELETE FROM tbl_students WHERE id = ?");
                $stmt->execute([$data['student_id']]);
            }

            // C. Revertir estatus de la Inscripción (ESTO ES LO QUE EL USUARIO SIEMPRE QUIERE)
            $stmt = $this->db->prepare("UPDATE tbl_enrollments SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $enrollmentId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'La inscripción ha sido revertida a ' . $newStatus . ' exitosamente.'];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error crítico en cancelIncription: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al procesar la anulación: ' . $e->getMessage()];
        }
    }

public function getDiplomados(): array {
    $stmt = $this->db->query(
        "SELECT DISTINCT d.id, d.name 
         FROM tbl_diplomados d
         INNER JOIN tbl_academic_offerings ao ON ao.diploma_id = d.id
         INNER JOIN tbl_enrollments e ON e.offering_id = ao.id
         WHERE e.status = 'APROBADO'
         ORDER BY d.name"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}