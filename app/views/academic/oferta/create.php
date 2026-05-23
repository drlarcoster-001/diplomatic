<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/oferta/create.php
 * PROPÓSITO: Formulario de creación con Grid de Pagos actualizada (Fecha de Vencimiento).
 * VERSIÓN: 3.19.0 - Integración de columna Vencimiento en Esquema de Pagos.
 */
?>
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_oferta.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="/diplomatic/public/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="/diplomatic/public/academic" class="text-decoration-none text-muted">Panel Académico</a></li>
            <li class="breadcrumb-item"><a href="/diplomatic/public/academic/oferta" class="text-decoration-none text-muted">Oferta Académica</a></li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Nueva Oferta</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Crear Oferta Académica</h2>
            <p class="text-muted small">Configuración operativa obligatoria de convocatorias.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic/oferta" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">Volver</a>
            <button type="submit" form="formOferta" class="btn btn-primary rounded-pill px-4 shadow-sm">Guardar Oferta</button>
        </div>
    </div>

    <form action="/diplomatic/public/academic/oferta/save" method="POST" id="formOferta">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4 border-left-primary border-0">
                    <div class="card-header bg-white fw-bold text-primary">A. Parámetros Base</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Diplomado *</label>
                                <select name="diploma_id" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($diplomados ?? [] as $d): ?>
                                        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Cohorte *</label>
                                <select name="cohort_id" id="cohort_id" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($cohortes ?? [] as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" 
                                                data-rstart="<?= htmlspecialchars((string)$c['enrollment_start']) ?>" 
                                                data-rend="<?= htmlspecialchars((string)$c['enrollment_end']) ?>" 
                                                data-cstart="<?= htmlspecialchars((string)$c['start_date']) ?>" 
                                                data-cend="<?= htmlspecialchars((string)$c['end_date']) ?>">
                                            [<?= htmlspecialchars($c['cohort_code']) ?>] <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3"><label class="small fw-bold">Insc. Inicio</label><input type="date" name="registration_start" id="reg_start" class="form-control form-control-sm bg-light" readonly></div>
                            <div class="col-md-3"><label class="small fw-bold">Insc. Fin</label><input type="date" name="registration_end" id="reg_end" class="form-control form-control-sm bg-light" readonly></div>
                            <div class="col-md-3"><label class="small fw-bold">Clases Inicio</label><input type="date" name="class_start" id="class_start" class="form-control form-control-sm bg-light" readonly></div>
                            <div class="col-md-3"><label class="small fw-bold">Clases Fin</label><input type="date" name="class_end" id="class_end" class="form-control form-control-sm bg-light" readonly></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="fw-bold small">Modalidad</label>
                                <select name="general_modality" class="form-select">
                                    <option value="Presencial">Presencial</option>
                                    <option value="Virtual">Virtual</option>
                                    <option value="Mixta">Mixta</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="fw-bold small">Descripción</label>
                                <textarea name="description" class="form-control" rows="1"><?= htmlspecialchars($oferta['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-left-warning border-0">
                    <div class="card-header bg-white fw-bold text-warning">C. Profesores</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="dual-listbox border rounded" id="list_available">
                                    <?php foreach($professors ?? [] as $p): ?>
                                        <div class="prof-item d-flex justify-content-between p-2 border-bottom" data-id="<?= (int)$p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>">
                                            <span><?= htmlspecialchars($p['full_name']) ?></span>
                                            <button type="button" class="btn btn-sm text-success btn-add-prof"><i class="bi bi-plus-circle-fill"></i></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-center justify-content-center"><i class="bi bi-arrow-left-right text-muted"></i></div>
                            <div class="col-md-6">
                                <div class="dual-listbox border rounded bg-light" id="list_selected"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-left-danger border-0">
                    <div class="card-header bg-white fw-bold text-danger">D. Esquema de Pagos *</div>
                    <div class="card-body">
                        <div class="row g-3 mb-4 bg-light p-2 rounded border">
                            <div class="col-md-3"><label class="fw-bold small">Costo Total ($)</label><input type="number" step="0.01" name="total_cost" id="calc_total" class="form-control fw-bold text-primary" required></div>
                            <div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" id="calc_has_inscripcion" checked><label class="form-check-label fw-bold small">¿Inscripción?</label></div></div>
                            <div class="col-md-2"><label class="fw-bold small">Monto</label><input type="number" id="calc_inscripcion_amount" class="form-control" value="30"></div>
                            <div class="col-md-2"><label class="fw-bold small">Cuotas</label><input type="number" id="calc_cuotas" class="form-control" value="5"></div>
                            <div class="col-md-2 mt-4"><button type="button" class="btn btn-dark btn-sm w-100 fw-bold" onclick="calculatePayments()">Calcular</button></div>
                        </div>
                        
                        <table class="table table-bordered mb-0" id="plans_table">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold small">Concepto</th>
                                    <th class="fw-bold small" style="width: 15%;">Monto ($)</th>
                                    <th class="fw-bold small" style="width: 20%;">Vencimiento *</th> <th class="fw-bold small">Nota</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                        <div id="payment_error" class="text-danger small mt-2 d-none">Debe generar al menos un plan de pago y completar todas las fechas.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 border-left-info border-0">
                    <div class="card-header bg-white fw-bold text-info">E. Sedes *</div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <div id="campuses_container">
                            <div class="small italic text-muted text-center py-3">Seleccione cohorte...</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-left-success border-0">
                    <div class="card-header bg-white fw-bold text-success">F. Grupos *</div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach($grupos ?? [] as $g): ?>
                            <div class="form-check border-bottom pb-2 mb-2 d-flex align-items-start">
                                <input class="form-check-input me-3 mt-1" type="checkbox" name="groups_check[]" value="<?= (int)$g['id'] ?>" id="g_<?= $g['id'] ?>">
                                <label class="form-check-label w-100" for="g_<?= $g['id'] ?>">
                                    <strong class="text-dark small"><?= htmlspecialchars($g['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($g['description'] ?? '') ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-left-secondary border-0">
                    <div class="card-header bg-white fw-bold text-secondary">B. Cupos *</div>
                    <div class="card-body">
                        <label for="total_capacity" class="fw-bold small">Cupo Total (1 - 399)</label>
                        <input type="number" name="total_capacity" id="total_capacity" class="form-control form-control-lg text-center fw-bold" min="1" max="399" required>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="/diplomatic/public/assets/js/academic_oferta.js"></script>