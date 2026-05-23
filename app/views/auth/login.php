<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/auth/login.php
 * Propósito: Pantalla de acceso centrada y simplificada.
 */

if (!isset($basePath)) {
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Acceso</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/access.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* Asegura el centrado absoluto en el viewport */
    body, html {
      height: 100%;
    }
    .dp-auth-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .dp-login-box {
      width: 100%;
      max-width: 450px;
    }
  </style>
</head>
<body class="bg-light">

<div class="dp-auth-wrapper">
  <div class="dp-login-box">
    
    <div class="text-center mb-4">
      <div class="dp-brand fs-2 fw-bold text-dark">DIPLOMATIC</div>
      <div class="dp-subtitle text-muted mt-1">Sistema de Gestión de Diplomados · Acceso institucional</div>
    </div>

    <div class="dp-card dp-shadow bg-white border rounded-4 p-4 p-md-5">
      <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
          <div class="dp-title h4 mb-1">Acceso</div>
          <div class="dp-subtitle small text-muted">Autenticación de usuario</div>
        </div>
        <span class="badge text-bg-light border">v1.0</span>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2 small" role="alert">
          <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <form method="POST" action="/diplomatic/public/login" novalidate>
        <div class="mb-3">
          <label class="form-label small fw-bold">Correo</label>
          <input type="email" name="email" class="form-control shadow-sm" placeholder="usuario@correo.com" required autocomplete="username">
        </div>

        <div class="mb-2">
          <label class="form-label small fw-bold">Contraseña</label>
          <div class="input-group shadow-sm">
            <input type="password" id="passwordInput" name="password" class="form-control border-end-0" placeholder="••••••••" required autocomplete="current-password">
            <button class="btn btn-outline-secondary bg-white border-start-0" type="button" id="togglePasswordBtn" style="border-color: #dee2e6;">
              <i class="bi bi-eye text-muted" id="togglePasswordIcon"></i>
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember">
            <label class="form-check-label small text-muted" for="rememberMe">Recordarme</label>
          </div>
          <a class="text-decoration-none small" href="/diplomatic/public/forgot-password">¿Olvidé mi contraseña?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Entrar</button>
      </form>

      <div class="text-center mt-4">
        <div class="small text-muted mb-2">¿No tienes cuenta?</div>
        <a class="btn btn-outline-secondary w-100 btn-sm fw-bold" href="/diplomatic/public/register">Registrarme</a>
      </div>
    </div>

    <div class="text-center mt-4 small text-muted">
      &copy; <?= date('Y') ?> DIPLOMATIC. Todos los derechos reservados.
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/access.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('passwordInput');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput) {
      togglePasswordBtn.addEventListener('click', function () {
        // Alternar el tipo de input entre 'password' y 'text'
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        
        // Alternar el ícono (ojo normal / ojo tachado)
        if (isPassword) {
          togglePasswordIcon.classList.remove('bi-eye');
          togglePasswordIcon.classList.add('bi-eye-slash');
        } else {
          togglePasswordIcon.classList.remove('bi-eye-slash');
          togglePasswordIcon.classList.add('bi-eye');
        }
      });
    }
  });
</script>

</body>
</html>