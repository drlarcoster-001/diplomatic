<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/topnav.php
 * Propósito: Barra superior con menú dinámico según ROL (Admin vs Usuario).
 */

declare(strict_types=1);

$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$userName  = $_SESSION['user']['name'] ?? 'Usuario';
$userEmail = $_SESSION['user']['email'] ?? '';
$userRole  = strtoupper($_SESSION['user']['role'] ?? 'USER'); // Capturamos el Rol
$initials  = '';

if ($userName !== '') {
  $parts = preg_split('/\s+/', trim($userName));
  $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
  $initials = trim($initials) !== '' ? $initials : 'U';
} else {
  $initials = 'U';
}
?>

<nav class="navbar dp-topnav bg-white border-bottom sticky-top">
  <div class="container-fluid px-3 px-md-4 d-flex align-items-center justify-content-between">

    <div class="d-flex align-items-center gap-2">
      <button id="dpBurger" class="dp-burger d-lg-none" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>

      <a class="navbar-brand fw-bold mb-0" href="<?= htmlspecialchars($basePath) ?>/dashboard">
        DIPLOMATIC <span class="text-muted fw-normal" style="font-size:.9rem;">· Panel</span>
      </a>
    </div>

    <div class="dropdown">
      <button class="btn btn-light border dp-userbtn dropdown-toggle"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false">
        <span class="dp-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
        <span class="d-none d-sm-inline"><?= htmlspecialchars($userName) ?></span>
      </button>

      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 280px;">
        <li class="px-3 pt-2 pb-2">
          <div class="d-flex align-items-center gap-2">
            <span class="dp-avatar dp-avatar-lg" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($userName) ?></div>
              <?php if ($userEmail !== ''): ?>
                <div class="text-muted small"><?= htmlspecialchars($userEmail) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </li>

        <li><hr class="dropdown-divider"></li>

        <?php if ($userRole === 'ADMIN'): ?>
            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/settings"><i class="bi bi-gear me-2"></i> Configuración</a></li>
            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/users"><i class="bi bi-shield-lock me-2"></i> Seguridad</a></li>
        <?php else: ?>
            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/profile"><i class="bi bi-person-circle me-2"></i> Mi Perfil</a></li>
            <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/profile/security"><i class="bi bi-key me-2"></i> Cambiar Contraseña</a></li>
        <?php endif; ?>

        <li><hr class="dropdown-divider"></li>

        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($basePath) ?>/logout"><i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión</a></li>
      </ul>
    </div>

  </div>
</nav>