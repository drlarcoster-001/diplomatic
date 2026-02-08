<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/topnav.php
 * Propósito: Barra superior (TopNav) con burger responsive y menú de usuario con avatar.
 */

declare(strict_types=1);

$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$userName  = $_SESSION['user']['name'] ?? 'Usuario';
$userEmail = $_SESSION['user']['email'] ?? '';
$initials  = '';

if ($userName !== '') {
  $parts = preg_split('/\s+/', trim($userName));
  $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
  $initials = trim($initials) !== '' ? $initials : 'U';
} else {
  $initials = 'U';
}

/**
 * Nota:
 * Por ahora usamos un avatar tipo "initials".
 * Más adelante lo conectamos a una foto real (ruta en DB o storage) sin romper UI.
 */
?>

<nav class="navbar dp-topnav bg-white border-bottom sticky-top">
  <div class="container-fluid px-3 px-md-4 d-flex align-items-center justify-content-between">

    <div class="d-flex align-items-center gap-2">
      <!-- BURGER: solo móvil/tablet -->
      <button id="dpBurger" class="dp-burger d-lg-none" aria-label="Menú">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <a class="navbar-brand fw-bold mb-0" href="<?= htmlspecialchars($basePath) ?>/dashboard">
        DIPLOMATIC <span class="text-muted fw-normal" style="font-size:.9rem;">· Panel</span>
      </a>
    </div>

    <!-- Usuario -->
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

        <!-- Las rutas pueden existir luego; por ahora dejamos el espacio funcional -->
        <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/user/settings">Configuración</a></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($basePath) ?>/user/security">Seguridad</a></li>

        <li><hr class="dropdown-divider"></li>

        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($basePath) ?>/logout.php">Cerrar sesión</a></li>
      </ul>
    </div>

  </div>
</nav>
