<?php
/**
 * MÓDULO: NÚCLEO / CONFIGURACIÓN
 * ARCHIVO: app/config/database.php
 * VERSIÓN: 1.8.1 - Credenciales corregidas para cPanel platafo2
 */

declare(strict_types=1);

return [
    'host'    => 'localhost', // CAMBIO: Usar 'localhost' es obligatorio en cPanel casi siempre
    //'dbname'  => 'platafo2_cohorte14_db_diplomatic', // CAMBIO: Tú tenías el prefijo al revés
    'dbname'  => 'platafo2_db_diplomatic', // CAMBIO: Tú tenías el prefijo al revés
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
];