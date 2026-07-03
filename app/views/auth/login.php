<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/auth/login.php
 * Versión: 2.0.0 — Rediseño V2 DIPLOMATIC by Amarellus
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/access.css" rel="stylesheet">
</head>
<body>

<div class="dp-wrapper">
  <div class="dp-card">

    <div class="dp-brand">DIPLOMATIC</div>
    <div class="dp-tagline">Sistema de Gestión de Diplomados</div>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="dp-alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="/diplomatic/public/login" novalidate>

      <div class="dp-input-group">
        <i class="bi bi-envelope dp-input-icon"></i>
        <input type="email" name="email" class="dp-input"
               placeholder="Email"
               required autocomplete="username">
      </div>

      <div class="dp-input-group">
        <i class="bi bi-lock dp-input-icon"></i>
        <input type="password" id="passwordInput" name="password"
               class="dp-input dp-input-pass"
               placeholder="Contraseña"
               required autocomplete="current-password">
        <button type="button" class="dp-eye-btn" id="togglePasswordBtn">
          <i class="bi bi-eye" id="togglePasswordIcon"></i>
        </button>
      </div>

      <div class="dp-row">
        <label class="dp-check-label">
          <input type="checkbox" name="remember" value="1">
          Recordarme
        </label>
        <a href="/diplomatic/public/forgot-password" class="dp-forgot">¿Olvidé mi contraseña?</a>
      </div>

      <button type="submit" class="dp-btn-entrar">Entrar</button>
    </form>

    <a href="/diplomatic/public/register" class="dp-btn-registro">Registrarme</a>

    <div class="dp-footer">
      &copy; <?= date('Y') ?> DIPLOMATIC by
      <a href="https://www.amarellus.com" target="_blank" rel="noopener">Amarellus</a>.
      Todos los derechos reservados.
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/access.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const btn  = document.getElementById('togglePasswordBtn');
    const inp  = document.getElementById('passwordInput');
    const icon = document.getElementById('togglePasswordIcon');
    if (btn && inp) {
      btn.addEventListener('click', function () {
        const isPass = inp.type === 'password';
        inp.type = isPass ? 'text' : 'password';
        icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    }
  });
</script>
</body>
</html>