<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/views/auth/password_reset.php
 * Versión: 2.0.0 — Rediseño V2 DIPLOMATIC by Amarellus
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Nueva Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/access.css" rel="stylesheet">
</head>
<body>

<div class="dp-wrapper">
  <div class="dp-card">

    <div class="dp-brand">DIPLOMATIC</div>
    <div class="dp-tagline">Nueva contraseña</div>
    <p class="dp-desc">Defina su nueva clave de acceso institucional para continuar.</p>

    <form id="formPassword" action="<?= $basePath ?>/register/create-password" method="POST" data-basepath="<?= $basePath ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="dp-input-group">
        <i class="bi bi-envelope dp-input-icon"></i>
        <input type="text" class="dp-input" value="<?= htmlspecialchars($email) ?>" readonly>
      </div>

      <div class="dp-input-group">
        <i class="bi bi-lock dp-input-icon"></i>
        <input type="password" name="password" id="password" class="dp-input dp-input-pass"
               placeholder="Nueva contraseña" required minlength="8">
        <button type="button" class="dp-eye-btn" id="toggleBtn1">
          <i class="bi bi-eye" id="toggleIcon1"></i>
        </button>
      </div>

      <div class="dp-input-group" style="margin-bottom:28px">
        <i class="bi bi-lock-fill dp-input-icon"></i>
        <input type="password" id="confirm_password" class="dp-input dp-input-pass"
               placeholder="Confirmar contraseña" required minlength="8">
        <button type="button" class="dp-eye-btn" id="toggleBtn2">
          <i class="bi bi-eye" id="toggleIcon2"></i>
        </button>
      </div>

      <button type="submit" class="dp-btn-entrar">Actualizar contraseña</button>
    </form>

    <a href="<?= $basePath ?>/" class="dp-btn-registro">Volver al acceso</a>

    <div class="dp-footer">
      &copy; <?= date('Y') ?> DIPLOMATIC by
      <a href="https://www.amarellus.com" target="_blank" rel="noopener">Amarellus</a>.
      Todos los derechos reservados.
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
<script>
  function togglePass(btnId, iconId, inputId) {
    document.getElementById(btnId).addEventListener('click', function () {
      const inp = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      const isPass = inp.type === 'password';
      inp.type = isPass ? 'text' : 'password';
      icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }
  togglePass('toggleBtn1', 'toggleIcon1', 'password');
  togglePass('toggleBtn2', 'toggleIcon2', 'confirm_password');
</script>
</body>
</html>