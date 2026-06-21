<?php
/**
 * MÓDULO: CORRESPONDENCIA / VERIFICACIÓN
 * ARCHIVO: public/verificar_correspondencia.php
 * PROPÓSITO: Validador público para documentos de Correspondencia (cartas,
 *            memos, oficios, actas, reconocimientos, constancias). Página
 *            independiente de verificar.php (que sigue siendo exclusiva de
 *            Constancias de Estudio/Inscripción) para no arriesgar nada
 *            que ya funciona.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

require_once __DIR__ . '/../app/services/CorrespondenciaValidator.php';

$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_SPECIAL_CHARS);
$doc  = $code ? CorrespondenciaValidator::validate($code) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Digital | UCLA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root { --brand-color: #6f42c1; }
        body { background-color: #f4f7f9; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .main-card { max-width: 480px; margin: 60px auto; border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; background: white; }
        .header { background: var(--brand-color); color: white; padding: 40px 20px; text-align: center; }
        .ucla-logo { height: 75px; margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
        .status-badge { background: #e8f5e9; color: #2e7d32; padding: 12px 25px; border-radius: 50px; font-weight: 700; border: 1px solid #c8e6c9; margin-bottom: 30px; display: inline-block; }
        .info-panel { background: #f8f9fa; border-radius: 15px; padding: 25px; text-align: left; border-left: 5px solid var(--brand-color); }
        .data-label { color: #8a99af; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .data-value { color: #1a202c; font-weight: 600; font-size: 1.1rem; margin-bottom: 15px; line-height: 1.2; }
        .footer-note { font-size: 0.85rem; color: #6c757d; padding: 0 20px; line-height: 1.4; }
    </style>
</head>
<body>

<div class="container px-3">
    <div class="card main-card">
        <div class="header">
            <img src="assets/uploads/logos/logo-ucla.png" class="ucla-logo" alt="UCLA" onerror="this.style.display='none'">
            <h5 class="mb-0 fw-bold">DOCUMENTO DE CORRESPONDENCIA</h5>
            <p class="small mb-0 opacity-75">Sistema de Validación Digital</p>
        </div>

        <div class="card-body p-4 text-center">
            <?php if ($doc): ?>
                <div class="status-badge">
                    <i class="bi bi-patch-check-fill me-2"></i> DOCUMENTO AUTÉNTICO
                </div>

                <div class="info-panel shadow-sm">
                    <?php if (!empty($doc['titular'])): ?>
                    <div class="data-label">Titular:</div>
                    <div class="data-value"><?= htmlspecialchars(strtoupper($doc['titular'])) ?></div>
                    <?php endif; ?>

                    <div class="data-label">Tipo de Documento:</div>
                    <div class="data-value"><?= htmlspecialchars($doc['tipo_documento']) ?></div>

                    <div class="data-label">Plantilla:</div>
                    <div class="data-value"><?= htmlspecialchars($doc['plantilla_nombre']) ?></div>

                    <div class="data-label">Código de Verificación:</div>
                    <div class="data-value text-primary"><?= htmlspecialchars($doc['code']) ?></div>

                    <div class="data-label">Fecha de Emisión:</div>
                    <div class="data-value mb-0"><?= date('d/m/Y', strtotime($doc['generated_at'])) ?></div>
                </div>

                <div class="footer-note mt-4">
                    <p>Este documento digital ha sido verificado satisfactoriamente. La información presentada coincide con los archivos vigentes de la Coordinación de Extensión.</p>
                </div>

            <?php else: ?>
                <div class="py-5">
                    <i class="bi bi-shield-lock-fill text-danger display-1"></i>
                    <h4 class="mt-3 fw-bold text-danger">VERIFICACIÓN INVÁLIDA</h4>
                    <p class="text-muted px-4">El código consultado no existe o no corresponde a un documento oficial emitido por la institución.</p>
                    <hr class="mx-5">
                    <small class="text-muted">Si considera que esto es un error, contacte a soporte técnico.</small>
                </div>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-top">
                <small class="text-muted">Decanato de Ciencias de la Salud &bull; <?= date('d/m/Y') ?></small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>