<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / TASAS DE CAMBIO
 * ARCHIVO: app/models/FinancialExchangeRatesModel.php
 * PROPÓSITO: Abstracción de datos para la tabla tbl_financial_exchange_rates.
 * VERSIÓN: 1.1.0 - Implementación de borrado físico y consulta por ID para auditoría.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FinancialExchangeRatesModel
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getLastRate() {
        // Ordenamos por fecha descendente para capturar la realidad del mercado hoy
        $sql = "SELECT dolar_bcv, euro_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE status = 'ACTIVE' 
                ORDER BY rate_date DESC, id DESC LIMIT 1";
        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result : [
            'dolar_bcv' => 0, 
            'euro_bcv' => 0, 
            'rate_date' => date('Y-m-d')
        ];
    }
    

    /**
     * Obtiene un registro específico por su ID.
     * Útil para recuperar datos antes de eliminar y registrarlos en la auditoría.
     */
    public function getById(int $id) {
        $sql = "SELECT id, rate_date, dolar_bcv, euro_bcv, created_at 
                FROM tbl_financial_exchange_rates 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el listado histórico con soporte de filtros por fecha.
     */
    public function getHistory(?string $desde = null, ?string $hasta = null): array {
        $sql = "SELECT id, rate_date, dolar_bcv, euro_bcv, created_at 
                FROM tbl_financial_exchange_rates 
                WHERE status = 'ACTIVE'";
        
        if ($desde && $hasta) {
            $sql .= " AND rate_date BETWEEN :desde AND :hasta";
        }
        
        $sql .= " ORDER BY id DESC LIMIT 100";
        $stmt = $this->db->prepare($sql);
        
        if ($desde && $hasta) {
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un nuevo registro de tasa.
     */
    public function save(array $data): bool {
        $sql = "INSERT INTO tbl_financial_exchange_rates (rate_date, dolar_bcv, euro_bcv, user_id, status) 
                VALUES (:rate_date, :dolar, :euro, :user_id, 'ACTIVE')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':rate_date' => date('Y-m-d'),
            ':dolar'     => $data['dolar'],
            ':euro'      => $data['euro'],
            ':user_id'   => $data['user_id']
        ]);
    }

    /**
     * Elimina físicamente un registro de la base de datos por su ID.
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM tbl_financial_exchange_rates WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtiene la tasa más cercana (igual o anterior) a la fecha proporcionada.
     * Permite que fines de semana o feriados usen la última tasa oficial registrada.
     */
    public function getRateByDate(string $date) {
        $sql = "SELECT dolar_bcv, euro_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE rate_date <= :rate_date 
                AND status = 'ACTIVE' 
                ORDER BY rate_date DESC, id DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':rate_date' => $date]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countAll(?string $desde = null, ?string $hasta = null): int {
        $sql = "SELECT COUNT(*) FROM tbl_financial_exchange_rates WHERE status = 'ACTIVE'";
        $params = [];

        if ($desde && $hasta) {
            $sql .= " AND rate_date BETWEEN :desde AND :hasta";
            $params[':desde'] = $desde;
            $params[':hasta'] = $hasta;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getHistoryPaginated(int $limit, int $offset, ?string $desde = null, ?string $hasta = null): array {
        $sql = "SELECT id, rate_date, dolar_bcv, euro_bcv, created_at 
                FROM tbl_financial_exchange_rates 
                WHERE status = 'ACTIVE'";

        if ($desde && $hasta) {
            $sql .= " AND rate_date BETWEEN :desde AND :hasta";
        }

        $sql .= " ORDER BY rate_date DESC, id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if ($desde && $hasta) {
            $stmt->bindValue(':desde', $desde);
            $stmt->bindValue(':hasta', $hasta);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}