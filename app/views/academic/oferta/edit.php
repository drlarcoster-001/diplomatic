<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/oferta/edit.php
 * PROPÓSITO: Interfaz administrativa para la modificación de parámetros operativos de ofertas.
 * VERSIÓN: 3.44.0 - Integración de columna Vencimiento en Esquema de Pagos para edición.
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
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Editar Oferta #<?= (int)$oferta['id'] ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Oferta Académica</h2>
            <p class="text-muted small">Actualización de parámetros operativos y financieros.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic/oferta" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">Volver</a>
            <button type="submit" form="formOferta" class="btn btn-primary rounded-pill px-4 shadow-sm">Actualizar Oferta</button>
        </div>
    </div>

    <form action="/diplomatic/public/academic/oferta/update" method="POST" id="formOferta">
        <input type="hidden" name="id" value="<?= (int)$oferta['id'] ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4 border-left-primary border-0">
                    <div class="card-header bg-white fw-bold text-primary">A. Parámetros Base</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 mb-2">
                                <label class="form-label fw-bold small">Período</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($oferta['periodo_nombre'] ?? 'Sin período') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Diplomado</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($oferta['diplomado_name'] ?? '') ?>" readonly>
                                <input type="hidden" name="diploma_id" value="<?= (int)$oferta['diploma_id'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Cohorte</label>
                                <input type="text" class="form-control bg-light" value="[<?= htmlspecialchars($oferta['cohort_code'] ?? '') ?>] <?= htmlspecialchars($oferta['cohort_name'] ?? '') ?>" readonly>
                                <input type="hidden" name="cohort_id" value="<?= (int)$oferta['cohort_id'] ?>">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3"><label class="small fw-bold">Insc. Inicio</label><input type="date" name="registration_start" value="<?= htmlspecialchars($oferta['registration_start'] ?? '') ?>" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><label class="small fw-bold">Insc. Fin</label><input type="date" name="registration_end" value="<?= htmlspecialchars($oferta['registration_end'] ?? '') ?>" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><label class="small fw-bold">Clases Inicio</label><input type="date" name="class_start" value="<?= htmlspecialchars($oferta['class_start'] ?? '') ?>" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><label class="small fw-bold">Clases Fin</label><input type="date" name="class_end" value="<?= htmlspecialchars($oferta['class_end'] ?? '') ?>" class="form-control form-control-sm"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="fw-bold small">Modalidad</label>
                                <select name="general_modality" class="form-select">
                                    <option value="Presencial" <?= ($oferta['general_modality']=='Presencial')?'selected':'' ?>>Presencial</option>
                                    <option value="Virtual" <?= ($oferta['general_modality']=='Virtual')?'selected':'' ?>>Virtual</option>
                                    <option value="Mixta" <?= ($oferta['general_modality']=='Mixta')?'selected':'' ?>>Mixta</option>
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
                                    <?php 
                                    $selected_pids = array_column($oferta['professors'] ?? [], 'professor_id');
                                    foreach($professors ?? [] as $p): 
                                        $isHidden = in_array($p['id'], $selected_pids) ? 'd-none' : '';
                                    ?>
                                        <div class="prof-item d-flex justify-content-between p-2 border-bottom <?= $isHidden ?>" data-id="<?= (int)$p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>">
                                            <span><?= htmlspecialchars($p['full_name']) ?></span>
                                            <button type="button" class="btn btn-sm text-success btn-add-prof"><i class="bi bi-plus-circle-fill"></i></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-center justify-content-center"><i class="bi bi-arrow-left-right text-muted"></i></div>
                            <div class="col-md-6">
                                <div class="dual-listbox border rounded bg-light" id="list_selected">
                                    <?php foreach($oferta['professors'] ?? [] as $idx => $sp): 
                                        $pName = '';
                                        foreach($professors ?? [] as $pr) { 
                                            if($pr['id'] == $sp['professor_id']) { $pName = $pr['full_name']; break; } 
                                        }
                                    ?>
                                        <div class="prof-item d-flex justify-content-between p-2 border-bottom bg-white" data-id="<?= (int)$sp['professor_id'] ?>">
                                            <div class="text-truncate small" style="max-width:45%">
                                                <input type="hidden" name="professor_id[<?= $idx ?>]" value="<?= (int)$sp['professor_id'] ?>">
                                                <?= htmlspecialchars($pName) ?>
                                            </div>
                                            <div>
                                                <select name="professor_role[<?= $idx ?>]" class="form-select form-select-sm d-inline-block w-auto me-1" style="font-size:0.7rem;">
                                                    <option value="PRINCIPAL" <?= ($sp['role']=='PRINCIPAL')?'selected':'' ?>>Principal</option>
                                                    <option value="INVITADO" <?= ($sp['role']=='INVITADO')?'selected':'' ?>>Invitado</option>
                                                    <option value="ASISTENTE" <?= ($sp['role']=='ASISTENTE')?'selected':'' ?>>Asistente</option>
                                                    <option value="COORDINADOR" <?= ($sp['role']=='COORDINADOR')?'selected':'' ?>>Coordinador</option>
                                                </select>
                                                <button type="button" class="btn btn-sm text-danger btn-remove-prof"><i class="bi bi-trash-fill"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-left-danger border-0">
                    <div class="card-header bg-white fw-bold text-danger">D. Esquema de Pagos *</div>
                    <div class="card-body">
                        <div class="row g-3 mb-4 bg-light p-2 rounded border">
                            <div class="col-md-3"><label class="fw-bold small">Costo Total ($)</label><input type="number" step="0.01" name="total_cost" id="calc_total" value="<?= (float)$oferta['total_cost'] ?>" class="form-control fw-bold text-primary"></div>
                            <div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" id="calc_has_inscripcion" checked><label class="form-check-label fw-bold small">¿Inscripción?</label></div></div>
                            <div class="col-md-2"><label class="fw-bold small">Monto</label><input type="number" id="calc_inscripcion_amount" class="form-control" value="30"></div>
                            <div class="col-md-2"><label class="fw-bold small">Cuotas</label><input type="number" id="calc_cuotas" class="form-control" value="5"></div>
                            <div class="col-md-2 mt-4"><button type="button" class="btn btn-dark btn-sm w-100 fw-bold" onclick="calculatePayments()">Recalcular</button></div>
                        </div>
                        <table class="table table-bordered mb-0" id="plans_table">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold small">Concepto</th>
                                    <th class="fw-bold small" style="width: 15%;">Monto ($)</th>
                                    <th class="fw-bold small" style="width: 20%;">Vencimiento *</th>
                                    <th class="fw-bold small">Nota</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($oferta['payments'] ?? [] as $idx => $pay): ?>
                                    <tr>
                                        <td><input type="text" name="payment_concept[<?= $idx ?>]" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($pay['name'] ?? '') ?>" readonly></td>
                                        <td><input type="number" step="0.01" name="payment_amount[<?= $idx ?>]" class="form-control form-control-sm bg-light" value="<?= (float)$pay['amount'] ?>" readonly></td>
                                        <td><input type="date" name="payment_due_date[<?= $idx ?>]" class="form-control form-control-sm border-primary" value="<?= htmlspecialchars($pay['due_date'] ?? '') ?>" required></td>
                                        <td><input type="text" name="payment_description[<?= $idx ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($pay['notes'] ?? '') ?>"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 border-left-info border-0">
                    <div class="card-header bg-white fw-bold text-info">E. Sedes *</div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach($campuses ?? [] as $c): 
                            $isChecked = in_array($c['id'], $oferta['campuses'] ?? []) ? 'checked' : '';
                        ?>
                            <div class="form-check border-bottom pb-2 mb-2 d-flex align-items-start">
                                <input class="form-check-input me-3 mt-1" type="checkbox" name="campuses[]" value="<?= (int)$c['id'] ?>" id="cam_<?= $c['id'] ?>" <?= $isChecked ?>>
                                <label class="form-check-label w-100" for="cam_<?= $c['id'] ?>">
                                    <strong class="text-dark small"><?= htmlspecialchars($c['name']) ?></strong>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card shadow-sm mb-4 border-left-success border-0">
                    <div class="card-header bg-white fw-bold text-success">F. Grupos *</div>
                    <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach($grupos ?? [] as $g): 
                            $isChecked = in_array($g['id'], $oferta['groups'] ?? []) ? 'checked' : '';
                        ?>
                            <div class="form-check border-bottom pb-2 mb-2 d-flex align-items-start">
                                <input class="form-check-input me-3 mt-1" type="checkbox" name="groups_check[]" value="<?= (int)$g['id'] ?>" id="g_<?= $g['id'] ?>" <?= $isChecked ?>>
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
                        <label class="fw-bold small">Cupo Total (1 - 399)</label>
                        <input type="number" name="total_capacity" id="total_capacity" value="<?= (int)($oferta['total_capacity'] ?? 0) ?>" class="form-control form-control-lg text-center fw-bold" min="1" max="399" required>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="/diplomatic/public/assets/js/academic_oferta.js"></script>