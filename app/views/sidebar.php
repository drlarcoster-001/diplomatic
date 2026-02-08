<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/sidebar.php
 * Propósito: Sidebar del panel. (Sin burger aquí, solo opciones).
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<aside id="dpSidebar" class="dp-sidebar bg-dark text-white">
  <div class="py-3 px-4 border-bottom border-secondary">
    <div class="fw-semibold">Menú</div>
    <div class="text-muted" style="font-size:.85rem;">Navegación</div>
  </div>

  <nav class="list-group list-group-flush">
    <a href="<?= htmlspecialchars($basePath) ?>/dashboard"
       class="list-group-item list-group-item-action bg-dark text-white border-0">
      Dashboard
    </a>

    <a href="#"
       class="list-group-item list-group-item-action bg-dark text-white border-0">
      Perfil
    </a>

    <a href="#"
       class="list-group-item list-group-item-action bg-dark text-white border-0">
      Configuración
    </a>
  </nav>
</aside>
