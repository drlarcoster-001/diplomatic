<?php
/**
 * MÓDULO: NÚCLEO / CONFIGURACIÓN
 * ARCHIVO: app/config/database.php
 * PROPÓSITO: Parámetros de conexión a la base de datos MySQL/MariaDB en entorno de producción.
 * VERSIÓN: 1.8.0 - Sincronización con credenciales de servidor cPanel platafo2 y soporte utf8mb4.
 */

declare(strict_types=1);

return [
    'host'    => '127.0.0.1',             // Host local para el servidor plataformadiplomados.com
    'dbname'  => 'platafo2_db_diplomatic_v2',// Base de datos principal según estructura cPanel
    'user'    => 'root',   // Usuario administrativo de base de datos
    'pass'    => 'Diplomatic2026',      // Contraseña validada para entorno de producción
    'charset' => 'utf8mb4',               // Soporte para caracteres especiales y emojis
];