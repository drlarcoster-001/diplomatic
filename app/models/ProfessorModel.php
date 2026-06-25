<?php
/**
 * MÓDULO: PORTAL DOCENTE
 * ARCHIVO: app/models/ProfessorModel.php
 * PROPÓSITO: Modelo compartido — resuelve el expediente de tbl_professors
 *            vinculado al usuario de sesión (vía tbl_professors.user_id).
 *            Lo usan todos los controladores del Portal Docente.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getProfessorByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_professors WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}