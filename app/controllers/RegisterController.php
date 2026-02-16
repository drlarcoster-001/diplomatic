<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/controllers/RegisterController.php
 * Propósito: Gestión de pre-registro, validación de tokens y promoción a usuarios reales.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailServiceRegister;
use App\Services\AuditService;
use Throwable;
use PDO;

final class RegisterController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->view('auth/register', ['title' => 'Registro']);
    }

    public function submit(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            if (!$email) throw new \Exception("El correo es obligatorio.");

            $this->db->beginTransaction();

            $sql = "INSERT INTO tbl_pre_users (first_name, last_name, email, phone, document_id, status) 
                    VALUES (:fname, :lname, :email, :phone, :doc, 'PENDING')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':fname' => $_POST['first_name'] ?? '',
                ':lname' => $_POST['last_name'] ?? '',
                ':email' => $email,
                ':phone' => $_POST['phone'] ?? null,
                ':doc'   => $_POST['document_id'] ?? null
            ]);
            $preUserId = (int)$this->db->lastInsertId();

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $this->db->prepare("INSERT INTO tbl_pre_user_tokens (pre_user_id, token_hash, expires_at) VALUES (?, ?, ?)")
                     ->execute([$preUserId, hash('sha256', $token), $expires]);

            $mailer = new MailServiceRegister();
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $link = "{$protocol}://{$_SERVER['HTTP_HOST']}{$basePath}/register/validate?token={$token}";

            $resMail = $mailer->enviarValidacion($email, [
                'nombre' => $_POST['first_name'] ?? '',
                'apellido' => $_POST['last_name'] ?? '',
                'link_inscripcion' => $link
            ]);

            if (!$resMail['ok']) throw new \Exception($resMail['msg']);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Registro exitoso. Revisa tu correo.']);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function validateToken(): void
    {
        $token = $_GET['token'] ?? '';
        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare("SELECT t.*, p.email FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id WHERE t.token_hash = ? AND t.expires_at > NOW() AND t.used_at IS NULL");
        $stmt->execute([$hash]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) die("Enlace inválido o expirado.");
        $this->view('auth/password', ['token' => $token, 'email' => $data['email']]);
    }

    public function createPassword(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $hashToken = hash('sha256', $token);

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT p.*, t.id as tid FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id WHERE t.token_hash = ? AND t.used_at IS NULL");
            $stmt->execute([$hashToken]);
            $pre = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pre) throw new \Exception("Token inválido.");

            $sql = "INSERT INTO tbl_users (user_type, status, first_name, last_name, email, role, phone, document_id, password_hash, created_at) 
                    VALUES ('ESTUDIANTE', 'ACTIVE', :f, :l, :e, 'USER', :ph, :doc, :pass, NOW())";
            $this->db->prepare($sql)->execute([
                ':f' => $pre['first_name'], ':l' => $pre['last_name'], ':e' => $pre['email'],
                ':ph' => $pre['phone'], ':doc' => $pre['document_id'],
                ':pass' => password_hash($password, PASSWORD_BCRYPT)
            ]);

            $this->db->prepare("UPDATE tbl_pre_user_tokens SET used_at = NOW() WHERE id = ?")->execute([$pre['tid']]);
            $this->db->prepare("UPDATE tbl_pre_users SET status = 'VERIFIED' WHERE id = ?")->execute([$pre['id']]);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Cuenta activada.']);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
}