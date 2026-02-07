<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/core/Autoload.php
 * Propósito: Autocargador PSR-4 simple para App\ sin Composer.
 * Solución: intenta rutas con normalización de mayúsculas/minúsculas para evitar fallos en Windows/XAMPP.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
  $prefix = 'App\\';
  $baseDir = __DIR__ . '/../'; // apunta a /app

  // Solo cargamos clases del namespace App\
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    return;
  }

  // App\Models\UserModel -> Models/UserModel
  $relativeClass = substr($class, $len);
  $relativePath = str_replace('\\', '/', $relativeClass);

  // Candidatos de rutas a intentar (para evitar problemas por case)
  $candidates = [];

  // 1) Ruta directa
  $candidates[] = $baseDir . $relativePath . '.php';

  // 2) Normalizar primer segmento: Core/Controllers/Models/... -> core/controllers/models/...
  $parts = explode('/', $relativePath);
  if (!empty($parts[0])) {
    $map = [
      'Core' => 'core',
      'Controllers' => 'controllers',
      'Models' => 'models',
      'Config' => 'config',
      'Middleware' => 'middleware',
      'Views' => 'views',
    ];
    if (isset($map[$parts[0]])) {
      $partsMapped = $parts;
      $partsMapped[0] = $map[$parts[0]];
      $candidates[] = $baseDir . implode('/', $partsMapped) . '.php';
    }
  }

  // 3) Todo el path en minúsculas (por si tu carpeta está en minúsculas y el namespace en mayúsculas)
  $candidates[] = $baseDir . strtolower($relativePath) . '.php';

  // 4) Solo carpetas en minúsculas, nombre de archivo intacto (más seguro)
  if (count($parts) >= 2) {
    $fileName = array_pop($parts);
    $dirsLower = array_map('strtolower', $parts);
    $candidates[] = $baseDir . implode('/', $dirsLower) . '/' . $fileName . '.php';
  }

  // Intentar cargar el primer archivo que exista
  foreach ($candidates as $file) {
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }
});
