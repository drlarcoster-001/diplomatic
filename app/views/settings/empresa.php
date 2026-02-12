<?php
/**
 * MÓDULO - app/views/settings/empresa.php
 * Vista del formulario de Identidad Institucional.
 * Presenta campos legales, de contacto y del responsable del sistema.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$e = $empresa;
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/company.css?v=<?= time() ?>">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Datos de Empresa</h2>
            <p class="text-muted small mb-0">Administra la ficha legal y de contacto de la institución.</p>
        </div>
        <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary px-3 btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al Menú
        </a>
    </div>

    <form id="form-company" data-basepath="<?= $basePath ?>">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary">Información Legal</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">Razón Social</label>
                            <input type="text" name="nombre_legal" class="form-control" value="<?= $e['nombre_legal'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nombre Comercial</label>
                            <input type="text" name="nombre_comercial" class="form-control" value="<?= $e['nombre_comercial'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Identificación Fiscal</label>
                            <input type="text" name="id_fiscal" class="form-control" value="<?= $e['id_fiscal'] ?? '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Dirección Fiscal</label>
                            <textarea name="direccion" class="form-control" rows="3"><?= $e['direccion'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                    <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary">Canales Directos</h5>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Teléfono Fijo</label>
                        <input type="text" name="telefono" class="form-control" value="<?= $e['telefono'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Email Institucional</label>
                        <input type="email" name="email" class="form-control" value="<?= $e['email'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Sitio Web</label>
                        <input type="url" name="sitio_web" class="form-control" value="<?= $e['sitio_web'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Responsable de la Institución</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Nombre Completo</label>
                            <input type="text" name="representante" class="form-control" value="<?= $e['representante'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Cargo / Función</label>
                            <input type="text" name="cargo_rep" class="form-control" value="<?= $e['cargo_rep'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Teléfono de Contacto</label>
                            <input type="text" name="tel_rep" class="form-control" value="<?= $e['tel_rep'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <button type="button" class="btn btn-primary px-5 py-3 fw-bold shadow-sm" onclick="saveCompanyInfo()">
                <i class="bi bi-save2 me-2"></i> Guardar Información
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/company.js?v=<?= time() ?>"></script>