<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/core/Autoload.php
 * Propósito: Autocargador PSR-4 simple para App\ sin Composer.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
  $prefix = 'App\\';
  $baseDir = __DIR__ . '/../'; 

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) return;

  $relativeClass = substr($class, $len);
  $relativePath = str_replace('\\', '/', $relativeClass);

  $candidates = [];
  $candidates[] = $baseDir . $relativePath . '.php';

  $parts = explode('/', $relativePath);
  if (!empty($parts[0])) {
    $map = [
      'Core'        => 'core',
      'Controllers' => 'controllers',
      'Models'      => 'models',
      'Config'      => 'config',
      'Middleware'  => 'middleware',
      'Views'       => 'views',
      'Services'    => 'services', // Mapeo crítico para auditoría
    ];
    if (isset($map[$parts[0]])) {
      $partsMapped = $parts;
      $partsMapped[0] = $map[$parts[0]];
      $candidates[] = $baseDir . implode('/', $partsMapped) . '.php';
    }
  }

  $candidates[] = $baseDir . strtolower($relativePath) . '.php';

  foreach ($candidates as $file) {
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }
});