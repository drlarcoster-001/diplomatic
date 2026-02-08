<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: public/logout.php
 * Propósito: Forzar cierre de sesión (herramienta temporal de soporte).
 * Nota: Redirige correctamente al login dentro del basePath del proyecto.
 */

declare(strict_types=1);

session_start();

// Limpia variables de sesión
$_SESSION = [];

// Destruye la sesión
if (session_id() !== '' || isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// BasePath real del proyecto (ej: /diplomatic/public)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Redirige al login del sistema (ruta "/")
header('Location: ' . $basePath . '/');
exit;
