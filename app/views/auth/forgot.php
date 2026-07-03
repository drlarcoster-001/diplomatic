<?php
/**
 * MÓDULO: USUARIOS Y ACCESO
 * Archivo: app/views/auth/forgot.php
 * Versión: 2.0.0 — Rediseño V2 DIPLOMATIC by Amarellus
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Recuperar Contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/access.css" rel="stylesheet">
</head>
<body>

<div class="dp-wrapper">
  <div class="dp-card">

    <div class="dp-brand">DIPLOMATIC</div>
    <div class="dp-tagline">Recuperación de contraseña</div>

    <p class="dp-desc">Ingrese su correo y cédula para verificar su identidad y recibir un enlace de recuperación.</p>

    <form id="formForgot" action="<?= $basePath ?>/forgot-password/submit" method="POST" data-basepath="<?= $basePath ?>">

      <div class="dp-input-group">
        <i class="bi bi-envelope dp-input-icon"></i>
        <input type="email" name="email" class="dp-input" placeholder="Email" required autocomplete="email">
      </div>

      <div class="dp-input-group" style="margin-bottom:28px">
        <i class="bi bi-person-vcard dp-input-icon"></i>
        <input type="text" name="document_id" class="dp-input" placeholder="Cédula / Documento de identidad" required>
      </div>

      <button type="submit" class="dp-btn-entrar">Enviar enlace de recuperación</button>
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
</body>
</html>