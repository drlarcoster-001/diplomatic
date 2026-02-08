<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/View.php
 * Propósito: Renderizar vistas dentro del Layout principal automáticamente.
 */

declare(strict_types=1);

namespace App\Core;

final class View
{
  public static function render(string $viewPath, array $data = []): void
  {
    $viewFile = __DIR__ . '/../views/' . trim($viewPath, '/') . '.php';

    if (!file_exists($viewFile)) {
      echo "<h1>Error: No se encuentra la vista: {$viewPath}</h1>";
      return;
    }

    extract($data, EXTR_SKIP);

    // LÓGICA DE LAYOUT:
    // Si es login (auth), carga limpio. Si es interno, carga con Layout.
    if (strpos($viewPath, 'auth/') !== false) {
        require $viewFile;
    } else {
        ob_start(); // Inicia la captura del HTML
        require $viewFile; // Carga la tabla de usuarios
        $content = ob_get_clean(); // Guarda el HTML en la variable $content

        // Inyecta el contenido en el marco principal
        require __DIR__ . '/../views/layout.php';
    }
  }
}