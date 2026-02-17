<?php
/**
 * MÓDULO: USUARIOS Y ACCESO
 * Archivo: app/controllers/RegisterController.php
 * Propósito: Registro con asignación de ROL 'PARTICIPANT' y TIPO 'PARTICIPANT'.
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

    public function index(): void 
    { 
        $this->view('auth/register', ['title' => 'Registro']); 
    }

    /**
     * REGISTRO INICIAL (Prospecto)
     */
    public function submit(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            
            // Verificación previa en tabla de usuarios reales
            $stmtCheck = $this->db->prepare("SELECT id FROM tbl_users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                throw new \Exception("Este correo ya está registrado en la plataforma.");
            }

            $fName = trim($_POST['first_name'] ?? '');
            $lName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $docId = trim($_POST['document_id'] ?? '');

            $this->db->beginTransaction();

            // INSERT inicial en tbl_pre_users: status PENDING y email_verified 0
            $sql = "INSERT INTO tbl_pre_users (first_name, last_name, email, phone, document_id, status, email_verified) 
                    VALUES (?, ?, ?, ?, ?, 'PENDING', 0)";
            $this->db->prepare($sql)->execute([$fName, $lName, $email, $phone, $docId]);
            
            $preId = (int)$this->db->lastInsertId();
            $token = bin2hex(random_bytes(32));

            // Guardar Token Hash
            $this->db->prepare("INSERT INTO tbl_pre_user_tokens (pre_user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 24 HOUR)")
                     ->execute([$preId, hash('sha256', $token)]);

            $mailer = new MailServiceRegister();
            $res = $mailer->enviarValidacion($email, [
                'nombre'           => $fName,
                'apellido'         => $lName,
                'link_inscripcion' => $this->getLink($token, '/register/validate')
            ], 'INSCRIPCION');

            if (!$res['ok']) throw new \Exception($res['msg']);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Registro exitoso. Revisa tu correo para activar tu cuenta.']);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * VALIDAR TOKEN
     */
    public function validateToken(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            header("Location: " . $this->getLink('', '/'));
            exit;
        }

        $hash = hash('sha256', $token);
        
        $sqlPre = "SELECT t.*, p.email FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id 
                   WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()";
        
        $stmtPre = $this->db->prepare($sqlPre);
        $stmtPre->execute([$hash]);
        $dataPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

        if ($dataPre) {
            $this->view('auth/password', ['token' => $token, 'email' => $dataPre['email']]);
            return;
        }
        
        $this->view('auth/token_error');
    }

    /**
     * FINALIZAR: Conversión de Pre-usuario a Usuario Real.
     * Mapeo según tbl_roles y tbl_users
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

            $stmtP = $this->db->prepare("SELECT p.*, t.id as tid FROM tbl_pre_user_tokens t JOIN tbl_pre_users p ON t.pre_user_id = p.id WHERE t.token_hash = ? AND t.used_at IS NULL");
            $stmtP->execute([$hash]);
            $pre = $stmtP->fetch(PDO::FETCH_ASSOC);
            
            if (!$pre) throw new \Exception("El token no es válido o ya fue procesado.");

            /**
             * ALINEACIÓN FINAL CON TUS TABLAS:
             * user_type: 'PARTICIPANT'
             * role: 'PARTICIPANT'
             * status: 'ACTIVE'
             */
            $sqlI = "INSERT INTO tbl_users (user_type, status, first_name, last_name, email, role, phone, document_id, password_hash, created_at) 
                     VALUES ('PARTICIPANT', 'ACTIVE', ?, ?, ?, 'PARTICIPANT', ?, ?, ?, NOW())";
            
            $this->db->prepare($sqlI)->execute([
                $pre['first_name'], 
                $pre['last_name'], 
                $pre['email'], 
                $pre['phone'], 
                $pre['document_id'], 
                $pass
            ]);
            
            // Marcar Token como usado
            $this->db->prepare("UPDATE tbl_pre_user_tokens SET used_at = NOW() WHERE id = ?")->execute([$pre['tid']]);
            
            // Actualizar Pre-usuario a VERIFIED y 1
            $this->db->prepare("UPDATE tbl_pre_users SET status = 'VERIFIED', email_verified = 1 WHERE id = ?")->execute([$pre['id']]);

            $this->db->commit();
            echo json_encode(['ok' => true, 'msg' => '¡Cuenta activada con éxito!']);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (strpos($e->getMessage(), '1062') !== false) {
                echo json_encode(['ok' => false, 'msg' => 'El correo ya está en uso.']);
            } else {
                echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
            }
        }
        exit;
    }

    private function getLink($token, $path) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return "{$protocol}://{$_SERVER['HTTP_HOST']}{$base}{$path}?token={$token}";
    }
}