<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/dashboard/index.php
 * Propósito: Página principal del dashboard, que incluye el layout base.
 * Nota: Este archivo utiliza el layout común para el dashboard.
 */

declare(strict_types=1);

$content = '
  <h2 class="mb-4">Bienvenido, ' . htmlspecialchars($_SESSION['user']['name']) . '!</h2>
  <p>Este es el panel de control principal. Aquí puedes gestionar tus diplomados, ver estadísticas y más.</p>
';

include __DIR__ . '/../layout.php'; // Incluye el layout con topnav y sidebar
