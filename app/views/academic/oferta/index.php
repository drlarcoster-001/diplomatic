<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/oferta/index.php
 * PROPÓSITO: Vista principal para el listado y control de Ofertas Académicas. 
 * Gestiona el ciclo de vida (Venta/Académico) y el bloqueo de edición por estatus.
 * VERSIÓN: 3.42.2 - Inserción de la columna "Grupos" en la matriz principal (Preparación de UI).
 */
?>
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_oferta.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="/diplomatic/public/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="/diplomatic/public/academic" class="text-decoration-none text-muted">Panel Académico</a></li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Oferta Académica</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Oferta Académica</h2>
            <p class="text-muted small">Creación, Control y Asignación de Oferta Académica.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm"><i class="bi bi-arrow-left me-1"></i> Volver</a>
            <a href="/diplomatic/public/academic/oferta/create" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="bi bi-plus-lg me-1"></i> Nueva Oferta</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" method="GET" action="/diplomatic/public/academic/oferta" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="f_diploma" class="form-label fw-bold small text-secondary">Diplomado</label>
                    <select name="diploma_id" id="f_diploma" class="form-select form-select-sm auto-filter">
                        <option value="">-- Todos --</option>
                        <?php foreach($diplomados as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($filters['diploma_id'] == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="f_cohort" class="form-label fw-bold small text-secondary">Cohorte</label>
                    <select name="cohort_id" id="f_cohort" class="form-select form-select-sm auto-filter">
                        <option value="">-- Todas --</option>
                        <?php foreach($cohortes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($filters['cohort_id'] == $c['id']) ? 'selected' : '' ?>>[<?= $c['cohort_code'] ?>] <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="f_status" class="form-label fw-bold small text-secondary">Estatus</label>
                    <select name="status" id="f_status" class="form-select form-select-sm auto-filter">
                        <option value="">-- Todos --</option>
                        <option value="BORRADOR" <?= ($filters['status'] == 'BORRADOR') ? 'selected' : '' ?>>BORRADOR</option>
                        <option value="ABIERTA" <?= ($filters['status'] == 'ABIERTA') ? 'selected' : '' ?>>ABIERTA</option>
                        <option value="EN CURSO" <?= ($filters['status'] == 'EN CURSO') ? 'selected' : '' ?>>EN CURSO</option>
                        <option value="CERRADA" <?= ($filters['status'] == 'CERRADA') ? 'selected' : '' ?>>CERRADA</option>
                        <option value="FINALIZADA" <?= ($filters['status'] == 'FINALIZADA') ? 'selected' : '' ?>>FINALIZADA</option>
                    </select>
                </div>
                <div class="col-md-1"><a href="/diplomatic/public/academic/oferta" class="btn btn-light border btn-sm w-100" title="Limpiar"><i class="bi bi-eraser text-secondary"></i></a></div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-blue small fw-bold text-uppercase">
                    <tr>
                        <th class="ps-4">Diplomado / Cohorte</th>
                        <th>Períodos Operativos</th>
                        <th>Grupos</th> <th class="text-center">Cupos (Tot/Disp)</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $row): ?>
                        <tr class="row-oferta cursor-pointer" 
                            data-id="<?= $row['id'] ?>" 
                            data-status="<?= $row['status'] ?>"
                            data-diploma="<?= htmlspecialchars($row['diplomado_name']) ?>"
                            data-cohort="[<?= htmlspecialchars($row['cohort_code']) ?>] <?= htmlspecialchars($row['cohort_name']) ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['diplomado_name']) ?></div>
                                <div class="text-muted small" style="font-weight:400;">[<?= htmlspecialchars($row['cohort_code']) ?>] <?= htmlspecialchars($row['cohort_name']) ?></div>
                            </td>
                            <td>
                                <div class="small mb-1" style="font-weight:400;"><span class="badge bg-light text-dark border me-1" style="font-size:0.6rem;">INSCRIPCIÓN</span> <?= $row['registration_start'] ?> <i class="bi bi-arrow-right text-muted"></i> <?= $row['registration_end'] ?></div>
                                <div class="small" style="font-weight:400;"><span class="badge bg-light text-dark border me-1" style="font-size:0.6rem;">CLASES</span> <?= $row['class_start'] ?> <i class="bi bi-arrow-right text-muted"></i> <?= $row['class_end'] ?></div>
                            </td>
                            
                            <td>
                                <?php if (!empty($row['grupos_nombres'])): ?>
                                    <span class="badge bg-light text-primary border" style="font-weight:500;">
                                        <i class="bi bi-people-fill me-1"></i> <?= htmlspecialchars($row['grupos_nombres']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-dash"></i> Sin asignar</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center fw-bold">
                                <span class="text-dark"><?= $row['total_capacity'] ?></span> / <span class="text-success"><?= $row['cupos_disponibles'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($row['status'] === 'ABIERTA') $badgeClass = 'bg-success';
                                    if ($row['status'] === 'EN CURSO') $badgeClass = 'bg-primary';
                                    if (in_array($row['status'], ['CERRADA', 'FINALIZADA', 'CANCELADA'])) $badgeClass = 'bg-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 shadow-sm"><?= $row['status'] ?></span>
                            </td>
                            <td class="text-end pe-4" onclick="event.stopPropagation();">
                                <?php if($row['status'] !== 'BORRADOR'): ?>
                                    <button type="button" class="btn btn-sm btn-white border text-warning shadow-sm btn-lock" 
                                            data-id="<?= $row['id'] ?>"
                                            data-status="<?= $row['status'] ?>"
                                            title="Bloqueada (<?= $row['status'] ?>). Requiere Admin.">
                                        <i class="bi bi-lock-fill"></i>
                                    </button>
                                <?php else: ?>
                                    <div class="btn-group shadow-sm">
                                        <a href="/diplomatic/public/academic/oferta/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border text-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <form action="/diplomatic/public/academic/oferta/delete" method="POST" class="d-inline form-delete-oferta">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-white border text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="/diplomatic/public/assets/js/academic_oferta.js"></script>