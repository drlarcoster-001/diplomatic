<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/diplomados/edit.php
 * Propósito: Formulario de edición con persistencia unificada, selector de estatus y breadcrumbs.
 * Version: 1.4.3 - Integración de navegación jerárquica y estandarización de Panel Académico.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$d = $data['diplomado'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_diplomados.css">

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic/diplomados" class="text-decoration-none text-muted">Catálogo</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Modificar Diplomado</li>
        </ol>
    </nav>

    <form action="<?= $basePath ?>/academic/diplomados/save" method="POST" id="formEditDiplomado">
        <input type="hidden" name="id" value="<?= $d['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Modificar Diplomado</h2>
                <p class="text-muted small">Editando el programa: <span class="badge bg-dark"><?= htmlspecialchars($d['code']) ?></span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $basePath ?>/academic/diplomados" class="btn btn-outline-secondary rounded-pill px-4">Volver</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Guardar Cambios
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
                                <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($d['code']) ?>" maxlength="25" required>
                                <div class="form-text" style="font-size: 0.7rem;">Máximo 25 caracteres.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estatus Operativo</label>
                                <select name="status" id="status_select" class="form-select shadow-sm">
                                    <option value="BORRADOR" <?= $d['status'] === 'BORRADOR' ? 'selected' : '' ?>>BORRADOR</option>
                                    <option value="ACTIVO" <?= $d['status'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Nombre del Diplomado</label>
                                <textarea name="name" class="form-control" rows="2" required><?= htmlspecialchars($d['name']) ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Descripción / Objetivo</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($d['description']) ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perfil del Aspirante (Dirigido a:)</label>
                                <textarea name="directed_to" class="form-control" rows="3"><?= htmlspecialchars($d['directed_to']) ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Carga Horaria Total</label>
                                <div class="input-group">
                                    <input type="number" name="total_hours" id="total_hours" class="form-control" value="<?= $d['total_hours'] ?>" min="0" max="2400" required>
                                    <span class="input-group-text bg-light text-muted small">Horas</span>
                                </div>
                                <div class="form-text" style="font-size: 0.7rem;">Rango permitido: 0 - 2400 horas.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Condiciones del Contrato</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('condicionesContainer', 'conditions')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="condicionesContainer">
                        <?php if (empty($data['conditions'])): ?>
                            <div class="input-group mb-3">
                                <textarea name="conditions[]" class="form-control" rows="2" placeholder="Ejemplo: La nota mínima aprobatoria del diplomado es de 15 puntos."></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['conditions'] as $cond): ?>
                                <div class="input-group mb-3">
                                    <textarea name="conditions[]" class="form-control" rows="2"><?= htmlspecialchars($cond['condition_text']) ?></textarea>
                                    <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Requisitos de Ingreso</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('requisitosContainer', 'requirements')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="requisitosContainer">
                        <?php if (empty($data['requirements'])): ?>
                            <div class="input-group mb-3">
                                <textarea name="requirements[]" class="form-control" rows="2" placeholder="Ej: Título de fondo negro..."></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data['requirements'] as $req): ?>
                                <div class="input-group mb-3">
                                    <textarea name="requirements[]" class="form-control" rows="2"><?= htmlspecialchars($req['requirement_text']) ?></textarea>
                                    <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_diplomados.js?v=<?= time() ?>"></script>