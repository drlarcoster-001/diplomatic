<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/sidebar.php
 * Propósito: Sidebar para el dashboard.
 * Nota: Este archivo contiene la navegación lateral.
 */

declare(strict_types=1);
?>

<div class="d-flex" id="sidebar-wrapper">
  <div class="bg-dark text-white" style="width: 250px; height: 100vh;">
    <div class="sidebar-header py-3 px-4">
      <h5 class="text-center">MENÚ</h5>
    </div>
    <div class="list-group list-group-flush">
      <a href="/dashboard" class="list-group-item list-group-item-action bg-dark text-white">
        <i class="bi bi-house-door"></i> Dashboard
      </a>
      <a href="#" class="list-group-item list-group-item-action bg-dark text-white">
        <i class="bi bi-person-circle"></i> Perfil
      </a>
      <a href="#" class="list-group-item list-group-item-action bg-dark text-white">
        <i class="bi bi-cogs"></i> Configuración
      </a>
    </div>
  </div>
</div>
