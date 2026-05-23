<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / DOCUMENTOS RECHAZADOS
 * ARCHIVO: app/models/AdministrativeRejectedModel.php
 * PROPÓSITO: Persistencia de datos y lógica de negocio para retorno de expedientes a flujo regular.
 * VERSIÓN: 1.3.1 - Sincronización con tbl_enrollments_payments y disparidad de tipos INT.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AdministrativeRejectedModel 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    public function getRejectedList(): array 
    {
        try {
            $sql = "SELECT 
                        e.id AS enrollment_id,
                        u.first_name, u.last_name, u.document_id AS cedula,
                        d.name AS diplomado_name,
                        e.status, e.observations, e.updated_at AS fecha_accion,
                        COALESCE(p.method, 'S/M') AS payment_method
                    FROM tbl_enrollments e
                    INNER JOIN tbl_users u ON e.user_id = u.id
                    INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    LEFT JOIN tbl_enrollments_payments p ON e.id = p.enrollment_id
                    WHERE e.status = 'RECHAZADO'
                    ORDER BY e.updated_at DESC";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function revertStatus(int $enrollmentId): bool 
    {
        try {
            // Obtener el método de pago para decidir el estatus destino
            $stmt = $this->db->prepare("SELECT method FROM tbl_enrollments_payments WHERE enrollment_id = ? LIMIT 1");
            $stmt->execute([$enrollmentId]);
            $method = strtoupper($stmt->fetchColumn() ?: '');

            // Lógica institucional de retorno
            $newStatus = (in_array($method, ['PAGOMOVIL', 'BINANCE', 'ZELLE'])) ? 'REVISION' : 'COMPROMISO';

            $update = $this->db->prepare("UPDATE tbl_enrollments SET status = ?, observations = NULL, updated_at = NOW() WHERE id = ?");
            return $update->execute([$newStatus, $enrollmentId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}