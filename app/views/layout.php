<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/views/layout.php
 * Propósito: Layout base para el sistema (sidebar + topnav).
 * Nota: Este archivo es el layout común donde se incluirán el sidebar y el topnav.
 */

declare(strict_types=1);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIPLOMATIC · Dashboard</title>

  <!-- Bootstrap CSS (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- CSS del Módulo 1 -->
  <link href="<?= $basePath ?>/assets/css/access.css" rel="stylesheet">
  <link href="<?= $basePath ?>/assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">

  <!-- INCLUYE EL SIDEBAR -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="main-content">
    <!-- INCLUYE EL TOPNAV -->
    <?php include __DIR__ . '/topnav.php'; ?>

    <!-- CONTENIDO DINÁMICO (dependerá de la vista) -->
    <div class="container py-5">
      <?php echo $content; ?>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- JS del módulo -->
  <script src="<?= $basePath ?>/assets/js/access.js"></script>
</body>
</html>
