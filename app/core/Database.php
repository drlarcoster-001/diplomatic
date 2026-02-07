<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/core/Database.php
 * Propósito: conexión central PDO a MySQL (XAMPP) para uso por modelos/repositorios.
 * Nota: aquí NO se hacen consultas de negocio; solo conexión y helpers mínimos.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
  private static ?PDO $pdo = null;

  public static function getConnection(): PDO
  {
    if (self::$pdo instanceof PDO) {
      return self::$pdo;
    }

    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) {
      throw new \RuntimeException('No existe el archivo de configuración de base de datos: app/config/database.php');
    }

    $cfg = require $configFile;

    $host = $cfg['host'] ?? '127.0.0.1';
    $dbname = $cfg['dbname'] ?? '';
    $user = $cfg['user'] ?? 'root';
    $pass = $cfg['pass'] ?? '';
    $charset = $cfg['charset'] ?? 'utf8mb4';

    if ($dbname === '') {
      throw new \RuntimeException('La configuración de base de datos no contiene dbname.');
    }

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    try {
      self::$pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);

      return self::$pdo;
    } catch (PDOException $e) {
      /**
       * Comentario importante:
       * No mostramos detalles sensibles al usuario final. Solo devolvemos un mensaje técnico controlado.
       * Los detalles se deben registrar en logs (si luego habilitamos storage/logs).
       */
      throw new \RuntimeException('Error de conexión a la base de datos. Verifique credenciales y que MySQL esté activo.');
    }
  }
}
