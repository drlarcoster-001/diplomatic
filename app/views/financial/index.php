<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA
 * ARCHIVO: app/views/financial/index.php
 * PROPÓSITO: Dashboard principal del módulo financiero.
 * VERSIÓN: 1.6.0 - Carpeta visual "Operaciones de Egreso" con mismo tamaño de tarjetas.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial.css">

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel Financiero</h2>
        <p class="text-muted small">Control de ingresos, conciliación de pagos y gestión de indicadores económicos.</p>
    </div>

    <div class="row g-4">

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-success">Validación de Inscripción</h5>
                    <p class="text-muted small mb-0">Verificación de pagos iniciales para activar nuevos ingresos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_registration" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #ffc107 !important;">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-cash-coin fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-warning">Registro de Pagos</h5>
                    <p class="text-muted small mb-0">Carga manual de abonos y cuotas desde la taquilla administrativa.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_validations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-receipt fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Validaciones de Pago</h5>
                    <p class="text-muted small mb-0">Gestión y control de pagos de estudiantes regulares.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/student_statement" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #6610f2 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(102, 16, 242, 0.1); color: #6610f2;">
                        <i class="bi bi-file-earmark-person-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6610f2;">Estados de Cuenta</h5>
                    <p class="text-muted small mb-0">Consulta de solvencia, historial de cargos y pagos por estudiante.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/reverse_operations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-arrow-counterclockwise fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Reverso de Operaciones</h5>
                    <p class="text-muted small mb-0">Anulación de recibos y corrección de abonos contables.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_rejections" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #212529 !important;">
                    <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-x-octagon-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Rechazos de Pago</h5>
                    <p class="text-muted small mb-0">Gestión de pagos rechazados y eliminación de inscripciones.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/exchange_rates" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-currency-exchange fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Tasa de Cambio</h5>
                    <p class="text-muted small mb-0">Actualización y gestión de divisas e históricos BCV.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/bank_statements" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-bank fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0dcaf0;">Estados Bancarios</h5>
                    <p class="text-muted small mb-0">Carga y gestión de archivos bancarios para conciliación.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/gasto-categorias" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-tags-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-success">Categorías de Gasto</h5>
                    <p class="text-muted small mb-0">Clasificación contable de alto nivel para egresos institucionales.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/gasto-conceptos" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-tag-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Conceptos de Gasto</h5>
                    <p class="text-muted small mb-0">Clasificación detallada de egresos vinculada a categorías.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/proveedores" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #fd7e14 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(253,126,20,0.1); color:#fd7e14;">
                        <i class="bi bi-building fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#fd7e14;">Proveedores</h5>
                    <p class="text-muted small mb-0">Catálogo de proveedores externos vinculados al programa.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/pagos-proveedores" class="text-decoration-none text-dark card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #533AB7 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(83,58,183,0.1); color: #533AB7;">
                        <i class="bi bi-receipt-cutoff fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #533AB7;">Pagos a Proveedores</h5>
                    <p class="text-muted small">Facturación y pagos a proveedores registrados.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/aprobar-pagos" class="text-decoration-none text-dark card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-clipboard2-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Aprobar Pagos a Proveedores</h5>
                    <p class="text-muted small">Revisión y aprobación de pagos procesados a proveedores.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/ordenes-pago" class="text-decoration-none text-dark card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0C447C !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-journal-check fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0C447C;">Órdenes de Pago</h5>
                    <p class="text-muted small">Revisión y aprobación de todas las órdenes generadas.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/tesoreria" class="text-decoration-none text-dark card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Tesorería</h5>
                    <p class="text-muted small">Ejecución de pagos sobre órdenes aprobadas.</p>
                </div>
            </a>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- CARPETA: OPERACIONES DE EGRESO                          -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/egresos" class="carpeta-link">
                <div class="carpeta-wrap shadow-sm">
                    <div class="carpeta-pestaña"></div>
                    <div class="carpeta-docs">
                        <div class="carpeta-doc-1"></div>
                        <div class="carpeta-doc-2"></div>
                        <div class="carpeta-doc-3"></div>
                    </div>
                    <div class="carpeta-cuerpo">
                        <div class="carpeta-icono">
                            <i class="bi bi-folder-fill"></i>
                        </div>
                        <p class="carpeta-titulo">Operaciones<br>de Egreso</p>
                        <p class="carpeta-desc">Dashboard financiero y libro de todos los pagos realizados.</p>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>