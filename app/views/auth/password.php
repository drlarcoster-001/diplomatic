<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/views/auth/password.php
 * Propósito: Interfaz para la definición de credenciales de acceso y finalización del proceso de registro o recuperación de cuenta.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Establecer Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
</head>
<body>
<div class="dp-auth-container px-3">
  <div class="container" style="max-width: 450px;">
    
    <div class="text-center mb-4">
      <a href="<?= $basePath ?>/" class="text-decoration-none">
        <span class="dp-brand fs-3 text-primary" style="letter-spacing: 2px; font-weight: 700;">DIPLOMATIC</span>
      </a>
    </div>

    <div class="dp-card dp-shadow p-4 p-md-5">
      <div class="dp-title h4 mb-1">Finalizar Proceso</div>
      <p class="dp-subtitle mb-4">Establezca una clave segura para su cuenta institucional.</p>
      
      <form id="formPassword" action="<?= $basePath ?>/register/create-password" method="POST" data-basepath="<?= $basePath ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        
        <div class="mb-3">
          <label class="form-label">Correo Electrónico</label>
          <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($email) ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label">Nueva Contraseña</label>
          <input type="password" name="password" id="password" class="form-control" required minlength="8">
          <div class="form-text text-muted small">Mínimo 8 caracteres.</div>
        </div>

        <div class="mb-4">
          <label class="form-label">Confirmar Contraseña</label>
          <input type="password" id="confirm_password" class="form-control" required minlength="8">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">Validar y Finalizar</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
</body>
</html>