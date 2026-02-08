<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/dashboard/index.php
 * Propósito: Dashboard base (usa layout con sidebar + topnav).
 */

declare(strict_types=1);

$userName = $_SESSION['user']['name'] ?? 'Usuario';

ob_start();
?>
<div class="row">
  <div class="col-12">
    <div class="card dp-shadow">
      <div class="card-body">
        <h4 class="mb-2">Bienvenido, <?= htmlspecialchars($userName) ?></h4>
        <div class="text-muted">Panel principal del sistema (base). Desde aquí vamos habilitando módulos progresivamente.</div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/../layout.php';
