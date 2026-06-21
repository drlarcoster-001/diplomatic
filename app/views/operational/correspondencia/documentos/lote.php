<?php
/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: app/views/operational/correspondencia/documentos/lote.php
 * PROPÓSITO: Pantalla de resultado tras generar un lote: lista cada
 *            documento producido con su código y link de descarga/impresión.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_correspondencia_documentos.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational/correspondencia/documentos" class="text-decoration-none text-muted">Documentos Generados</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#198754;">Lote Generado</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><i class="bi bi-check-circle-fill text-success me-2"></i> Documentos Generados</h2>
            <p class="text-muted small mb-0"><?= count($documentos) ?> documento(s) producido(s) en este lote.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/operational/correspondencia/documentos/create" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Generar Otro Lote
            </a>
            <a href="<?= $basePath ?>/operational/correspondencia/documentos" class="btn btn-dark rounded-pill px-4 shadow-sm">
                Ver Historial Completo
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-3">Código</th>
                        <th>Tabla</th>
                        <th class="text-end pe-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $d): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($d['codigo']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars(str_replace('_', ' ', $d['tabla_objetivo'])) ?></td>
                        <td class="text-end pe-3">
                            <a href="<?= $basePath ?>/operational/correspondencia/documentos/descargar?id=<?= $d['id'] ?>"
                               target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-printer me-1"></i> Ver / Imprimir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>