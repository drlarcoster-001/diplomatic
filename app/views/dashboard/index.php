<?php
/**
 * VISTA: DASHBOARD
 * Archivo: app/views/dashboard/index.php
 * Nota: No incluyas header, footer ni layout. El Core lo hace automático.
 */
?>

<div class="row">
  <div class="col-12">
    <div class="card border-0 shadow-sm p-4">
      <div class="d-flex align-items-center mb-3">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
          <i class="bi bi-house-door-fill fs-4"></i>
        </div>
        <div>
          <h2 class="h4 fw-bold mb-0">Bienvenido, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Usuario') ?></h2>
          <p class="text-muted mb-0">Panel principal del sistema.</p>
        </div>
      </div>
      
      <hr class="my-3">

      <div class="alert alert-light border border-primary border-opacity-25 text-primary">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Estado del sistema:</strong> Los módulos de <u>Usuarios</u> y <u>Seguridad</u> están activos.
      </div>
      
      <p class="text-muted small">
        Desde el menú lateral puede acceder a la gestión de usuarios. Próximamente se habilitarán los módulos académicos.
      </p>
    </div>
  </div>
</div>