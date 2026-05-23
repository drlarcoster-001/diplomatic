<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REVERSO DE OPERACIONES
 * ARCHIVO: app/views/financial/reverse_operations/index.php
 * PROPÓSITO: Interfaz principal con soporte para filas clickables y gestión de pestañas.
 * VERSIÓN: 1.6.1 - UX: Soporte para disparadores de fila completa y estandarización de rutas.
 */

declare(strict_types=1);

$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_reverse_operations.css?v=<?= time() ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0 d-inline-flex">
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Finanzas</a></li>
            <li class="breadcrumb-item small active fw-bold text-danger" aria-current="page">Reverso de Operaciones</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-arrow-counterclockwise text-danger me-2"></i> Reverso de Operaciones
            </h4>
            <p class="text-muted small mb-0">Bandeja de auditoría. Haga clic en cualquier fila para iniciar el proceso de anulación.</p>
        </div>
        <a href="<?= $basePath ?>/financial" class="btn btn-light btn-sm rounded-pill px-4 border shadow-sm text-dark fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        
        <div class="card-header bg-white border-bottom-0 p-0 pt-3 px-3">
            <ul class="nav nav-tabs border-bottom" id="reverseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-danger px-4" id="inscripciones-tab" data-bs-toggle="tab" data-bs-target="#tab-inscripciones" type="button" role="tab">
                        <i class="bi bi-person-badge me-2"></i> INSCRIPCIONES
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-muted px-4" id="cuotas-tab" data-bs-toggle="tab" data-bs-target="#tab-cuotas" type="button" role="tab">
                        <i class="bi bi-journal-text me-2"></i> PAGOS DE CUOTAS
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4 bg-light">
            <div class="tab-content" id="reverseTabsContent">
                
                <div class="tab-pane fade show active" id="tab-inscripciones" role="tabpanel">
                    <form id="form-search-inscripciones" class="row g-3 align-items-end mb-4 bg-white p-3 rounded-3 shadow-sm border">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1">Filtrar Inscripciones Reversibles</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-funnel"></i></span>
                                <input type="text" class="form-control border-start-0" id="search-inscripcion" placeholder="Buscar por Cédula, Nombre o Diplomado..." autocomplete="off">
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive bg-white rounded-3 shadow-sm border">
                        <table class="table table-hover align-middle mb-0 clickable-table" id="grid-inscripciones">
                            <thead class="table-light">
                                <tr class="text-uppercase small text-muted">
                                    <th class="ps-3" style="width: 15%">Fecha</th>
                                    <th style="width: 25%">Participante</th>
                                    <th style="width: 15%">Cédula</th>
                                    <th style="width: 20%">Diplomado</th>
                                    <th style="width: 15%">Monto Pago</th>
                                    <th class="text-end pe-3" style="width: 10%">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="resultsInscripciones">
                                </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-cuotas" role="tabpanel">
                    <form id="form-search-cuotas" class="row g-3 align-items-end mb-4 bg-white p-3 rounded-3 shadow-sm border">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1">Filtrar Pagos de Cuotas</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control border-start-0" id="search-cuota" placeholder="Buscar por nombre del alumno, recibo o método..." autocomplete="off">
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive bg-white rounded-3 shadow-sm border">
                        <table class="table table-hover align-middle mb-0 clickable-table" id="grid-cuotas">
                            <thead class="table-light">
                                <tr class="text-uppercase small text-muted">
                                    <th class="ps-3" style="width: 15%">Fecha</th>
                                    <th style="width: 25%">Participante</th>
                                    <th style="width: 15%">Método</th>
                                    <th style="width: 15%">Referencia</th>
                                    <th style="width: 15%">Monto</th>
                                    <th class="text-end pe-3" style="width: 10%">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="resultsCuotas">
                                </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const BASE_URL = '<?= $basePath ?>';
</script>

<script src="<?= $basePath ?>/assets/js/financial_reverse_operations.js?v=<?= time() ?>"></script>