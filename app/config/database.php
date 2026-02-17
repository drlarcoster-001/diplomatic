<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/config/database.php
 * Propósito: Configuración de la base de datos para la conexión.
 * Nota: Ajusta los parámetros según tu entorno de desarrollo (XAMPP/MYSQL).
 */

return [
  'host' => '127.0.0.1',  // Dirección del servidor de base de datos
  'dbname' => 'db_diplomatic',  // Nombre de la base de datos
  'user' => 'root',  // Usuario de MySQL (por defecto en XAMPP)
  'pass' => '',  // Contraseña de MySQL (en XAMPP, el usuario 'root' no tiene contraseña)
  'charset' => 'utf8mb4',  // Codificación recomendada para MySQL
];

// Punto Antes de Gestion Academica