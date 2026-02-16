<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/auth/password.php
 * Propósito: Pantalla para establecer contraseña tras validación de token.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
</head>
<body>
<div class="dp-auth-container px-3">
  <div class="container" style="max-width: 980px;">
    <div class="row g-4 align-items-center justify-content-center">
      <div class="col-12 col-lg-5">
        <div class="dp-card dp-shadow p-4 p-md-5">
          <div class="dp-title h4 mb-1">Crea tu contraseña</div>
          <p class="dp-subtitle mb-4">Para: <b><?= htmlspecialchars($email ?? '') ?></b></p>
          <form id="formPassword" action="<?= $basePath ?>/register/create-password" method="POST" data-basepath="<?= $basePath ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            <div class="mb-3"><label class="form-label">Nueva Contraseña</label><input type="password" id="password" name="password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Confirmar Contraseña</label><input type="password" id="confirm_password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100 py-2" id="btnPass">Validar y Crear Cuenta</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
</body>
</html>