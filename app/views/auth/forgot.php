<?php
/**
 * MÓDULO: USUARIOS Y ACCESO
 * Archivo: app/views/auth/forgot.php
 * Propósito: Vista profesional para solicitar la recuperación de contraseña.
 */

// Definición de rutas base para activos y enlaces
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$cssAccess = $basePath . '/assets/css/access.css';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DIPLOMATIC · Recuperar Contraseña</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($cssAccess) ?>" rel="stylesheet">
    
    <style>
        .dp-brand { font-weight: 800; letter-spacing: 1px; color: #0d6efd; text-decoration: none; }
        .dp-card { border: none; border-radius: 12px; }
        .dp-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

<div class="dp-auth-container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="container" style="max-width: 450px;">
        
        <div class="text-center mb-4">
            <a href="<?= $basePath ?>/" class="dp-brand fs-2">DIPLOMATIC</a>
        </div>

        <div class="dp-card dp-shadow p-4 p-md-5 bg-white">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">¿Olvidó su contraseña?</h4>
                <p class="text-muted small">Ingrese sus datos para verificar su identidad y enviarle un enlace de recuperación.</p>
            </div>
            
            <form id="formForgot" action="<?= $basePath ?>/forgot-password/submit" method="POST" data-basepath="<?= $basePath ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control py-2" required placeholder="nombre@ejemplo.com">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Documento de Identidad</label>
                    <input type="text" name="document_id" class="form-control py-2" required placeholder="Ingrese su cédula">
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg fs-6 py-2">Enviar Enlace de Recuperación</button>
                </div>
                
                <div class="text-center">
                    <a href="<?= $basePath ?>/" class="text-decoration-none small text-muted">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/register.js"></script>

</body>
</html>