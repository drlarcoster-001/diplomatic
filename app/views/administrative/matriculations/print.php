<?php
/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: app/views/administrative/matriculations/print.php
 * PROPÓSITO: Plantilla de impresión nativa en lienzo blanco para el listado de control de asistencia.
 * VERSIÓN: 2.4.0 - Incorporación de encabezado institucional formal con logos de UCLA y Medicina.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<style>
    /* Apagamos cualquier elemento de navegación que el framework inyecte */
    header, nav, .navbar, .topbar, .sidebar, footer, #header, #sidebar, .logo {
        display: none !important;
    }
    /* Reseteamos los márgenes por si el sistema empuja el contenido hacia abajo */
    body, .main-content, .wrapper, .content-wrapper, #app {
        padding: 0 !important;
        margin: 0 !important;
        background-color: #e9ecef !important;
    }
</style>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia - <?= htmlspecialchars($data['header']['cohort_code'] ?? 'Cohorte') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_matriculations_print.css">
</head>
<body>

    <div class="text-center mt-4 mb-4 no-print">
        <button id="btn-print-action" class="btn btn-primary px-4 shadow-sm fw-bold">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill me-2" viewBox="0 0 16 16"><path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/><path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/></svg>
            Imprimir Formato
        </button>
        <button id="btn-close-action" class="btn btn-secondary px-4 shadow-sm fw-bold ms-2">
            Cerrar Pestaña
        </button>
    </div>

    <div class="hoja-impresion">
        
        <div class="row border-bottom border-dark pb-3 mb-4 align-items-center">
            <div class="col-2 text-center">
                <img src="<?= $basePath ?>/assets/uploads/logos/logo-ucla.png" style="width: 70px; height: auto;" alt="Logo UCLA">
            </div>
            <div class="col-8 text-center">
                <div class="fw-bold" style="font-size: 11pt; line-height: 1.2;">
                    UNIVERSIDAD CENTROCCIDENTAL “LISANDRO ALVARADO”<br>
                    DECANATO DE CIENCIAS DE LA SALUD<br>
                    COORDINACIÓN DE EXTENSIÓN
                </div>
                <h5 class="fw-bold mb-0 text-uppercase mt-3" style="letter-spacing: 1px;">LISTADO DE INSCRITOS</h5>
            </div>
            <div class="col-2 text-center">
                <img src="<?= $basePath ?>/assets/uploads/logos/logo-medicina.jpg" style="width: 70px; height: auto;" alt="Logo Medicina">
            </div>
        </div>

        <div class="mb-4">
            <table class="table table-sm table-borderless mb-0" style="font-size: 14px;">
                <tr>
                    <td width="15%" class="fw-bold">PROGRAMA:</td>
                    <td class="border-bottom border-dark text-uppercase"><?= htmlspecialchars($data['header']['diplomado_name'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="fw-bold">COHORTE:</td>
                    <td class="border-bottom border-dark"><?= htmlspecialchars($data['header']['cohort_code'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="fw-bold">FECHA:</td>
                    <td class="border-bottom border-dark">______ / ______ / 202___</td>
                </tr>
                <tr>
                    <td class="fw-bold">FACILITADOR:</td>
                    <td class="border-bottom border-dark"></td>
                </tr>
            </table>
        </div>

        <table class="table table-bordered border-dark text-center align-middle">
            <thead class="table-light print-bg-light text-uppercase">
                <tr>
                    <th width="5%" class="py-2">N°</th>
                    <th width="15%" class="py-2">Cédula</th>
                    <th width="40%" class="py-2">Apellidos y Nombres</th>
                    </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['students'])): ?>
                    <?php $contador = 1; ?>
                    <?php foreach ($data['students'] as $student): ?>
                        <tr>
                            <td class="fw-bold"><?= $contador++ ?></td>
                            <td><?= htmlspecialchars($student['cedula']) ?></td>
                            <td class="text-start ps-3 text-uppercase">
                                <?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?>
                            </td>
                            </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-5 text-muted">No hay registros de estudiantes para imprimir.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-5 pt-5 text-center" style="page-break-inside: avoid;">
            <div class="col-6">
                __________________________________<br>
                <span class="fw-bold mt-2 d-block">Firma del Coordinador</span>
            </div>
            <div class="col-6">
                __________________________________<br>
                <span class="fw-bold mt-2 d-block">Sello de Coordinación Académica</span>
            </div>
        </div>

    </div>

    <script src="<?= $basePath ?>/assets/js/administrative_matriculations_print.js"></script>
</body>
</html>