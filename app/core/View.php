<?php
/**
 * MÓDULO 1 - app/core/View.php
 * Motor mínimo de vistas.
 * Renderiza archivos PHP dentro de /app/views sin layouts por ahora.
 */

declare(strict_types=1);

namespace App\Core;

final class View
{
  public static function render(string $viewPath, array $data = []): void
  {
    $viewFile = __DIR__ . '/../views/' . trim($viewPath, '/') . '.php';

    if (!file_exists($viewFile)) {
      http_response_code(500);
      echo 'Vista no encontrada: ' . htmlspecialchars($viewPath);
      return;
    }

    // Variables disponibles en la vista
    extract($data, EXTR_SKIP);

    require $viewFile;
  }
}
