<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/auth/login.php
 * Propósito: pantalla de acceso (Login) responsive con Bootstrap + estilos del módulo.
 * Nota: Enlaces "Registrarme" y "Olvidé mi contraseña" son UI en esta etapa (lógica se implementa progresivamente).
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';

$registerUrl = $basePath . '/register';
$forgotUrl   = $basePath . '/forgot-password';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Acceso</title>

  <!-- Bootstrap (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- CSS del Módulo 1 -->
  <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
</head>
<body>

<div class="dp-auth-container px-3">
  <div class="container" style="max-width: 980px;">
    <div class="row g-4 align-items-center justify-content-center">

      <!-- Columna de identidad / mensaje institucional -->
      <div class="col-12 col-lg-6">
        <div class="mb-4">
          <div class="dp-brand fs-3">DIPLOMATIC</div>
          <div class="dp-subtitle mt-2">
            Sistema de Gestión de Diplomados · Acceso institucional
          </div>
        </div>

        <div class="bg-white border rounded-4 p-4 dp-shadow">
          <div class="dp-title h5 mb-2">Ingreso al sistema</div>
          <div class="dp-subtitle mb-0">
            Ingrese sus credenciales para acceder al dashboard. En esta etapa el sistema está limitado al acceso y navegación base.
          </div>
        </div>

        <div class="dp-footer mt-3">
          Consejo: utilice un correo válido y conserve sus credenciales de forma segura.
        </div>
      </div>

      <!-- Columna del formulario -->
      <div class="col-12 col-lg-5">
        <div class="dp-card dp-shadow p-4 p-md-5">

          <div class="d-flex align-items-start justify-content-between mb-3">
            <div>
              <div class="dp-title h4 mb-1">Acceso</div>
              <div class="dp-subtitle">Autenticación de usuario</div>
            </div>
            <span class="badge text-bg-light border">Módulo 1</span>
          </div>

          <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
              <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
          <?php endif; ?>

          <form method="POST" action="<?= htmlspecialchars($basePath) ?>/login" novalidate>
            <div class="mb-3">
              <label class="form-label">Correo</label>
              <input type="email" name="email" class="form-control" placeholder="usuario@correo.com" required autocomplete="username">
            </div>

            <div class="mb-2">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me" disabled>
                <label class="form-check-label dp-subtitle" for="rememberMe">
                  Recordarme <span class="text-muted">(próximamente)</span>
                </label>
              </div>

              <a class="text-decoration-none" href="<?= htmlspecialchars($forgotUrl) ?>">
                ¿Olvidé mi contraseña?
              </a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
              Entrar
            </button>
          </form>

          <div class="text-center mt-4">
            <div class="dp-subtitle mb-2">¿No tienes cuenta?</div>
            <a class="btn btn-outline-secondary w-100" href="<?= htmlspecialchars($registerUrl) ?>">
              Registrarme
            </a>
          </div>

          <div class="dp-footer mt-4 text-center">
            DIPLOMATIC · Usuarios, Roles y Acceso
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JS del módulo (lo crearemos) -->
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/access.js"></script>

</body>
</html>
