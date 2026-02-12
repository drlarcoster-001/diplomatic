<?php
/**
 * MÓDULO - app/controllers/SettingsCompanyController.php
 * Controlador de gestión de identidad institucional.
 * Administra Razón Social, ID Fiscal, Representante Legal y Contactos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class SettingsCompanyController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_company_settings LIMIT 1");
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->view('settings/empresa', [
            'title' => 'Datos de la Institución',
            'empresa' => $empresa
        ]);
    }

    public function save(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            // Forzamos el ID 1 para mantener un registro único de la empresa
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

            echo json_encode(['ok' => true, 'msg' => 'Información actualizada correctamente']);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error SQL: ' . $e->getMessage()]);
        }
        exit;
    }
}