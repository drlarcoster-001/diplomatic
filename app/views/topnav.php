<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/topnav.php
 * Propósito: Barra de navegación superior.
 * Nota: Contendrá el nombre del sistema a la izquierda y el nombre del usuario a la derecha.
 */

declare(strict_types=1);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard">DIPLOMATIC</a>
    <div class="d-flex align-items-center">
      <!-- Nombre del usuario -->
      <div class="dropdown">
        <button class="btn btn-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Usuario') ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          <li><a class="dropdown-item" href="#">Configuración</a></li>
          <li><a class="dropdown-item" href="#">Seguridad</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/logout">Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
