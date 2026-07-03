<?php
/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: app/views/auth/token_error.php
 * Versión: 2.0.0 — Rediseño V2 DIPLOMATIC by Amarellus
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Error de Enlace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/access.css" rel="stylesheet">
</head>
<body>

<div class="dp-wrapper">
  <div class="dp-card" style="text-align:center">

    <div class="dp-brand">DIPLOMATIC</div>

    <i class="bi bi-exclamation-triangle" style="font-size:52px;color:#dc3545;margin:20px 0 12px"></i>

    <div style="font-size:20px;font-weight:700;color:#1a1a1a;margin-bottom:8px">Enlace no válido</div>
    <p class="dp-desc">Este enlace de activación ya fue utilizado, ha expirado o es incorrecto.</p>

    <a href="<?= $basePath ?>/" class="dp-btn-entrar" style="text-decoration:none;display:block;text-align:center">
      Volver al acceso
    </a>

    <div class="dp-footer" style="margin-top:24px">
      &copy; <?= date('Y') ?> DIPLOMATIC by
      <a href="https://www.amarellus.com" target="_blank" rel="noopener">Amarellus</a>.
      Todos los derechos reservados.
    </div>

  </div>
</div>

</body>
</html>