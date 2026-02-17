<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/auth/forgot.php
 * Propósito: pantalla de recuperación de contraseña.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Recuperar Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
</head>
<body>
<div class="dp-auth-container px-3">
  <div class="container" style="max-width: 980px;">
    <div class="row g-4 align-items-center justify-content-center">
      <div class="col-12 col-lg-5">
        <div class="dp-card dp-shadow p-4 p-md-5">
          <div class="dp-title h4 mb-1">Recuperación</div>
          <p class="dp-subtitle mb-4">Valide su identidad para restablecer el acceso.</p>
          
          <form id="formForgot" action="<?= $basePath ?>/forgot-password/submit" method="POST" data-basepath="<?= $basePath ?>">
            <div class="mb-3">
              <label class="form-label">Documento de Identidad</label>
              <input type="text" name="document_id" class="form-control" placeholder="Ingrese su documento" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Correo Electrónico</label>
              <input type="email" name="email" class="form-control" placeholder="usuario@correo.com" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" id="btnForgot">Enviar Enlace</button>
          </form>
          
          <div class="text-center mt-4">
            <a href="<?= $basePath ?>/" class="dp-subtitle text-decoration-none">Volver al Acceso</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>
</body>
</html>