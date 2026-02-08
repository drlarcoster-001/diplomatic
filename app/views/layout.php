<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/layout.php
 * Propósito: Layout del panel (Sidebar + TopNav + Contenido). Carga CSS/JS del panel.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$content  = $content ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Panel</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- CSS Panel (único) -->
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/panel.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body class="bg-light">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Overlay -->
  <div id="dpOverlay" class="dp-overlay" aria-hidden="true"></div>

  <main class="dp-main">
    <?php include __DIR__ . '/topnav.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4">
      <?= $content ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- JS Panel (único) -->
  <script src="<?= htmlspecialchars($basePath) ?>/assets/js/panel.js?v=<?= time() ?>"></script>
</body>
</html>
