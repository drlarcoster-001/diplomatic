<?php
/**
 * MÓDULO: USUARIOS Y ACCESO
 * Archivo: app/views/auth/password_reset.php
 * Propósito: Vista de restablecimiento de contraseña tras validación de token de recuperación.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Nueva Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
  <style>
      .dp-brand { font-weight: 800; letter-spacing: 2px; color: #0d6efd; text-decoration: none; }
  </style>
</head>
<body>
<div class="dp-auth-container px-3 d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="container" style="max-width: 450px;">
    
    <div class="text-center mb-4">
      <a href="<?= $basePath ?>/" class="dp-brand fs-3">DIPLOMATIC</a>
    </div>

    <div class="dp-card dp-shadow p-4 p-md-5 bg-white">
      <div class="dp-title h4 fw-bold mb-1">Nueva Contraseña</div>
      <p class="dp-subtitle mb-4 text-muted">Defina su nueva clave de acceso para continuar.</p>
      
      <form id="formPassword" action="<?= $basePath ?>/register/create-password" method="POST" data-basepath="<?= $basePath ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Correo Electrónico</label>
          <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($email) ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nueva Clave</label>
          <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres">
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Confirmar Clave</label>
          <input type="password" id="confirm_password" class="form-control" required minlength="8" placeholder="Repita su clave">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="btnPass">Actualizar Contraseña</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
</body>
</html>