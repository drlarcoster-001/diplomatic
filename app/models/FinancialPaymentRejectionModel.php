<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / RECHAZOS DE PAGO
 * ARCHIVO: app/Models/FinancialPaymentRejectionModel.php
 * PROPÓSITO: Persistencia de datos para eliminación de registros y reactivación de estatus de pagos rechazados.
 * VERSIÓN: 1.2.0 - Fix: Eliminación de la resta de cupos (enrolled_count) ya que un pago rechazado no consolida matrícula.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialPaymentRejectionModel
{
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /* =======================================================
       PESTAÑA 1: INSCRIPCIONES RECHAZADAS
       ======================================================= */
    public function searchInscripciones(string $search): array {
        try {
            $term = "%" . trim($search) . "%";
            $sql = "SELECT 
                        ep.id AS payment_id, e.id AS enrollment_id,
                        DATE_FORMAT(ep.created_at, '%d/%m/%Y %h:%i %p') AS fecha_pago,
                        u.document_id AS cedula, CONCAT(u.first_name, ' ', u.last_name) AS participante,
                        d.name AS diplomado,
                        ep.amount AS monto, ep.currency AS moneda, ep.method AS metodo_pago,
                        ep.payment_metadata
                    FROM tbl_enrollments_payments ep
                    INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                    INNER JOIN tbl_users u ON e.user_id = u.id
                    INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    WHERE ep.status = 'REJECTED' AND e.status = 'RECHAZADO'
                      AND (u.document_id LIKE :s1 OR u.first_name LIKE :s2 OR u.last_name LIKE :s3 OR :s4 = '')
                    ORDER BY ep.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':s1' => $term, ':s2' => $term, ':s3' => $term, ':s4' => trim($search)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Error SQL Inscripciones: " . $e->getMessage());
        }
    }

    public function incorporarInscripcion(int $paymentId, int $enrollmentId): bool {
        try {
            $this->db->beginTransaction();

            $st0 = $this->db->prepare("SELECT method FROM tbl_enrollments_payments WHERE id = ? LIMIT 1");
            $st0->execute([$paymentId]);
            $method = $st0->fetchColumn();
            
            $newStatus = ($method === 'CASH') ? 'COMPROMISO' : 'REVISION';

            $this->db->prepare("UPDATE tbl_enrollments SET status = ?, updated_at = NOW() WHERE id = ?")
                     ->execute([$newStatus, $enrollmentId]);
            $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'PENDING' WHERE id = ?")
                     ->execute([$paymentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function eliminarInscripcion(int $paymentId, int $enrollmentId): bool {
        try {
            $this->db->beginTransaction();

            // 1. Obtener el offering_id antes de borrar nada
            $stmt = $this->db->prepare("SELECT offering_id FROM tbl_enrollments WHERE id = ? LIMIT 1");
            $stmt->execute([$enrollmentId]);
            $offeringId = $stmt->fetchColumn();

            // 2. Si existe la oferta, restamos 1 al contador de inscritos
            if ($offeringId) {
                // Usamos 'enrolled_count' que es la columna real en tu SQL
                $sqlSlot = "UPDATE tbl_academic_offerings 
                            SET enrolled_count = enrolled_count - 1 
                            WHERE id = ? AND enrolled_count > 0";
                $this->db->prepare($sqlSlot)->execute([$offeringId]);
            }

            // 3. Eliminación física de los registros
            // Primero eliminamos el pago
            $this->db->prepare("DELETE FROM tbl_enrollments_payments WHERE id = ?")->execute([$paymentId]);
            // Luego eliminamos la inscripción
            $this->db->prepare("DELETE FROM tbl_enrollments WHERE id = ?")->execute([$enrollmentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception("Error SQL al actualizar contador: " . $e->getMessage());
        }
    }

    /* =======================================================
       PESTAÑA 2: PAGOS REGULARES RECHAZADOS
       ======================================================= */
    public function searchRegulares(string $search): array {
        try {
            $term = "%" . trim($search) . "%";
            $sql = "SELECT 
                        fp.id AS payment_id,
                        DATE_FORMAT(fp.created_at, '%d/%m/%Y %h:%i %p') AS fecha_pago,
                        s.student_code AS expediente,
                        CONCAT(u.first_name, ' ', u.last_name) AS participante,
                        d.name AS diplomado,
                        fp.amount AS monto, fp.currency AS moneda, fp.method AS metodo_pago,
                        fp.payment_metadata
                    FROM tbl_financial_payments fp
                    INNER JOIN tbl_students s ON fp.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    INNER JOIN tbl_student_matriculations sm ON fp.matriculation_id = sm.id
                    INNER JOIN tbl_academic_offerings o ON sm.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    WHERE fp.status = 'REJECTED'
                      AND (s.student_code LIKE :s1 OR u.first_name LIKE :s2 OR u.last_name LIKE :s3 OR :s4 = '')
                    ORDER BY fp.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':s1' => $term, ':s2' => $term, ':s3' => $term, ':s4' => trim($search)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Error SQL Regulares: " . $e->getMessage());
        }
    }

public function incorporarRegular(int $paymentId): bool {
    try {
        // Al reincorporar, limpiamos el estatus, la observación y el validador anterior
        $sql = "UPDATE tbl_financial_payments 
                SET status = 'PENDING', 
                    observation = NULL, 
                    collector_id = NULL 
                WHERE id = ?";
        
        return $this->db->prepare($sql)->execute([$paymentId]);
    } catch (Exception $e) {
        // Es buena práctica loguear el error para saber por qué falló el SQL
        error_log("Error al incorporar pago regular: " . $e->getMessage());
        return false;
    }
}

    public function eliminarRegular(int $paymentId): bool {
        try {
            $this->db->prepare("DELETE FROM tbl_financial_payments WHERE id = ?")
                     ->execute([$paymentId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error SQL al eliminar regular: " . $e->getMessage());
        }
    }
}