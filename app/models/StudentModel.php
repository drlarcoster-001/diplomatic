<?php
/**
 * MÓDULO: ESTUDIANTES
 * ARCHIVO: app/Models/StudentModel.php
 * PROPÓSITO: Interacción con la base de datos para el portal del estudiante (Ofertas, Inscripciones, Pagos).
 * VERSIÓN: 1.0.0 - Creación inicial. Consulta de ofertas académicas con estatus ABIERTA.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class StudentModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la lista de ofertas académicas disponibles para inscripción.
     * @return array
     */
    public function getOpenOfferings(): array
    {
        $sql = "
            SELECT 
                o.id as offering_id, 
                d.name as diplomado_name, 
                c.name as cohort_name, 
                o.general_modality, 
                o.total_cost, 
                o.currency_code, 
                o.registration_end, 
                o.class_start
            FROM tbl_academic_offerings o
            JOIN tbl_diplomas d ON o.diploma_id = d.id
            JOIN tbl_cohorts c ON o.cohort_id = c.id
            WHERE o.status = 'ABIERTA'
            ORDER BY o.registration_end ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}