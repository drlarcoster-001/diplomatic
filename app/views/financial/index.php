<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA
 * ARCHIVO: app/views/financial/index.php
 * PROPÓSITO: Dashboard principal del módulo financiero con acceso a operaciones, validación y gestión de estatus.
 * VERSIÓN: 1.4.0 - Fix de rutas absolutas para soporte /diplomatic/public/ e incorporación del acceso a Gestión de Rechazos de Pago.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial.css">

<div class="container-fluid py-4">
    <div class="mb-4 animate__animated animate__fadeIn">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel Financiero</h2>
        <p class="text-muted small">Control de ingresos, conciliación de pagos y gestión de indicadores económicos.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-success" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-check-fill fs-2"></i> 
                    </div>
                    <h5 class="fw-bold text-success">Validación de Inscripción</h5>
                    <p class="text-muted small mb-0">Verificación de pagos iniciales (Inscripción + Cuota 1) para activar nuevos ingresos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_registration" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-warning" style="border-top: 4px solid #ffc107 !important;">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-cash-coin fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-warning">Registro de Pagos</h5>
                    <p class="text-muted small mb-0">Carga manual de abonos y cuotas de estudiantes desde la taquilla administrativa.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_validations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-primary" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-receipt fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Validaciones de Pago</h5>
                    <p class="text-muted small mb-0">Gestión y Control de pagos de estudiantes regulares.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/student_statement" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-indigo" style="border-top: 4px solid #6610f2 !important;">
                    <div class="bg-indigo bg-opacity-10 p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(102, 16, 242, 0.1); color: #6610f2;">
                        <i class="bi bi-file-earmark-person-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6610f2;">Estados de Cuenta</h5>
                    <p class="text-muted small mb-0">Consulta detallada de solvencia, historial de cargos y pagos por estudiante.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/reverse_operations" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-arrow-counterclockwise fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Reverso de Operaciones</h5>
                    <p class="text-muted small mb-0">Anulación de recibos, corrección de abonos y reverso de transacciones contables.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_rejections" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-dark" style="border-top: 4px solid #212529 !important;">
                    <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-x-octagon-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Rechazos de Pago / Eliminar Inscripción</h5>
                    <p class="text-muted small mb-0">Gestión de pagos rechazados: eliminación física o reactivación de soporte.</p>
                </div>
            </a>
        </div>

         <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/exchange_rates" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center border-top-primary" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-currency-exchange fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Tasa de Cambio</h5>
                    <p class="text-muted small mb-0">Actualización y gestión de divisas, históricos y tasas BCV oficiales.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/bank_statements" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-bank fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0dcaf0;">Estados de Cuenta Bancarios</h5>
                    <p class="text-muted small mb-0">Carga y gestión de archivos bancarios para conciliación de pagos.</p>
                </div>
            </a>
        </div>


    </div>
</div>