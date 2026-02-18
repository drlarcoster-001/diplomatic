<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/diplomados/create.php
 * Propósito: Formulario de registro con carga horaria y campos tipo memo.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_diplomados.css">

<div class="container py-4">
    <form action="<?= $basePath ?>/academic/diplomados/save" method="POST" id="formDiplomado">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Registrar Diplomado</h2>
                <p class="text-muted small">Defina la identidad y requerimientos del nuevo programa.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $basePath ?>/academic/diplomados" class="btn btn-outline-secondary rounded-pill px-4">Volver</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Guardar Programa
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Código Institucional</label>
                                <input type="text" name="code" class="form-control" placeholder="Ej: TR-2026" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Nombre del Diplomado (Memo)</label>
                                <textarea name="name" class="form-control" rows="2" placeholder="Nombre completo..." required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-bold">Descripción / Objetivo</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="¿Cuál es el propósito científico?"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perfil del Aspirante (Dirigido a:)</label>
                                <textarea name="directed_to" class="form-control" rows="3" placeholder="Ej: Médicos, Enfermeros..."></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Carga Horaria Total</label>
                                <div class="input-group">
                                    <input type="number" name="total_hours" class="form-control" value="200" min="1" required>
                                    <span class="input-group-text bg-light text-muted small">Horas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Condiciones del Contrato (Memo)</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('condicionesContainer', 'conditions')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="condicionesContainer">
                        <div class="input-group mb-3">
                            <textarea name="conditions[]" class="form-control" rows="2" required>La nota mínima aprobatoria del diplomado es de 15 puntos.</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Requisitos de Ingreso (Memo)</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('requisitosContainer', 'requirements')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="requisitosContainer">
                        <div class="input-group mb-3">
                            <textarea name="requirements[]" class="form-control" rows="2" placeholder="Ej: Título de fondo negro..." required></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_diplomados.js"></script>