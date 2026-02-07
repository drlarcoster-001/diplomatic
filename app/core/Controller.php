<?php
/**
 * MÓDULO 1 - app/core/Controller.php
 * Controlador base del MVC.
 * Provee métodos mínimos de renderizado y redirección, sin lógica de negocio.
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
  protected function view(string $viewPath, array $data = []): void
  {
    // Renderiza una vista simple (por ahora sin layouts)
    View::render($viewPath, $data);
  }

  protected function redirect(string $path): void
  {
    // Redirección relativa al directorio donde vive index.php
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    header('Location: ' . $basePath . $path);
    exit;
  }
}
