<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / RECHAZOS DE PAGO
 * ARCHIVO: app/views/financial/payment_rejection/index.php
 * PROPÓSITO: Interfaz con pestañas duales y filas interactivas para visualizar detalles de rechazo en popups.
 * VERSIÓN: 1.1.0 - Fix: Limpieza de grilla para soportar clics en filas y rutas con soporte /diplomatic/public/.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_payment_rejection.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none">Panel Financiero</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gestión de Rechazos</li>
                </ol>
            </nav>
            <h2 class="h4 fw-bold mb-0"><i class="bi bi-x-octagon-fill text-dark me-2"></i> Gestión de Rechazos de Pago</h2>
        </div>
        <a href="<?= htmlspecialchars($basePath) ?>/financial" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al Panel
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <ul class="nav nav-tabs nav-justified border-bottom" id="rejectionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark" id="inscripciones-tab" data-bs-toggle="tab" data-bs-target="#inscripciones" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1"></i> Inscripciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-dark" id="regulares-tab" data-bs-toggle="tab" data-bs-target="#regulares" type="button" role="tab">
                        <i class="bi bi-people-fill me-1"></i> Pagos Regulares
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body bg-light">
            <div class="tab-content" id="rejectionTabsContent">
                
                <div class="tab-pane fade show active" id="inscripciones" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <input type="text" id="searchInscripciones" class="form-control form-control-sm w-25 shadow-sm border-0" placeholder="Buscar por cédula o nombre...">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Clic en una fila para ver detalles y opciones</small>
                    </div>
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="small">FECHA DE PAGO</th>
                                    <th class="small">CÉDULA</th>
                                    <th class="small">NOMBRES</th>
                                    <th class="small">DIPLOMADO</th>
                                    <th class="small text-end">MONTO BS</th>
                                    <th class="small text-end">MONTO USD</th>
                                    <th class="small text-center">TIPO</th>
                                    <th class="small text-center"><i class="bi bi-search"></i></th>
                                </tr>
                            </thead>
                            <tbody id="resultsInscripciones"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="regulares" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <input type="text" id="searchRegulares" class="form-control form-control-sm w-25 shadow-sm border-0" placeholder="Buscar por expediente o nombre...">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Clic en una fila para ver detalles y opciones</small>
                    </div>
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="small">FECHA DE PAGO</th>
                                    <th class="small">EXPEDIENTE</th>
                                    <th class="small">NOMBRES</th>
                                    <th class="small">DIPLOMADO</th>
                                    <th class="small text-end">MONTO BS</th>
                                    <th class="small text-end">MONTO USD</th>
                                    <th class="small text-center">TIPO</th>
                                    <th class="small text-center"><i class="bi bi-search"></i></th>
                                </tr>
                            </thead>
                            <tbody id="resultsRegulares"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>const BASE_URL = '<?= htmlspecialchars($basePath) ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_payment_rejection.js"></script>