<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/controllers/RegisterController.php
 * Propósito: Controlador unificado para Registro y Recuperación de Contraseña con validación de identidad.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailServiceRegister;
use Throwable;
use PDO;

final class RegisterController extends Controller
{
    private $db;

    public function __construct() 
    { 
        $this->db = (new Database())->getConnection(); 
    }

    // --- VISTAS ---
    public function index(): void { $this->view('auth/register'); }
    public function forgotPasswordIndex(): void { $this->view('auth/forgot'); }

    /**
     * REGISTRO: Proceso de prospecto inicial (PENDING / 0)
     */
    public function submit(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            
            // Verificación previa para evitar duplicidad
            $stmtCheck = $this->db->prepare("SELECT id FROM tbl_users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) throw new \Exception("Este correo ya está registrado.");

            $this->db->beginTransaction();

            // Insert inicial con status PENDING y email_verified 0
            $sql = "INSERT INTO tbl_pre_users (first_name, last_name, email, phone, document_id, status, email_verified) 
                    VALUES (?, ?, ?, ?, ?, 'PENDING', 0)";
            $this->db->prepare($sql)->execute([
                trim($_POST['first_name'] ?? ''), 
                trim($_POST['last_name'] ?? ''), 
                $email, 
                trim($_POST['phone'] ?? ''), 
                trim($_POST['document_id'] ?? '')
            ]);
            
            $preId = (int)$this->db->lastInsertId();
            $token = bin2hex(random_bytes(32));

            // Token de registro
            $this->db->prepare("INSERT INTO tbl_pre_user_tokens (pre_user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 24 HOUR)")
                     ->execute([$preId, hash('sha256', $token)]);

            $mailer = new MailServiceRegister();
            $res = $mailer->enviarValidacion($email, [
                'nombre'           => $_POST['first_name'],
                'apellido'         => $_POST['last_name'], // Sincronizado para evitar llaves en el correo
                'link_inscripcion' => $this->getLink($token, '/register/validate')
            ], 'INSCRIPCION');

            if (!$res['ok']) throw new \Exception($res['msg']);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Registro exitoso. Revisa tu correo.']);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * FORGOT PASSWORD: Envío de enlace de recuperación con validación de Apellido
     */
    public function forgotPasswordSubmit(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $doc   = trim($_POST['document_id'] ?? '');

            // Validamos que el usuario exista y esté ACTIVO
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM tbl_users WHERE email = ? AND document_id = ? AND status = 'ACTIVE' LIMIT 1");
            $stmt->execute([$email, $doc]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) throw new \Exception("Los datos no coinciden con un usuario activo.");

            $this->db->beginTransaction();
            $token = bin2hex(random_bytes(32));
            
            // Tabla de recuperación (vence en 2 horas)
            $this->db->prepare("INSERT INTO tbl_user_recovery_tokens (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 2 HOUR)")
                     ->execute([$user['id'], hash('sha256', $token)]);

            // Envío de correo con Nombre y Apellido corregidos
            $mailer = new MailServiceRegister();
            $res = $mailer->enviarValidacion($email, [
                'nombre'           => $user['first_name'],
                'apellido'         => $user['last_name'], // SE AGREGA PARA QUE NO SALGA {{apellido}}
                'link_inscripcion' => $this->getLink($token, '/forgot-password/validate')
            ], 'RECUPERACION');

            if (!$res['ok']) throw new \Exception($res['msg']);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Enlace de recuperación enviado.']);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * VALIDADOR: Decide si el token es de Registro o Recuperación
     */
    public function validateToken(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) { header("Location: /"); exit; }
        
        $hash = hash('sha256', $token);
        
        // 1. ¿Es un token de recuperación?
        $stmtR = $this->db->prepare("SELECT t.*, u.email FROM tbl_user_recovery_tokens t JOIN tbl_users u ON t.user_id = u.id WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()");
        $stmtR->execute([$hash]);
        $rec = $stmtR->fetch(PDO::FETCH_ASSOC);

        if ($rec) {
            $this->view('auth/password_reset', ['token' => $token, 'email' => $rec['email']]);
            return;
        }

        // 2. ¿Es un token de registro?
        $stmtP = $this->db->prepare("SELECT t.*, p.email FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()");
        $stmtP->execute([$hash]);
        $pre = $stmtP->fetch(PDO::FETCH_ASSOC);

        if ($pre) {
            $this->view('auth/password', ['token' => $token, 'email' => $pre['email']]);
            return;
        }

        $this->view('auth/token_error');
    }

    /**
     * FINALIZAR: Crea usuario o actualiza clave
     */
    public function createPassword(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            $token = $_POST['token'] ?? '';
            $pass  = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
            $hash  = hash('sha256', $token);
            $this->db->beginTransaction();

            // CASO A: Recuperación de contraseña
            $stmtR = $this->db->prepare("SELECT id, user_id FROM tbl_user_recovery_tokens WHERE token_hash = ? AND used_at IS NULL");
            $stmtR->execute([$hash]);
            $rec = $stmtR->fetch(PDO::FETCH_ASSOC);

            if ($rec) {
                $this->db->prepare("UPDATE tbl_users SET password_hash = ? WHERE id = ?")->execute([$pass, $rec['user_id']]);
                $this->db->prepare("UPDATE tbl_user_recovery_tokens SET used_at = NOW() WHERE id = ?")->execute([$rec['id']]);
            } else {
                // CASO B: Registro nuevo
                $stmtP = $this->db->prepare("SELECT p.*, t.id as tid FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id WHERE t.token_hash = ? AND t.used_at IS NULL");
                $stmtP->execute([$hash]);
                $pre = $stmtP->fetch(PDO::FETCH_ASSOC);
                
                if (!$pre) throw new \Exception("Token inválido.");

                // INSERT con PARTICIPANT / PARTICIPANT
                $sqlI = "INSERT INTO tbl_users (user_type, status, first_name, last_name, email, role, phone, document_id, password_hash, created_at) 
                         VALUES ('PARTICIPANT', 'ACTIVE', ?, ?, ?, 'PARTICIPANT', ?, ?, ?, NOW())";
                $this->db->prepare($sqlI)->execute([$pre['first_name'], $pre['last_name'], $pre['email'], $pre['phone'], $pre['document_id'], $pass]);
                
                $this->db->prepare("UPDATE tbl_pre_user_tokens SET used_at = NOW() WHERE id = ?")->execute([$pre['tid']]);
                $this->db->prepare("UPDATE tbl_pre_users SET status = 'VERIFIED', email_verified = 1 WHERE id = ?")->execute([$pre['id']]);
            }

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Proceso completado exitosamente.']);
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); }
        exit;
    }

    private function getLink($token, $path) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return "{$protocol}://{$_SERVER['HTTP_HOST']}{$base}{$path}?token={$token}";
    }
}