<?php
/**
 * MODULE - app/controllers/SettingsCompanyController.php
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService; 
use PDO;
use Throwable;

final class SettingsCompanyController extends Controller
{
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    public function index(): void {
        AuditService::log([
            'module' => 'SETTINGS_COMPANY', 'action' => 'ACCESS',
            'description' => 'User accessed the company profile', 'event_type' => 'NORMAL'
        ]);
        $stmt = $this->db->query("SELECT * FROM tbl_company_settings LIMIT 1");
        $this->view('settings/empresa', ['title' => 'Datos de la Institución', 'empresa' => $stmt->fetch(PDO::FETCH_ASSOC) ?: []]);
    }

    public function save(): void {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            // SQL con marcadores ÚNICOS para INSERT y para UPDATE (Evita error 500)
            $sql = "INSERT INTO tbl_company_settings 
                    (id, nombre_legal, nombre_comercial, id_fiscal, direccion, telefono, email, sitio_web, representante, cargo_rep, tel_rep) 
                    VALUES (1, :legal, :comercial, :fiscal, :dir, :tel, :email, :web, :rep, :cargo, :tel_r)
                    ON DUPLICATE KEY UPDATE 
                    nombre_legal=:u_legal, nombre_comercial=:u_comercial, id_fiscal=:u_fiscal, direccion=:u_dir, 
                    telefono=:u_tel, email=:u_email, sitio_web=:u_web, representante=:u_rep, cargo_rep=:u_cargo, tel_rep=:u_tel_r";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':legal'       => $_POST['nombre_legal'] ?? '',
                ':comercial'   => $_POST['nombre_comercial'] ?? '',
                ':fiscal'      => $_POST['id_fiscal'] ?? '',
                ':dir'         => $_POST['direccion'] ?? '',
                ':tel'         => $_POST['telefono'] ?? '',
                ':email'       => $_POST['email'] ?? '',
                ':web'         => $_POST['sitio_web'] ?? '',
                ':rep'         => $_POST['representante'] ?? '',
                ':cargo'       => $_POST['cargo_rep'] ?? '',
                ':tel_r'       => $_POST['tel_rep'] ?? '',
                // Parámetros únicos para el bloque UPDATE
                ':u_legal'     => $_POST['nombre_legal'] ?? '',
                ':u_comercial' => $_POST['nombre_comercial'] ?? '',
                ':u_fiscal'    => $_POST['id_fiscal'] ?? '',
                ':u_dir'       => $_POST['direccion'] ?? '',
                ':u_tel'       => $_POST['telefono'] ?? '',
                ':u_email'     => $_POST['email'] ?? '',
                ':u_web'       => $_POST['sitio_web'] ?? '',
                ':u_rep'       => $_POST['representante'] ?? '',
                ':u_cargo'     => $_POST['cargo_rep'] ?? '',
                ':u_tel_r'     => $_POST['tel_rep'] ?? ''
            ]);

            AuditService::log([
                'module' => 'SETTINGS_COMPANY', 'action' => 'UPDATE',
                'description' => 'Updated institutional identity data', 'event_type' => 'WARNING'
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Información actualizada']);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}