<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA BANCARIOS
 * ARCHIVO: app/models/FinancialBankStatementsModel.php
 * PROPÓSITO: Modelo para la gestión de transacciones bancarias.
 *            Maneja dos tablas: tbl_financial_bank_transactions_mobile (T-Pago)
 *            y tbl_financial_bank_transactions_account (Movimientos Mercantil).
 * VERSIÓN: 1.0.0 - Creación inicial con soporte dual de tablas bancarias.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialBankStatementsModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================
    // T-PAGO: tbl_financial_bank_transactions_mobile
    // =========================================================

    /**
     * Total de transacciones T-Pago con filtros
     */
    public function getTotalTpagoTransactions(array $filters = []): int
    {
        [$where, $params] = $this->buildTpagoWhere($filters);
        $sql = "SELECT COUNT(*) FROM tbl_financial_bank_transactions_mobile $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene transacciones T-Pago paginadas y filtradas
     */
    public function getTpagoTransactions(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = $this->buildTpagoWhere($filters);

        $sql = "SELECT 
                    id,
                    op_type,
                    DATE_FORMAT(op_date, '%d/%m/%Y') as op_date,
                    reference_id,
                    origin_phone,
                    origin_bank,
                    amount,
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at
                FROM tbl_financial_bank_transactions_mobile
                $where
                ORDER BY op_date DESC, id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $i => $val) {
            $stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un lote de transacciones T-Pago
     * Ignora duplicados por clave única (op_type, op_date, reference_id, phone_source, bank_source, amount)
     */
    public function saveTpagoBatch(array $data, int $userId): int
    {
        $inserted = 0;

        $sql = "INSERT IGNORE INTO tbl_financial_bank_transactions_mobile 
                    (op_type, op_date, reference_id, origin_phone, origin_bank, amount, admin_id)
                VALUES 
                    (:op_type, :op_date, :reference_id, :origin_phone, :origin_bank, :amount, :admin_id)";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $row) {
            $stmt->execute([
                ':op_type'      => $row['op_type']      ?? 'NC',
                ':op_date'      => $row['date_tran']     ?? null,
                ':reference_id' => $row['reference']     ?? '',
                ':origin_phone' => $row['phone_source']  ?? '',
                ':origin_bank'  => $row['bank_source']   ?? '',
                ':amount'       => $row['amount_bs']     ?? 0,
                ':admin_id'     => $userId
            ]);
            $inserted += $stmt->rowCount();
        }

        return $inserted;
    }

    /**
     * Construye el WHERE para T-Pago
     */
    private function buildTpagoWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['date'])) {
            $where[]  = "op_date = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['reference'])) {
            $where[]  = "reference_id LIKE ?";
            $params[] = '%' . $filters['reference'] . '%';
        }

        if (!empty($filters['amount'])) {
            $where[]  = "amount = ?";
            $params[] = $filters['amount'];
        }

        if (!empty($filters['phone'])) {
            $where[]  = "origin_phone LIKE ?";
            $params[] = '%' . $filters['phone'] . '%';
        }

        if (!empty($filters['text'])) {
            $where[]  = "(reference_id LIKE ? OR origin_phone LIKE ? OR origin_bank LIKE ?)";
            $search   = '%' . $filters['text'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereStr, $params];
    }

    // =========================================================
    // MOVIMIENTOS MERCANTIL: tbl_financial_bank_transactions_account
    // =========================================================

    /**
     * Total de movimientos Mercantil con filtros
     */
    public function getTotalMovimientosTransactions(array $filters = []): int
    {
        [$where, $params] = $this->buildMovimientosWhere($filters);
        $sql = "SELECT COUNT(*) FROM tbl_financial_bank_transactions_account $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene movimientos Mercantil paginados y filtrados
     */
    public function getMovimientosTransactions(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = $this->buildMovimientosWhere($filters);

        $sql = "SELECT 
                    id,
                    op_type,
                    DATE_FORMAT(op_date, '%d/%m/%Y') as op_date,
                    reference_id,
                    description,
                    amount,
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at
                FROM tbl_financial_bank_transactions_account
                $where
                ORDER BY op_date DESC, id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $i => $val) {
            $stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un lote de movimientos Mercantil
     * Ignora duplicados por clave única (op_type, op_date, reference_id, amount)
     */
    public function saveMovimientosBatch(array $data, int $userId): int
    {
        $inserted = 0;

        $sql = "INSERT IGNORE INTO tbl_financial_bank_transactions_account 
                    (op_type, op_date, reference_id, description, amount, uploaded_by)
                VALUES 
                    (:op_type, :op_date, :reference_id, :description, :amount, :uploaded_by)";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $row) {
            $stmt->execute([
                ':op_type'      => $row['op_type']     ?? 'NC',
                ':op_date'      => $row['date_tran']    ?? null,
                ':reference_id' => $row['reference']    ?? '',
                ':description'  => $row['description']  ?? '',
                ':amount'       => $row['amount_bs']    ?? 0,
                ':uploaded_by'  => $userId
            ]);
            $inserted += $stmt->rowCount();
        }

        return $inserted;
    }

    /**
     * Construye el WHERE para Movimientos Mercantil
     */
    private function buildMovimientosWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['date'])) {
            $where[]  = "op_date = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['reference'])) {
            $where[]  = "reference_id LIKE ?";
            $params[] = '%' . $filters['reference'] . '%';
        }

        if (!empty($filters['amount'])) {
            $where[]  = "amount = ?";
            $params[] = $filters['amount'];
        }

        if (!empty($filters['text'])) {
            $where[]  = "(reference_id LIKE ? OR description LIKE ?)";
            $search   = '%' . $filters['text'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereStr, $params];
    }
}