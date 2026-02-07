<?php
/**
 * MÓDULO 1 - app/core/Router.php
 * Router simple: soporta GET y POST.
 * Nota: sistema cerrado -> si no encuentra ruta, redirige a /.
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
  private array $routes = [
    'GET' => [],
    'POST' => [],
  ];

  public function get(string $path, array $handler): void
  {
    $this->routes['GET'][$this->normalize($path)] = $handler;
  }

  public function post(string $path, array $handler): void
  {
    $this->routes['POST'][$this->normalize($path)] = $handler;
  }

  public function dispatch(): void
  {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

    // Detecta prefijo /diplomatic/public o /diplomatic
    $basePath = $this->detectBasePath();
    $path = $this->normalize(str_replace($basePath, '', $uri) ?: '/');

    $handler = $this->routes[$method][$path] ?? null;

    if (!$handler) {
      // Sistema cerrado: vuelve a la raíz (login)
      header('Location: ' . $basePath . '/');
      exit;
    }

    [$class, $action] = $handler;

    $controller = new $class();

    if (!method_exists($controller, $action)) {
      header('Location: ' . $basePath . '/');
      exit;
    }

    $controller->$action();
  }

  private function normalize(string $path): string
  {
    $path = '/' . trim($path, '/');
    return $path === '//' ? '/' : $path;
  }

  private function detectBasePath(): string
  {
    // Ejemplo: SCRIPT_NAME = /diplomatic/public/index.php
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    return rtrim($dir, '/'); // /diplomatic/public
  }
}
