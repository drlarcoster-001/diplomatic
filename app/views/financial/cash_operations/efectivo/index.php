<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EFECTIVO (CASH)
 * ARCHIVO: app/views/financial/cash_operations/efectivo/index.php
 * PROPÓSITO: Interfaz de arqueo y conciliación para cobros físicos en ventanilla.
 * VERSIÓN: 1.2.0 - FIX: Sincronización de rutas con Bootstrap.php y prevención de caché JS.
 */

declare(strict_types=1);

// Estandarización de rutas para entorno de subcarpeta
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$fechaHoy = date('d/m/Y');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_cash_operations_efectivo.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="text-decoration-none text-muted">Caja Operativa</a>
            </li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Conciliación Efectivo</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Cobros en Efectivo (USD)</h2>
            <p class="text-muted small mb-0">Listado de inscripciones pendientes por recepción física de divisas.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="btn btn-outline-secondary fw-bold shadow-sm px-3 d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filter-form-efectivo" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Buscar Estudiante (Cédula o Nombre)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: V-12345678 o Nombre del alumno...">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold me-2" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i>Limpiar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                        <i class="bi bi-funnel me-1"></i>Filtrar Lista
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-cash-stack text-success me-2"></i>Compromisos de Pago por Procesar
            </h6>
            <span class="badge bg-light text-dark border fw-normal">Clic en cualquier fila para abrir arqueo</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-efectivo-pending">
                    <thead class="table-light sticky-top">
                        <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                            <th class="ps-4">Fecha Inscripción</th>
                            <th>Estudiante</th>
                            <th>Cédula</th>
                            <th>Diplomado</th>
                            <th class="text-end">Monto Pactado ($)</th>
                            <th class="text-center pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalValidateEfectivo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-calculator-fill me-2 text-success"></i>Arqueo de Recepción Física
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 bg-light p-3 rounded border g-0">
                    <div class="col-md-7 border-end">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">Información del Estudiante</h6>
                        <span id="v-estudiante" class="d-block fw-bold text-dark fs-5">Cargando...</span>
                        <span id="v-diplomado" class="text-muted small">-</span>
                    </div>
                    <div class="col-md-5 text-end ps-3">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">Monto por Recibir</h6>
                        <span id="v-monto-pactado" class="d-block fw-bold text-primary fs-2">$ 0.00</span>
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-coin me-2"></i>Desglose de Denominaciones (Dólares)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle bill-table">
                        <thead class="table-light text-center small fw-bold">
                            <tr>
                                <th>Billete</th>
                                <th width="140">Cantidad</th>
                                <th class="text-end">Subtotal ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ([100, 50, 20, 10, 5, 1] as $den): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">Billete de $<?= $den ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm bill-input text-center fw-bold" 
                                           data-den="<?= $den ?>" value="0" min="0" 
                                           oninput="window.calculateTotal()">
                                </td>
                                <td class="text-end pe-3 subtotal-display" id="sub-<?= $den ?>">$ 0.00</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="total-cash-box d-flex justify-content-between align-items-center mt-3 p-3 rounded bg-light border">
                    <div class="ps-2">
                        <span class="d-block small text-muted fw-bold text-uppercase">Total Contabilizado</span>
                        <span id="monto-contado" class="fs-1 fw-bold text-danger">$ 0.00</span>
                    </div>
                    <div id="status-message" class="text-end pe-2">
                        <span class="badge bg-danger p-2"><i class="bi bi-exclamation-triangle me-1"></i> Falta dinero</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger fw-bold px-3 shadow-sm" id="btn-reject-cash">
                        <i class="bi bi-x-circle me-1"></i> Rechazar Cobro
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold px-5 shadow-sm" id="btn-confirm-cash" disabled>
                        <i class="bi bi-check-circle-fill me-1"></i> Validar e Inscribir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script> 
    const BASE_URL = '<?= $basePath ?>'; 
</script>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_cash_operations_efectivo.js?v=<?= time() ?>"></script>