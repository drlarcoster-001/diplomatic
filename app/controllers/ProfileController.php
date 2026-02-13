<?php
/**
 * MÓDULO: PERFIL DE USUARIO
 * Archivo: app/controllers/ProfileController.php
 * Propósito: Gestión personal de datos, perfil profesional y seguridad del usuario.
 * Registro: Integrado con AuditService para trazabilidad total.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService; // Notario del sistema
use PDO;
use Throwable;

final class ProfileController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Verificamos que exista una sesión activa antes de proceder
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }
        
        $this->db = (new Database())->getConnection();
    }

    /**
     * Carga la vista principal del perfil con los datos del usuario logueado.
     */
    public function index(): void
    {
        $userId = $_SESSION['user']['id'];

        // Registro de Acceso en la Auditoría
        AuditService::log([
            'module'      => 'PROFILE',
            'action'      => 'ACCESS',
            'description' => 'El usuario accedió a gestionar su perfil personal',
            'event_type'  => 'NORMAL'
        ]);

        $stmt = $this->db->prepare("SELECT * FROM tbl_users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->view('users/profile', [
            'title' => 'Mi Perfil Profesional',
            'u'     => $usuario
        ]);
    }

    /**
     * Actualiza los datos profesionales y marca el perfil como completo.
     */
    public function update(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user']['id'];

            // Actualización de la misma tabla gestionada por el admin
            // Nota: Se añade 'profile_complete' para el algoritmo de pre-registro
            $sql = "UPDATE tbl_users SET 
                    direccion = :dir, 
                    telefono = :tel, 
                    grado_academico = :grado, 
                    especialidad = :esp,
                    profile_complete = 1 
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':dir'   => $_POST['direccion'] ?? '',
                ':tel'   => $_POST['telefono'] ?? '',
                ':grado' => $_POST['grado_academico'] ?? '',
                ':esp'   => $_POST['especialidad'] ?? '',
                ':id'    => $userId
            ]);

            // Auditoría del cambio
            AuditService::log([
                'module'      => 'PROFILE',
                'action'      => 'UPDATE_INFO',
                'description' => 'El usuario actualizó su información profesional y académica',
                'event_type'  => 'SUCCESS' // Color verde en consola
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Perfil actualizado correctamente']);

        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Muestra la vista de seguridad (cambio de clave).
     */
    public function security(): void
    {
        AuditService::log([
            'module'      => 'PROFILE_SECURITY',
            'action'      => 'ACCESS',
            'description' => 'El usuario ingresó al panel de cambio de credenciales',
            'event_type'  => 'NORMAL'
        ]);

        $this->view('users/security', ['title' => 'Seguridad de la Cuenta']);
    }

    /**
     * Procesa el cambio de contraseña con validación de seguridad.
     */
    public function changePassword(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user']['id'];
            $newPass = $_POST['new_password'] ?? '';

            if (strlen($newPass) < 8) {
                throw new \Exception("La contraseña debe tener al menos 8 caracteres");
            }

            $hash = password_hash($newPass, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("UPDATE tbl_users SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $userId]);

            AuditService::log([
                'module'      => 'PROFILE_SECURITY',
                'action'      => 'CHANGE_PASSWORD',
                'description' => 'El usuario realizó un cambio exitoso de su contraseña',
                'event_type'  => 'WARNING' // Resalta en amarillo por ser cambio crítico
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Contraseña actualizada']);

        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
}