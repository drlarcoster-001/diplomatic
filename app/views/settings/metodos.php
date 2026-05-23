<?php
/**
 * MÓDULO: CONFIGURACIÓN / MÉTODOS DE PAGO
 * ARCHIVO: app/views/settings/metodos.php
 * PROPÓSITO: Cuadrícula de métodos con modal de campos inteligentes.
 * VERSIÓN: 1.8.0 - Table-Grid profesional y Modal discriminatorio.
 */
$metodos = $metodos ?? [];
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="/diplomatic/public/assets/css/settings_metodos.css">

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><i class="bi bi-cash-coin me-2"></i>Métodos de Pago</h2>
            <p class="text-muted small">Haz clic en cualquier fila para configurar los datos del canal.</p>
        </div>
        <a href="/diplomatic/public/settings" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 user-security-grid">
                    <thead class="bg-light text-uppercase">
                        <tr>
                            <th class="ps-4" style="width: 80px;">Icono</th>
                            <th>Canal</th>
                            <th>Identificador</th>
                            <th>Info Extra</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($metodos as $m): 
                            $type = $m['method_type'];
                            $icon = 'bi-cash-stack'; $color = 'text-success';
                            if($type === 'pago_movil') { $icon = 'bi-phone-vibrate-fill'; $color = 'text-primary'; }
                            if($type === 'zelle')      { $icon = 'bi-bank2'; $color = 'text-info'; }
                            if($type === 'binance')    { $icon = 'bi-currency-bitcoin'; $color = 'text-warning'; }
                            $isActive = (int)$m['status'] === 1;
                        ?>
                        <tr class="security-row cursor-pointer" onclick='window.editMethod(<?= json_encode($m) ?>)'>
                            <td class="ps-4">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border shadow-sm <?= $color ?>" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                    <i class="bi <?= $icon ?>"></i>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold d-block"><?= htmlspecialchars($m['method_name']) ?></span>
                                <small class="text-muted smallest"><?= strtoupper($type) ?></small>
                            </td>
                            <td>
                                <code class="text-dark fw-bold"><?= htmlspecialchars($m['identifier'] ?? '---') ?></code>
                                <br><small class="text-muted smallest"><?= htmlspecialchars($m['titular'] ?? '') ?></small>
                            </td>
                            <td>
                                <small class="fw-bold text-muted"><?= htmlspecialchars($m['extra_info'] ?: '---') ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-<?= $isActive ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $isActive ? 'success' : 'danger' ?> px-3">
                                    <?= $isActive ? 'ACTIVO' : 'INACTIVO' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditMethod" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" id="formMetodo">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title small text-uppercase">CONFIGURAR: <span id="modal_title_type"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="row g-3">
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted">NOMBRE DEL CANAL (VISTA ESTUDIANTE)</label>
                        <input type="text" name="method_name" id="edit_name" class="form-control form-control-sm" required>
                    </div>

                    <div id="fieldsDigital" class="row g-3 m-0 p-0 d-none">
                        <div class="col-6" id="row_extra_info">
                            <label id="label_extra" class="smallest fw-bold text-muted">EXTRA INFO</label>
                            <input type="text" name="extra_info" id="edit_banco" class="form-control form-control-sm">
                        </div>
                        <div class="col-6" id="row_identifier">
                            <label id="label_identifier" class="smallest fw-bold text-muted">IDENTIFICADOR</label>
                            <input type="text" name="identifier" id="edit_identificador" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="smallest fw-bold text-muted">NOMBRE DEL TITULAR</label>
                            <input type="text" name="titular" id="edit_titular" class="form-control form-control-sm">
                        </div>
                        <div class="col-12" id="row_identification">
                            <label class="smallest fw-bold text-muted">CÉDULA / RIF</label>
                            <input type="text" name="identification" id="edit_documento" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div id="fieldsCash" class="col-12 d-none">
                        <label class="smallest fw-bold text-muted">INSTRUCCIONES DE PAGO</label>
                        <textarea name="description" id="edit_instrucciones" class="form-control form-control-sm" rows="3"></textarea>
                    </div>

                    <div class="col-12 mt-3 pt-3 border-top">
                        <div class="form-check form-switch custom-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="edit_estatus" value="1">
                            <label class="form-check-label small fw-bold text-primary" for="edit_estatus">HABILITAR PARA ESTUDIANTES</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script src="/diplomatic/public/assets/js/settings_metodos.js"></script>