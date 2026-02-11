<?php
/**
 * MÓDULO: PANEL (SIDEBAR)
 * Archivo: app/views/sidebar.php
 * Propósito: Menú lateral de navegación.
 * Lógica: Muestra enlaces según el ROL del usuario (RBAC).
 */

declare(strict_types=1);

// Calculamos la ruta base para que los enlaces funcionen en local o producción
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Recuperamos el rol del usuario (convertimos a mayúsculas para comparar seguro)
$userRole = strtoupper($_SESSION['user']['role'] ?? '');
?>

<aside id="dpSidebar" class="dp-sidebar bg-dark text-white d-flex flex-column">
  
  <div class="py-3 px-4 border-bottom border-secondary">
    <div class="fw-bold d-flex align-items-center gap-2">
      <i class="bi bi-grid-fill text-primary"></i> 
      <span>NAVEGACIÓN</span>
    </div>
  </div>

  <nav class="list-group list-group-flush mt-2 flex-grow-1">
    
    <a href="<?= htmlspecialchars($basePath) ?>/dashboard"
       class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 px-4">
      <i class="bi bi-speedometer2 me-2 text-secondary"></i> Dashboard
    </a>

    <?php if ($userRole === 'ADMIN'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">
          Administración
        </span>
      </div>

      <a href="<?= htmlspecialchars($basePath) ?>/users"
        class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-people-fill me-2 text-primary"></i> Usuarios
      </a>
      
      <?php endif; ?>

    <?php if ($userRole === 'ADMIN' || $userRole === 'ACADEMIC'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">
          Gestión Académica
        </span>
      </div>

      <a href="<?= htmlspecialchars($basePath) ?>/diplomados"
         class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
         <i class="bi bi-mortarboard me-2"></i> Diplomados
      </a>
    <?php endif; ?>

  </nav>

  <div class="mt-auto border-top border-secondary">
    <a href="#" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 px-4">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-gear"></i>
        <span>Configuración</span>
      </div>
    </a>
  </div>

</aside>