<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / AUDITORÍA BANCARIA
 * ARCHIVO: app/models/ManagerialBankReconciliationModel.php
 * PROPÓSITO: Modelo maestro de AUDITORÍA para contrastar TPago vs Inscripciones y Cuotas con lógica de 8 dígitos.
 * VERSIÓN: 1.5.0 - Implementación de Matriz Maestra Unificada y filtros dinámicos de Etapa/Estatus.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class ManagerialBankReconciliationModel 
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * MATRIZ DE AUDITORÍA MAESTRA
     * Cruza el banco (tbl_financial_bank_transactions_mobile) con las dos etapas del sistema.
     */
    public function getReconciliationMatrix(array $f): array {
        $params = [];
        
        // 1. Construcción de filtros de fecha
        $dateFilter = "";
        if (!empty($f['date_from']) && !empty($f['date_to'])) {
            $dateFilter = " AND btm.op_date BETWEEN ? AND ? ";
            $params[] = $f['date_from'];
            $params[] = $f['date_to'];
        }

        // 2. Query Principal: El Banco es el Eje Central
        $sql = "SELECT 
                    btm.op_date AS fecha_banco,
                    btm.reference_id AS referencia_banco,
                    btm.amount AS monto_banco,
                    btm.origin_phone AS telefono_emisor,
                    
                    -- IDENTIFICACIÓN DEL ESTUDIANTE (Busca en ambas etapas)
                    CASE 
                        WHEN ep.id IS NOT NULL THEN CONCAT(u_ins.first_name, ' ', u_ins.last_name)
                        WHEN fp.id IS NOT NULL THEN CONCAT(u_stu.first_name, ' ', u_stu.last_name)
                        ELSE '--- SIN REGISTRO ---'
                    END AS nombre_estudiante,

                    -- ETAPA FINANCIERA
                    CASE 
                        WHEN ep.id IS NOT NULL THEN 'INSCRIPCIÓN'
                        WHEN fp.id IS NOT NULL THEN 'CUOTA / ADICIONAL'
                        ELSE 'HUÉRFANO'
                    END AS etapa_financiera,

                    -- ESTATUS DE VALIDACIÓN EN EL SISTEMA (Raw para el Controller)
                    CASE 
                        WHEN ep.id IS NOT NULL THEN ep.status
                        WHEN fp.id IS NOT NULL THEN fp.status
                        ELSE 'N/A'
                    END AS status_raw,

                    -- ESTATUS FORMATEADO PARA EL JS
                    CASE 
                        WHEN ep.id IS NOT NULL THEN 
                            CASE 
                                WHEN ep.status = 'APPROVED' THEN '✅ YA APROBADO'
                                WHEN ep.status = 'PENDING' THEN '⏳ PENDIENTE POR VALIDAR'
                                WHEN ep.status = 'REJECTED' THEN '❌ RECHAZADO'
                                ELSE ep.status
                            END
                        WHEN fp.id IS NOT NULL THEN 
                            CASE 
                                WHEN fp.status = 'APPROVED' THEN '✅ YA APROBADO'
                                WHEN fp.status = 'PENDING' THEN '⏳ PENDIENTE POR VALIDAR'
                                WHEN fp.status = 'REJECTED' THEN '❌ RECHAZADO'
                                ELSE fp.status
                            END
                        ELSE 'N/A'
                    END AS estatus_sistema,

                    -- ESTADO DEL CRUCE BANCARIO
                    CASE 
                        WHEN ep.id IS NOT NULL OR fp.id IS NOT NULL THEN '✅ CONCILIADO'
                        ELSE '⚠️ NO ENCONTRADO EN SISTEMA'
                    END AS estatus_conciliacion

                FROM tbl_financial_bank_transactions_mobile btm

                -- CRUCE CON INSCRIPCIONES (Etapa 1)
                LEFT JOIN tbl_enrollments_payments ep ON (
                    ep.amount = btm.amount 
                    AND RIGHT(ep.reference_id, 8) = RIGHT(btm.reference_id, 8)
                )
                LEFT JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                LEFT JOIN tbl_users u_ins ON e.user_id = u_ins.id

                -- CRUCE CON CUOTAS / ADICIONALES (Etapa 2)
                LEFT JOIN tbl_financial_payments fp ON (
                    fp.amount = btm.amount 
                    AND RIGHT(fp.reference_id, 8) = RIGHT(btm.reference_id, 8)
                )
                LEFT JOIN tbl_students s ON fp.student_id = s.id
                LEFT JOIN tbl_users u_stu ON s.user_id = u_stu.id

                WHERE btm.op_type = 'NC' 
                $dateFilter";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 3. Aplicación de filtros dinámicos en PHP (Filtros de lógica de negocio)
            if (!empty($f['etapa_financiera']) && $f['etapa_financiera'] !== 'ALL') {
                $data = array_filter($data, fn($row) => $row['etapa_financiera'] === $f['etapa_financiera']);
            }

            if (!empty($f['status_sistema']) && $f['status_sistema'] !== 'ALL') {
                $data = array_filter($data, fn($row) => $row['status_raw'] === $f['status_sistema']);
            }

            if (!empty($f['status_conciliacion']) && $f['status_conciliacion'] !== 'ALL') {
                $target = ($f['status_conciliacion'] === 'CONCILIADO') ? '✅ CONCILIADO' : '⚠️ NO ENCONTRADO EN SISTEMA';
                $data = array_filter($data, fn($row) => $row['estatus_conciliacion'] === $target);
            }

            // 4. Búsqueda textual (Referencia, Nombre, Teléfono o Monto)
            if (!empty($f['search'])) {
                $s = strtolower($f['search']);
                $data = array_filter($data, function($row) use ($s) {
                    return strpos(strtolower((string)$row['referencia_banco']), $s) !== false || 
                           strpos(strtolower((string)$row['nombre_estudiante']), $s) !== false ||
                           strpos(strtolower((string)$row['telefono_emisor']), $s) !== false ||
                           strpos(strtolower((string)$row['monto_banco']), $s) !== false;
                });
            }

            return array_values($data);

        } catch (\PDOException $e) {
            throw new Exception("Error en la ejecución de la auditoría: " . $e->getMessage());
        }
    }
}