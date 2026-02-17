<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: public/logout.php
 * Propósito: Cierre de sesión total, incluyendo eliminación de tokens de persistencia.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/core/Database.php'; // Necesario para limpiar la BD

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. LIMPIEZA DE PERSISTENCIA (RECORDARME)
if (isset($_COOKIE['remember_me'])) {
    try {
        $db = (new \App\Core\Database())->getConnection();
        $tokenHash = hash('sha256', $_COOKIE['remember_me']);
        
        // Borramos el token de la base de datos para que no se pueda reutilizar
        $stmt = $db->prepare("DELETE FROM tbl_user_remember_tokens WHERE token_hash = ?");
        $stmt->execute([$tokenHash]);
    } catch (\Throwable $e) {
        // Fallo silencioso si no hay conexión, priorizamos el cierre de sesión
    }

    // Eliminamos la cookie del navegador
    setcookie('remember_me', '', time() - 3600, '/');
}

// 2. LIMPIEZA DE SESIÓN PHP
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// 3. REDIRECCIÓN
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
header('Location: ' . $basePath . '/');
exit;