<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/views/administrative/reactivations/manage.php
 * PROPÓSITO: Listado detallado de cohorte y disparador de reactivación masiva.
 * VERSIÓN: 1.1.0 - Refactorización: Limpieza de JS embebido, integración de Breadcrumbs y uso de Data-Attributes.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_matriculations.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb" class="m-0">
            <ol class="breadcrumb bg-light p-2 rounded shadow-sm border mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/administrative/reactivations" class="text-decoration-none text-muted">Reactivaciones</a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Gestión de Cohorte</li>
            </ol>
        </nav>
        <a href="<?= $basePath ?>/administrative/reactivations" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver a Diplomados
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Estudiantes en esta Cohorte</h5>
                <p class="text-muted small mb-0">Listado de registros detectados como Egresados o Inactivos.</p>
            </div>
            
            <button class="btn btn-danger fw-bold rounded-pill px-4 shadow-sm" 
                    id="btn-reactivate-massive" 
                    data-offering-id="<?= (int)$data['offering_id'] ?>">
                <i class="bi bi-unlock-fill me-1"></i> Abrir Matrícula Masiva
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary small text-uppercase">Cédula</th>
                        <th class="py-3 text-secondary small text-uppercase">Estudiante</th>
                        <th class="py-3 text-secondary small text-uppercase">Estatus Matrícula</th>
                        <th class="py-3 text-secondary small text-uppercase">Estatus Global</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['students'])): ?>
                        <?php foreach ($data['students'] as $s): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['document_id']) ?></td>
                                <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td>
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">
                                        <?= htmlspecialchars($s['academic_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                        <?= htmlspecialchars($s['student_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle me-2"></i> No hay estudiantes registrados en esta cohorte.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/administrative_reactivations.js?v=<?= time() ?>"></script>