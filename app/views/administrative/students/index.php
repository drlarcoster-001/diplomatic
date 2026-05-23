<?php
/**
 * MÓDULO: ADMINISTRATIVO / ESTUDIANTES
 * ARCHIVO: app/views/administrative/students/index.php
 * PROPÓSITO: Vista del directorio de estudiantes con filtros avanzados y exportación a Excel.
 * VERSIÓN: 1.2.6 - Integración de librería SheetJS y botón de exportación premium (Verde Excel).
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_students.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb premium-breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house me-1"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/administrative" class="text-decoration-none text-muted">Panel Administrativo</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Directorio Institucional</li>
                </ol>
            </nav>
            <h1 class="h2 fw-bold text-dark mb-0">Directorio de Estudiantes</h1>
            <p class="text-secondary opacity-75 mb-0">Gestión de expedientes, contacto y descarga de reportes académicos.</p>
        </div>
        
        <a href="<?= $basePath ?>/administrative" class="btn btn-white border shadow-sm fw-bold px-4 py-2 rounded-3 text-dark" style="background: white;">
            <i class="bi bi-arrow-left-short fs-4 me-1"></i> Volver al Panel
        </a>
    </div>

    <div class="card glass-panel border-0 mb-5">
        <div class="card-body p-4">
            <form id="filter-form-students" class="row g-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted px-2">Búsqueda Global</label>
                    <input type="text" class="form-control form-input-premium" id="search-text" placeholder="Cédula, Nombre o Expediente...">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted px-2">Diplomado</label>
                    <select class="form-select form-input-premium" id="filter-diplomado">
                        <option value="">Todos los Programas</option>
                        <?php if (isset($diplomados) && is_array($diplomados)): ?>
                            <?php foreach ($diplomados as $dip): ?>
                                <option value="<?= $dip['id'] ?>"><?= htmlspecialchars($dip['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted px-2">Estatus Académico</label>
                    <select class="form-select form-input-premium" id="filter-status">
                        <option value="">Cualquiera</option>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="CONGELADO">CONGELADO</option>
                        <option value="EGRESADO">EGRESADO</option>
                        <option value="SUSPENDIDO">SUSPENDIDO</option>
                        <option value="RETIRADO">RETIRADO</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted px-2">Documentos</label>
                    <select class="form-select form-input-premium" id="filter-docs">
                        <option value="">Todos</option>
                        <option value="COMPLETE">Completos</option>
                        <option value="INCOMPLETE">Pendientes</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-dark fw-bold w-100 py-3 rounded-4 shadow-sm" id="btn-clear-filters" title="Limpiar Filtros">
                            <i class="bi bi-eraser"></i>
                        </button>
                        <button type="button" class="btn btn-success fw-bold w-100 py-3 rounded-4 shadow-sm d-flex align-items-center justify-content-center" id="btn-export-excel" title="Exportar a Excel" style="background: #198754; border: none; color: white;">
                            <i class="bi bi-file-earmark-excel fs-5"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="glass-panel p-0 overflow-hidden border-0">
        <div class="d-flex justify-content-between align-items-center p-4 border-bottom bg-white">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i> Padrón Estudiantil</h5>
            <div id="total-results" class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold">...</div>
        </div>
        
        <div class="table-responsive p-4" style="max-height: 60vh;">
            <table class="table table-premium align-middle mb-0" id="table-students-directory">
                <thead>
                    <tr>
                        <th class="ps-4">Expediente</th>
                        <th>Nombre Completo</th>
                        <th>Cédula</th>
                        <th>Programa</th>
                        <th>Estado Académico</th>
                        <th>Documentación</th> 
                        <th class="text-end pe-4">Expediente</th> 
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/administrative_students.js?v=<?= time() ?>"></script>