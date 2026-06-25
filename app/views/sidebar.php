<?php
/**
 * MÓDULO: PANEL (SIDEBAR)
 * ARCHIVO: app/views/sidebar.php
 * PROPÓSITO: Menú lateral jerárquico. Orden: Dashboard -> Administración -> Académico -> Financiero -> Operativo -> Gerencial.
 * VERSIÓN: 1.9.6 - Refinamiento estético: Etiqueta de versión en Amarillo Dorado para legibilidad premium.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$userRole = strtoupper(trim($_SESSION['user']['role'] ?? '')); 
?>

<aside id="dpSidebar" class="dp-sidebar bg-dark text-white d-flex flex-column">
  
  <div class="py-3 px-4 border-bottom border-secondary">
    <div class="fw-bold d-flex align-items-center gap-2">
      <i class="bi bi-grid-fill text-primary"></i> 
      <span>NAVEGACIÓN</span>
    </div>
  </div>

  <nav class="list-group list-group-flush mt-2 flex-grow-1">
    
    <a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 px-4">
      <i class="bi bi-speedometer2 me-2 text-secondary"></i> Dashboard
    </a>

    <?php if ($userRole === 'ADMIN'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Administración</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/users" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-people-fill me-2 text-primary"></i> Usuarios
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'ADMIN' || $userRole === 'OPERATOR' || $userRole === 'ACADEMIC'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gestión Académica</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/academic" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-mortarboard-fill me-2 text-warning"></i> Panel Académico
      </a>
      <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-briefcase-fill me-2 text-info"></i> Panel Administrativo
      </a>
    <?php endif; ?>

<?php if ($userRole === 'ADMIN' || $userRole === 'OPERATOR'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gestión de Recursos</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/resources" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-person-lines-fill me-2" style="color: #a855f7;"></i> Panel de Recursos
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'ADMIN' || $userRole === 'OPERATOR'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gestión Financiera</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/financial" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-cash-coin me-2 text-success"></i> Panel Financiero
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'ADMIN' || $userRole === 'OPERATOR'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gestión Operativa</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/operational" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-layers-half me-2 text-danger"></i> Panel Operativo
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'ADMIN'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gestión General</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/managerial" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-pie-chart-fill me-2 text-warning"></i> Panel Gerencial
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'PARTICIPANT'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Estudiantes</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/students" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-person-badge-fill me-2 text-primary"></i> Panel Estudiantil
      </a>
    <?php endif; ?>

    <?php if ($userRole === 'PROFESOR'): ?>
      <div class="px-4 mt-4 mb-2">
        <span class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Docencia</span>
      </div>
      <a href="<?= htmlspecialchars($basePath) ?>/professor" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2 px-4">
          <i class="bi bi-person-video2 me-2 text-warning"></i> Panel Docente
      </a>
    <?php endif; ?>

  </nav>

  <div class="mt-auto border-top border-secondary pb-2">
    <?php if ($userRole === 'ADMIN'): ?>
      <a href="<?= htmlspecialchars($basePath) ?>/settings" 
         class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-gear"></i>
          <span>Configuración</span>
        </div>
      </a>
    <?php endif; ?>

    <div class="text-center pt-2">
        <a href="https://www.amarellus.com" target="_blank" class="text-decoration-none" style="outline: none;">
            <span class="text-warning" 
                  style="font-size: 0.6rem; opacity: 0.8; cursor: pointer; letter-spacing: 1px; user-select: none; color: #ffc107 !important;"
                  data-bs-toggle="tooltip" 
                  data-bs-placement="top" 
                  data-bs-html="true"
                  title="Desarrollado por Amarellus (www.amarellus.com)">
                V.20260412.352.42.V1.0
            </span>
        </a>
    </div>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>