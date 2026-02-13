<?php
/**
 * MODULE - app/controllers/SettingsCompanyController.php
 * Institutional Identity Management Controller.
 * Integrated with AuditService for data traceability.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService; // IMPORTANTE: Notario del sistema
use PDO;

final class SettingsCompanyController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Display Company Settings and Log Access.
     */
    public function index(): void
    {
        // Registro de Acceso en la Auditoría
        AuditService::log([
            'module'      => 'SETTINGS_COMPANY',
            'action'      => 'ACCESS',
            'description' => 'User accessed the institutional identity profile',
            'event_type'  => 'NORMAL' // Color Verde en consola
        ]);

        $stmt = $this->db->query("SELECT * FROM tbl_company_settings LIMIT 1");
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->view('settings/empresa', [
            'title' => 'Datos de la Institución',
            'empresa' => $empresa
        ]);
    }

    /**
     * Save/Update Company Settings and Log Changes.
     */
    public function save(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            // 1. CAPTURAMOS ESTADO ANTERIOR (Auditoría Profunda)
            $beforeStmt = $this->db->query("SELECT * FROM tbl_company_settings WHERE id = 1");
            $dataBefore = $beforeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // 2. EJECUTAMOS LA ACTUALIZACIÓN
            $sql = "INSERT INTO tbl_company_settings 
                    (id, nombre_legal, nombre_comercial, id_fiscal, direccion, telefono, email, sitio_web, representante, cargo_rep, tel_rep) 
                    VALUES (1, :n_legal, :n_com, :id_f, :dir, :tel, :email, :web, :rep, :cargo, :tel_r)
                    ON DUPLICATE KEY UPDATE 
                    nombre_legal=:u_legal, nombre_comercial=:u_com, id_fiscal=:u_f, direccion=:u_dir, 
                    telefono=:u_tel, email=:u_email, sitio_web=:u_web, representante=:u_rep, cargo_rep=:u_cargo, tel_rep=:u_tel_r";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':n_legal' => $_POST['nombre_legal'] ?? '',
                ':n_com'   => $_POST['nombre_comercial'] ?? '',
                ':id_f'    => $_POST['id_fiscal'] ?? '',
                ':dir'     => $_POST['direccion'] ?? '',
                ':tel'     => $_POST['telefono'] ?? '',
                ':email'   => $_POST['email'] ?? '',
                ':web'     => $_POST['sitio_web'] ?? '',
                ':rep'     => $_POST['representante'] ?? '',
                ':cargo'   => $_POST['cargo_rep'] ?? '',
                ':tel_r'   => $_POST['tel_rep'] ?? '',
                // Parámetros para el UPDATE
                ':u_legal' => $_POST['nombre_legal'] ?? '',
                ':u_com'   => $_POST['nombre_comercial'] ?? '',
                ':u_f'     => $_POST['id_fiscal'] ?? '',
                ':u_dir'   => $_POST['direccion'] ?? '',
                ':u_tel'   => $_POST['telefono'] ?? '',
                ':u_email' => $_POST['email'] ?? '',
                ':u_web'   => $_POST['sitio_web'] ?? '',
                ':u_rep'   => $_POST['representante'] ?? '',
                ':u_cargo' => $_POST['cargo_rep'] ?? '',
                ':u_tel_r' => $_POST['tel_rep'] ?? ''
            ]);

            // 3. REGISTRAMOS LA ACTUALIZACIÓN CON DATA ANTES/DESPUÉS
            AuditService::log([
                'module'       => 'SETTINGS_COMPANY',
                'action'       => 'UPDATE',
                'description'  => 'Updated legal and commercial identity information',
                'entity'       => 'tbl_company_settings',
                'entity_id'    => 1,
                'db_action'    => 'UPDATE',
                'data_before'  => $dataBefore, // Estado previo
                'data_after'   => $_POST,      // Nuevo estado enviado
                'event_type'   => 'WARNING'    // Color Amarillo en consola
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Información actualizada correctamente']);

        } catch (\Throwable $e) {
            // Registro de Error en Auditoría
            AuditService::log([
                'module'      => 'SETTINGS_COMPANY',
                'action'      => 'ERROR',
                'description' => 'System error during company settings update: ' . $e->getMessage(),
                'event_type'  => 'ERROR' // Color Rojo en consola
            ]);
            
            echo json_encode(['ok' => false, 'msg' => 'Error SQL: ' . $e->getMessage()]);
        }
        exit;
    }
}