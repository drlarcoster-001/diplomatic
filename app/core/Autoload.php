<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Autoload.php
 * Propósito: Autocargador PSR-4 con mapeo de servicios.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
  $prefix = 'App\\';
  $baseDir = __DIR__ . '/../'; 

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) return;

  $relativeClass = substr($class, $len);
  $relativePath = str_replace('\\', '/', $relativeClass);

  $parts = explode('/', $relativePath);
  if (!empty($parts[0])) {
    $map = [
      'Core'        => 'core',
      'Controllers' => 'controllers',
      'Models'      => 'models',
      'Config'      => 'config',
      'Middleware'  => 'middleware',
      'Views'       => 'views',
      'Services'    => 'services', // <--- FUNDAMENTAL
    ];
    if (isset($map[$parts[0]])) {
      $partsMapped = $parts;
      $partsMapped[0] = $map[$parts[0]];
      $file = $baseDir . implode('/', $partsMapped) . '.php';
      if (file_exists($file)) { require_once $file; return; }
    }
  }

  $fallback = $baseDir . strtolower($relativePath) . '.php';
  if (file_exists($fallback)) require_once $fallback;
});