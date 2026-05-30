<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REVERSO DE OPERACIONES
 * ARCHIVO: app/models/FinancialReverseOperationsModel.php
 * PROPÓSITO: Modelo integral de reversos con blindajes de seguridad.
 * VERSIÓN: 6.2.0 - FIX FINAL: Extracción directa de 'monto_sistema_usd' desde JSON para precisión contable.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialReverseOperationsModel
{
    private PDO $db;

    public function __construct() 
    { 
        $this->db = (new Database())->getConnection(); 
    }

    public function searchInscripciones(string $search): array
    {
        try {
            $term = trim($search);
            $likeTerm = "%{$term}%";

            $sql = "SELECT 
                        ep.id AS payment_id,
                        ep.enrollment_id,
                        DATE_FORMAT(ep.created_at, '%d/%m/%Y %h:%i %p') AS fecha_pago,
                        u.document_id AS cedula,
                        CONCAT(u.first_name, ' ', u.last_name) AS participante,
                        d.name AS diplomado,
                        ep.amount AS monto,
                        ep.currency AS moneda,
                        ep.method AS metodo_pago
                    FROM tbl_enrollments_payments ep
                    INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                    INNER JOIN tbl_users u ON e.user_id = u.id 
                    INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    LEFT JOIN tbl_students s ON e.id = s.enrollment_id
                    WHERE ep.status = 'APPROVED'
                      AND s.id IS NULL
                      AND (:search1 = '' OR u.document_id LIKE :s1 OR u.first_name LIKE :s2 OR u.last_name LIKE :s3)
                    ORDER BY ep.created_at DESC LIMIT 100";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':search1'=>$term, ':s1'=>$likeTerm, ':s2'=>$likeTerm, ':s3'=>$likeTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al buscar inscripciones.");
        }
    }

    public function searchCuotas(string $search): array
    {
        try {
            $term = trim($search);
            $likeTerm = "%{$term}%";

            $sql = "SELECT 
                        fp.id AS payment_id,
                        DATE_FORMAT(fp.created_at, '%d/%m/%Y %h:%i %p') AS fecha_pago,
                        u.document_id AS cedula,
                        CONCAT(u.first_name, ' ', u.last_name) AS participante,
                        fp.amount AS monto,
                        fp.currency AS moneda,
                        fp.method AS metodo_pago
                    FROM tbl_financial_payments fp
                    INNER JOIN tbl_students s ON fp.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    WHERE fp.status = 'APPROVED'
                      AND (:search1 = '' OR u.document_id LIKE :s1 OR u.first_name LIKE :s2 OR u.last_name LIKE :s3)
                    ORDER BY fp.created_at DESC LIMIT 100";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':search1'=>$term, ':s1'=>$likeTerm, ':s2'=>$likeTerm, ':s3'=>$likeTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al buscar cuotas.");
        }
    }

    public function executeReverseInscripcion(int $paymentId, int $enrollmentId): bool
    {
        try {
            $this->db->beginTransaction();

            $stmtCheck = $this->db->prepare("SELECT id FROM tbl_students WHERE enrollment_id = ? LIMIT 1");
            $stmtCheck->execute([$enrollmentId]);
            if ($stmtCheck->fetch()) throw new Exception("El participante ya posee expediente activo.");

            $st0 = $this->db->prepare("SELECT method FROM tbl_enrollments_payments WHERE id = ? LIMIT 1");
            $st0->execute([$paymentId]);
            $method = $st0->fetchColumn();
            $newStatus = ($method === 'CASH') ? 'COMPROMISO' : 'REVISION';

            $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'PENDING' WHERE id = ?")->execute([$paymentId]);
            $this->db->prepare("DELETE FROM tbl_financial_student_ledger WHERE enrollment_id = ?")->execute([$enrollmentId]);
            $this->db->prepare("UPDATE tbl_enrollments SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $enrollmentId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * ACCIÓN 2: REVERSO DE CASCADA MATEMÁTICA EN DÓLARES
     */
    public function executeReverseCuota(int $paymentId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos, INCLUYENDO LA MONEDA Y METADATA
            $stmtPay = $this->db->prepare("
                SELECT fp.amount, fp.currency, fp.payment_metadata, s.user_id, sm.enrollment_id 
                FROM tbl_financial_payments fp
                INNER JOIN tbl_students s ON fp.student_id = s.id
                INNER JOIN tbl_student_matriculations sm ON fp.matriculation_id = sm.id
                WHERE fp.id = ? AND fp.status = 'APPROVED' LIMIT 1
            ");
            $stmtPay->execute([$paymentId]);
            $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

            if (!$payment) throw new Exception("Pago no encontrado o ya fue reversado.");

            // 2. LÓGICA DE CONVERSIÓN MONETARIA A DÓLARES (Extracción directa de JSON)
            $montoFisico = (float)$payment['amount'];
            $moneda = strtoupper(trim((string)$payment['currency']));
            $metadataStr = (string)$payment['payment_metadata'];
            
            $montoAReversar = $montoFisico; // Asumimos USD inicialmente

            // Si es Bolívares, leemos el JSON
            if (in_array($moneda, ['BS', 'VES', 'BS.', 'BOLIVARES'])) {
                $metadata = json_decode($metadataStr, true) ?? [];
                
                // OPCIÓN A (LA MEJOR): El JSON ya tiene los dólares exactos calculados
                if (isset($metadata['monto_sistema_usd']) && (float)$metadata['monto_sistema_usd'] > 0) {
                    $montoAReversar = (float)$metadata['monto_sistema_usd'];
                } 
                // OPCIÓN B: Fallback por si es un recibo viejo sin 'monto_sistema_usd'
                else {
                    $tasaCambio = isset($metadata['tasa_cambio']) ? (float)$metadata['tasa_cambio'] : 0.0;
                    
                    if ($tasaCambio <= 0) {
                        throw new Exception("Error Crítico: El pago está en Bolívares pero no se encontró 'monto_sistema_usd' ni 'tasa_cambio' en los metadatos.");
                    }
                    $montoAReversar = round($montoFisico / $tasaCambio, 2);
                }
            }

            $userId = (int)$payment['user_id'];
            $enrollmentId = (int)$payment['enrollment_id'];

            // 3. Regresar el recibo a estado PENDING
            $this->db->prepare("UPDATE tbl_financial_payments SET status = 'PENDING' WHERE id = ?")->execute([$paymentId]);

            // 4. BUSCAR LAS CUOTAS CON DINERO
            $stmtLedger = $this->db->prepare("
                SELECT id, amount_paid 
                FROM tbl_financial_student_ledger 
                WHERE user_id = ? AND enrollment_id = ? AND amount_paid > 0
                ORDER BY id DESC
            ");
            $stmtLedger->execute([$userId, $enrollmentId]);
            $cuotas = $stmtLedger->fetchAll(PDO::FETCH_ASSOC);

            // 5. MOTOR DE RESTA EN CASCADA (Monto ya en USD)
            foreach ($cuotas as $cuota) {
                if ($montoAReversar <= 0.001) break; 

                $idFila = (int)$cuota['id'];
                $pagadoEnFila = (float)$cuota['amount_paid'];

                if ($montoAReversar >= $pagadoEnFila) {
                    $montoAReversar -= $pagadoEnFila;
                    $nuevoSaldoPagado = 0.00;
                    $nuevoStatus = 'PENDIENTE';
                } else {
                    $nuevoSaldoPagado = $pagadoEnFila - $montoAReversar;
                    $montoAReversar = 0.00; 
                    $nuevoStatus = 'ABONADO';
                }

                $this->db->prepare("
                    UPDATE tbl_financial_student_ledger 
                    SET amount_paid = ?, status = ?, payment_id = NULL 
                    WHERE id = ?
                ")->execute([$nuevoSaldoPagado, $nuevoStatus, $idFila]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception("Error en cascada inversa: " . $e->getMessage());
        }
    }

    public function searchEstudiantesCuotas(string $search, int $limit, int $offset): array
{
    $t = "%{$search}%";
    $sql = "SELECT DISTINCT
                u.id as user_id,
                u.document_id as cedula,
                CONCAT(u.first_name, ' ', u.last_name) AS participante,
                COUNT(fp.id) as total_pagos
            FROM tbl_financial_payments fp
            INNER JOIN tbl_students s ON fp.student_id = s.id
            INNER JOIN tbl_users u ON s.user_id = u.id
            WHERE fp.status = 'APPROVED'
            AND (u.document_id LIKE '{$t}' OR u.first_name LIKE '{$t}' OR u.last_name LIKE '{$t}')
            GROUP BY u.id, u.document_id, u.first_name, u.last_name
            ORDER BY u.last_name ASC
            LIMIT {$limit} OFFSET {$offset}";
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function countEstudiantesCuotas(string $search): int
{
    $t = "%{$search}%";
    $sql = "SELECT COUNT(DISTINCT u.id)
            FROM tbl_financial_payments fp
            INNER JOIN tbl_students s ON fp.student_id = s.id
            INNER JOIN tbl_users u ON s.user_id = u.id
            WHERE fp.status = 'APPROVED'
            AND (u.document_id LIKE '{$t}' OR u.first_name LIKE '{$t}' OR u.last_name LIKE '{$t}')";
    $stmt = $this->db->query($sql);
    return (int) $stmt->fetchColumn();
}

public function getCuotasByUserId(int $userId): array
{
    $sql = "SELECT 
                fp.id AS payment_id,
                DATE_FORMAT(fp.created_at, '%d/%m/%Y %h:%i %p') AS fecha_pago,
                u.document_id AS cedula,
                CONCAT(u.first_name, ' ', u.last_name) AS participante,
                d.name AS diplomado,
                fp.amount AS monto,
                fp.currency AS moneda,
                fp.method AS metodo_pago
            FROM tbl_financial_payments fp
            INNER JOIN tbl_students s ON fp.student_id = s.id
            INNER JOIN tbl_users u ON s.user_id = u.id
            INNER JOIN tbl_student_matriculations sm ON fp.matriculation_id = sm.id
            INNER JOIN tbl_academic_offerings ao ON sm.offering_id = ao.id
            INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
            WHERE fp.status = 'APPROVED'
            AND u.id = {$userId}
            ORDER BY fp.created_at DESC";
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}