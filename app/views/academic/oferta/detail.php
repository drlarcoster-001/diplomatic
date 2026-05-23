<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/oferta/detail.php
 * Propósito: Vista inyectada por AJAX para Modal de Detalles.
 * Version: 1.2.0 - Adaptado para mostrar el cronograma de pagos correctamente.
 */
?>
<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-md-12">
            <h6 class="fw-bold text-primary border-bottom pb-2">Información Principal</h6>
        </div>
        <div class="col-md-6 mb-2"><strong>Diplomado:</strong> <br><?= htmlspecialchars($oferta['diplomado_name']) ?></div>
        <div class="col-md-6 mb-2"><strong>Cohorte:</strong> <br><?= htmlspecialchars($oferta['cohort_code']) ?> - <?= htmlspecialchars($oferta['cohort_name']) ?></div>
        <div class="col-md-4 mb-2"><strong>Inscripciones:</strong> <br><?= $oferta['registration_start'] ?> / <?= $oferta['registration_end'] ?></div>
        <div class="col-md-4 mb-2"><strong>Clases:</strong> <br><?= $oferta['class_start'] ?> / <?= $oferta['class_end'] ?></div>
        <div class="col-md-4 mb-2"><strong>Modalidad:</strong> <br><?= $oferta['general_modality'] ?></div>
        <div class="col-md-4 mb-2"><strong>Cupo Total:</strong> <?= $oferta['total_capacity'] ?></div>
        <div class="col-md-4 mb-2"><strong>Inscritos:</strong> <span class="text-primary fw-bold"><?= $oferta['enrolled_count'] ?></span></div>
        <div class="col-md-4 mb-2"><strong>Costo Total:</strong> $<?= $oferta['total_cost'] ?> <?= $oferta['currency_code'] ?></div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold text-info border-bottom pb-2">Sedes Habilitadas</h6>
            <ul class="list-group list-group-flush mb-3">
                <?php foreach($oferta['campuses'] as $c): ?>
                    <li class="list-group-item py-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($c['name']) ?></li>
                <?php endforeach; ?>
            </ul>

            <h6 class="fw-bold text-success border-bottom pb-2">Grupos</h6>
            <ul class="list-group list-group-flush mb-3">
                <?php foreach($oferta['groups'] as $g): ?>
                    <li class="list-group-item py-1">
                        <strong><?= htmlspecialchars($g['name']) ?></strong> 
                        <span class="badge bg-secondary"><?= $g['modality'] ?></span><br>
                        <small class="text-muted"><i class="bi bi-clock"></i> <?= htmlspecialchars($g['schedule_info']) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="col-md-6">
            <h6 class="fw-bold text-warning border-bottom pb-2">Equipo Docente</h6>
            <ul class="list-group list-group-flush mb-3">
                <?php foreach($oferta['professors'] as $p): ?>
                    <li class="list-group-item py-1">
                        <i class="bi bi-person"></i> <strong class="text-dark"><?= htmlspecialchars($p['full_name']) ?></strong> <br>
                        <small class="text-muted">Rol: <?= $p['role'] ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <h6 class="fw-bold text-danger border-bottom pb-2 mt-2">Cronograma de Pagos</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th class="fw-bold">Concepto y Descripción</th>
                    <th class="fw-bold text-end">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($oferta['payment_plans'] as $pl): ?>
                    <tr>
                        <td><?= htmlspecialchars($pl['name']) ?></td>
                        <td class="text-end fw-bold text-success">$<?= number_format($pl['installment_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>