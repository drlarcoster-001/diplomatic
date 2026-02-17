<?php
/**
 * Archivo: app/views/auth/password_forgot.php
 * Propósito: Vista de restablecimiento de contraseña.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>DIPLOMATIC · Nueva Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $basePath ?>/assets/css/access.css" rel="stylesheet">
</head>
<body>
<div class="dp-auth-container px-3">
  <div class="dp-card dp-shadow p-5 mx-auto" style="max-width: 450px;">
    <div class="dp-title h4">Nueva Contraseña</div>
    <p class="dp-subtitle mb-4">Defina su nueva clave de acceso.</p>
    <form id="formPassword" action="<?= $basePath ?>/register/create-password" method="POST" data-basepath="<?= $basePath ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-3"><label class="form-label">Correo</label><input type="text" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled></div>
      <div class="mb-3"><label class="form-label">Nueva Clave</label><input type="password" name="password" id="password" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Confirmar</label><input type="password" id="confirm_password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary w-100" id="btnPass">Actualizar Contraseña</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
</body>
</html>