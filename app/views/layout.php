<?php
/**
 * VISTA MAESTRA (LAYOUT)
 * Archivo: app/views/layout.php
 * Propósito: Estructura base (Head, Sidebar, Topnav) para todas las páginas internas.
 */

declare(strict_types=1);

// Definir ruta base
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$content  = $content ?? ''; // Contenido inyectado desde la vista
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Panel de Gestión</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/panel.css?v=<?= time() ?>" rel="stylesheet">
  
  <link href="<?= htmlspecialchars($basePath) ?>/assets/css/users.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body class="bg-light">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <div id="dpOverlay" class="dp-overlay" aria-hidden="true"></div>

  <main class="dp-main">
    
    <?php include __DIR__ . '/topnav.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4">
      <?= $content ?>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script src="<?= htmlspecialchars($basePath) ?>/assets/js/panel.js?v=<?= time() ?>"></script>
</body>
</html>