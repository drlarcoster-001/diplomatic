<?php
/**
 * MÓDULO 1 - public/index.php
 * Front Controller: punto único de entrada.
 * Inicia sesión, registra el autoload y ejecuta Bootstrap.
 */

declare(strict_types=1);

session_start();

// Autoload del proyecto (sin Composer)
require_once __DIR__ . '/../app/core/Autoload.php';

// Ejecuta la app
$app = new \App\Core\Bootstrap();
$app->run();
