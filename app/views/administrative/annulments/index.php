<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/views/administrative/annulments/index.php
 * PROPÓSITO: Interfaz de búsqueda y gestión para la cancelación de inscripciones con visor de detalles y confirmación.
 * VERSIÓN: 1.1.0 - Refactorización de UI: Integración de modal de detalles, ajuste de breadcrumbs y sincronización con JS.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_annulments.css">

<div class="container-fluid py-4">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small">
                <a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a>
            </li>
            <li class="breadcrumb-item small">
                <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="text-decoration-none text-muted fw-medium">Panel Administrativo</a>
            </li>
            <li class="breadcrumb-item active fw-bold small" style="color: #b02a37;" aria-current="page">
                Cancelar Inscripciones
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-trash3-fill me-2" style="color: #b02a37;"></i>Anular Inscripciones
            </h2>
            <p class="text-muted small mb-0">Búsqueda de inscripciones <b>Aprobadas</b> para reinicio de usuario y eliminación de matrícula.</p>
        </div>
        <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="btn btn-light rounded-pill px-4 fw-bold text-muted border shadow-sm">
            <i class="bi bi-arrow-left me-2"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8 position-relative">
                    <label class="form-label small fw-bold text-secondary text-uppercase mb-2">
                        <i class="bi bi-person-search me-1"></i> Localizar Inscripción
                    </label>
                    <div class="input-group input-group-lg border rounded-3 overflow-hidden">
                        <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="search-annulment" class="form-control border-0 shadow-none bg-white" 
                               placeholder="Cédula, nombre o diplomado..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted border shadow-sm" id="btn-clear-filter">
                        <i class="bi bi-eraser me-1"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="results-area" class="d-none animate__animated animate__fadeIn">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-secondary small text-uppercase">Cédula</th>
                            <th class="py-3 text-secondary small text-uppercase">Estudiante</th>
                            <th class="py-3 text-secondary small text-uppercase">Diplomado / Programa</th>
                            <th class="py-3 text-secondary small text-uppercase">Fecha Aprobación</th>
                            <th class="text-end pe-4 py-3 text-secondary small text-uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="annulments-table-body">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
            <i class="bi bi-person-x fs-1 text-muted opacity-50"></i>
        </div>
        <h5 class="text-muted fw-bold">Sin resultados</h5>
        <p class="text-muted small mx-auto" style="max-width: 450px;">
            No se encontraron inscripciones aprobadas con los criterios ingresados o el sistema está esperando su entrada.
        </p>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-person-badge-fill me-2" style="color: #b02a37;"></i> Revisión de Inscripción
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4" id="modal-content-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-danger" role="status"></div>
                        <p class="small text-muted mt-2">Cargando ficha del estudiante...</p>
                        </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                    <button type="button" id="btn-execute-annulment" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-trash3-fill me-1"></i> Confirmar Cancelación
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_annulments.js?v=<?= time() ?>"></script>