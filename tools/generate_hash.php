<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: tools/generate_hash.php
 * Propósito: herramienta local para generar password_hash() y usarlo en seeds SQL.
 * Uso: abrir en el navegador, colocar una clave, copiar el hash, y luego ELIMINAR este archivo.
 */

declare(strict_types=1);

$password = isset($_GET['p']) ? (string)$_GET['p'] : '';
$hash = '';

if ($password !== '') {
  $hash = password_hash($password, PASSWORD_DEFAULT);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Generador de Hash · DIPLOMATIC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 720px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h5 mb-2">Generador de password_hash()</h1>
        <p class="text-muted mb-4">
          Uso local: escriba una contraseña y copie el hash para pegarlo en su seed SQL.
          <br><strong>Al finalizar, elimine este archivo.</strong>
        </p>

        <form method="GET">
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="text" name="p" class="form-control" value="<?= htmlspecialchars($password) ?>" placeholder="Ej: Admin123*">
          </div>
          <button class="btn btn-primary">Generar hash</button>
        </form>

        <?php if ($hash !== ''): ?>
          <hr>
          <div class="mb-2"><strong>Hash generado</strong></div>
          <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($hash) ?></textarea>
          <div class="text-muted small mt-2">
            Copie el valor completo (incluyendo $2y$...) y péguelo en el INSERT de tbl_users.
          </div>
        <?php endif; ?>

      </div>
    </div>

    <div class="text-center text-muted small mt-3">
      DIPLOMATIC · Herramienta temporal
    </div>
  </div>
</body>
</html>
