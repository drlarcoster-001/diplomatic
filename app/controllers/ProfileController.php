<?php
/**
 * MÓDULO: PERFIL DE USUARIO
 * Archivo: app/controllers/ProfileController.php
 * Propósito: Gestión integral del perfil, carga de avatar y seguridad (re-autenticación).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use PDO;
use Throwable;

final class ProfileController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }
        $this->db = (new Database())->getConnection();
    }

    /**
     * Vista principal del perfil
     */
    public function index(): void
    {
        $userId = $_SESSION['user']['id'];
        $stmt = $this->db->prepare("SELECT * FROM tbl_users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->view('users/profile', ['title' => 'Mi Perfil', 'u' => $usuario]);
    }

    /**
     * Procesa la actualización de datos y el Avatar
     */
    public function update(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['user']['id'];
            $avatarName = $_POST['current_avatar'] ?? 'default_avatar.png';

            if (!empty($_FILES['avatar']['name'])) {
                $dir = __DIR__ . '/../../public/assets/img/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $avatarName = 'usr_' . time() . '.' . $ext;
                $target = $dir . $avatarName;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                    $_SESSION['user']['avatar'] = $avatarName;
                }
            }

            $sql = "UPDATE tbl_users SET 
                    phone = :phone, address = :addr, provenance = :prov, 
                    undergraduate_degree = :degree, avatar = :avatar, profile_complete = 1 
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':phone'  => $_POST['phone'] ?? '',
                ':addr'   => $_POST['address'] ?? '',
                ':prov'   => $_POST['provenance'] ?? '',
                ':degree' => $_POST['undergraduate_degree'] ?? '',
                ':avatar' => $avatarName,
                ':id'     => $userId
            ]);

            AuditService::log([
                'module'      => 'PROFILE',
                'action'      => 'UPDATE',
                'description' => 'El usuario actualizó sus datos profesionales y foto de perfil',
                'event_type'  => 'SUCCESS'
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Perfil actualizado correctamente']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Vista de cambio de contraseña
     */
    public function security(): void
    {
        $this->view('users/security', ['title' => 'Seguridad']);
    }

    /**
     * Procesa el cambio de contraseña con validación de clave anterior
     */
    public function changePassword(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $userId  = $_SESSION['user']['id'];
            $oldPass = $_POST['old_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';

            // 1. Obtener el hash actual del usuario
            $stmt = $this->db->prepare("SELECT password_hash FROM tbl_users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verificar que la contraseña anterior sea correcta
            if (!$user || !password_verify($oldPass, $user['password_hash'])) {
                AuditService::log([
                    'module'      => 'PROFILE_SECURITY',
                    'action'      => 'CHANGE_PASSWORD_FAILED',
                    'description' => 'Intento fallido: La contraseña anterior no es válida.',
                    'event_type'  => 'SECURITY'
                ]);
                throw new \Exception("La contraseña anterior es incorrecta.");
            }

            // 3. Validar longitud de la nueva clave
            if (strlen($newPass) < 8) {
                throw new \Exception("La nueva contraseña debe tener al menos 8 caracteres.");
            }

            // 4. Actualizar el hash
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $update = $this->db->prepare("UPDATE tbl_users SET password_hash = :hash WHERE id = :id");
            $update->execute([':hash' => $hash, ':id' => $userId]);

            AuditService::log([
                'module'      => 'PROFILE_SECURITY',
                'action'      => 'CHANGE_PASSWORD_SUCCESS',
                'description' => 'Contraseña actualizada tras validar identidad previa',
                'event_type'  => 'WARNING' // Amarillo por ser cambio crítico
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Contraseña actualizada exitosamente']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
}