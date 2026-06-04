<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/views/auth/register.php
 * Propósito: Interfaz de registro minimalista sincronizada con tbl_pre_users.
 * VERSIÓN: 1.4.0 - Fix: Eliminación de campos inexistentes en BD (Título/Dirección).
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
<body class="bg-light">

<div class="dp-auth-wrapper">
  <div class="dp-reg-box">
    
    <div class="text-center mb-4">
      <div class="dp-brand fs-2 fw-bold text-dark">DIPLOMATIC</div>
      <div class="dp-subtitle text-muted mt-1">Gestión Universitaria · Registro Institucional</div>
    </div>

    <div class="dp-card bg-white border shadow-sm rounded-4 p-4 p-md-5">
      <div class="mb-4">
        <h1 class="dp-title h4 mb-1 fw-bold">Registro de Usuario</h1>
        <p class="dp-subtitle small text-muted">Complete sus datos para crear su cuenta institucional.</p>
      </div>

      <form id="formRegister" action="<?= $basePath ?>/register/submit" method="POST" novalidate>
        
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Nombres</label>
            <input type="text" name="first_name" class="form-control shadow-sm form-control-caps" 
                   placeholder="EJ: JUAN ALBERTO" required oninput="this.value = this.value.toUpperCase()">
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Apellidos</label>
            <input type="text" name="last_name" class="form-control shadow-sm form-control-caps" 
                   placeholder="EJ: PÉREZ RODRÍGUEZ" required oninput="this.value = this.value.toUpperCase()">
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold">Correo Electrónico</label>
            <input type="email" name="email" class="form-control shadow-sm" placeholder="usuario@correo.com" required>
            <span class="email-note">
                <i class="bi bi-info-circle me-1"></i> Use preferiblemente: 
                <strong>Gmail, Hotmail, Outlook o Yahoo</strong>.
            </span>
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold d-block">Teléfono Móvil</label>
            <input type="tel" id="phone_mask" class="form-control shadow-sm">
            <input type="hidden" name="phone" id="full_phone">
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold">Documento de Identidad (Cédula/DNI/Pasaporte)</label>
            <input type="text" name="document_id" class="form-control shadow-sm" placeholder="Ingrese número de identificación" required>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-bold">Procedencia (Ciudad/Estado)</label>
            <input type="text" name="provenance" class="form-control shadow-sm" 
                  placeholder="EJ: BARQUISIMETO / LARA" required 
                  oninput="this.value = this.value.toUpperCase()">
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-bold">Carrera de Pregrado</label>
            <input type="text" name="undergraduate_degree" class="form-control shadow-sm" 
                  placeholder="EJ: MÉDICO CIRUJANO" required 
                  oninput="this.value = this.value.toUpperCase()">
        </div>

        </div>

        <div class="mt-4 pt-2">
          <button type="submit" id="btnSubmit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
            Finalizar Registro
          </button>
        </div>
      </form>

      <div class="text-center mt-4 border-top pt-4">
        <p class="small text-muted mb-2">¿Ya posees una cuenta en el sistema?</p>
        <a href="<?= $basePath ?>/" class="btn btn-outline-secondary w-100 btn-sm fw-bold rounded-pill">Volver al Acceso</a>
      </div>
    </div>

    <div class="text-center mt-4 small text-muted">
      &copy; <?= date('Y') ?> DIPLOMATIC · Decanato de Ciencias de la Salud UCLA.
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/intlTelInput.min.js"></script>
<script src="<?= $basePath ?>/assets/js/register.js?v=<?= time() ?>"></script>

</body>
</html>