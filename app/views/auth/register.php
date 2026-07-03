<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/views/auth/register.php
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
  <title>DIPLOMATIC · Registro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/intlTelInput.min.css">
  <link href="<?= $basePath ?>/assets/css/access.css" rel="stylesheet">
  <link href="<?= $basePath ?>/assets/css/register.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<div class="dp-wrapper" style="padding: 32px 16px;">
  <div class="dp-card" style="max-width: 560px;">

    <div class="dp-brand">DIPLOMATIC</div>
    <div class="dp-tagline">Registro de usuario</div>

    <form id="formRegister" action="<?= $basePath ?>/register/submit" method="POST" novalidate>

      <div class="row g-0">

        <div class="col-md-6 pe-md-2">
          <div class="dp-input-group">
            <i class="bi bi-person dp-input-icon"></i>
            <input type="text" name="first_name" class="dp-input"
                   placeholder="Nombres"
                   required oninput="this.value = this.value.toUpperCase()">
          </div>
        </div>

        <div class="col-md-6 ps-md-2">
          <div class="dp-input-group">
            <i class="bi bi-person dp-input-icon"></i>
            <input type="text" name="last_name" class="dp-input"
                   placeholder="Apellidos"
                   required oninput="this.value = this.value.toUpperCase()">
          </div>
        </div>

        <div class="col-12">
          <div class="dp-input-group">
            <i class="bi bi-envelope dp-input-icon"></i>
            <input type="email" name="email" class="dp-input"
                   placeholder="Correo electrónico" required>
          </div>
          <small class="dp-hint"><i class="bi bi-info-circle me-1"></i>Use preferiblemente Gmail, Hotmail, Outlook o Yahoo.</small>
        </div>

        <div class="col-12">
          <div class="dp-input-group" style="margin-top:12px">
            <input type="tel" id="phone_mask" class="dp-input" style="padding-left:0" placeholder="Teléfono móvil">
            <input type="hidden" name="phone" id="full_phone">
          </div>
        </div>

        <div class="col-12">
          <div class="dp-input-group">
            <i class="bi bi-person-vcard dp-input-icon"></i>
            <input type="text" name="document_id" class="dp-input"
                   placeholder="Cédula / DNI / Pasaporte" required>
          </div>
        </div>

        <div class="col-md-6 pe-md-2">
          <div class="dp-input-group">
            <i class="bi bi-geo-alt dp-input-icon"></i>
            <input type="text" name="provenance" class="dp-input"
                   placeholder="Procedencia (ciudad/estado)"
                   required oninput="this.value = this.value.toUpperCase()">
          </div>
        </div>

        <div class="col-md-6 ps-md-2">
          <div class="dp-input-group">
            <i class="bi bi-mortarboard dp-input-icon"></i>
            <input type="text" name="undergraduate_degree" class="dp-input"
                   placeholder="Carrera de pregrado"
                   required oninput="this.value = this.value.toUpperCase()">
          </div>
        </div>

      </div>

      <div style="margin-top: 28px;">
        <button type="submit" id="btnSubmit" class="dp-btn-entrar">Finalizar registro</button>
      </div>

    </form>

    <a href="<?= $basePath ?>/" class="dp-btn-registro">Volver al acceso</a>

    <div class="dp-footer">
      &copy; <?= date('Y') ?> DIPLOMATIC by
      <a href="https://www.amarellus.com" target="_blank" rel="noopener">Amarellus</a>.
      Todos los derechos reservados.
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/intlTelInput.min.js"></script>
<script src="<?= $basePath ?>/assets/js/register.js?v=<?= time() ?>"></script>
</body>
</html>