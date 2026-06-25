<?php
/**
 * MÓDULO: GESTIÓN GENERAL / GERENCIAL
 * ARCHIVO: app/views/managerial/index.php
 * PROPÓSITO: Dashboard principal gerencial con acceso total a reportes estratégicos.
 * VERSIÓN: 1.2.0 - Limpieza de entorno: Eliminación de lógica de módulos en desarrollo.
 */

declare(strict_types=1);

// Cálculo dinámico de la ruta base para assets y enlaces
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/managerial.css?v=<?= time() ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="mb-5">
        <h2 class="h4 fw-bold mb-1 text-dark">Panel Gerencial</h2>
        <p class="text-muted small">Indicadores estratégicos y reportes consolidados para la toma de decisiones institucionales.</p>
    </div>

    <div class="row g-4">
        
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/payments-report" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-funnel fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-success">Reporte de Pagos de Inscritos</h5>
                    <p class="text-muted small mb-0">Análisis de prospectos, tasas de conversión y abonos iniciales en proceso de formalización.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/pending-payments" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #fd7e14 !important;">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-hourglass-split fs-2" style="color: #fd7e14;"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #fd7e14;">Control de Pagos Pendientes</h5>
                    <p class="text-muted small mb-0">Revisión, auditoría y validación de recibos o transferencias reportadas por los estudiantes.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/movements-report" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #6610f2 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(102, 16, 242, 0.1);">
                        <i class="bi bi-arrow-left-right fs-2" style="color: #6610f2;"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6610f2;">Reporte Gerencial de Movimientos</h5>
                    <p class="text-muted small mb-0">Trazabilidad completa de operaciones financieras (Inscripción + 5 Cuotas) y balance de transacciones.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/academic-control" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-check-fill fs-2"></i> 
                    </div>
                    <h5 class="fw-bold text-primary">Control Académico</h5>
                    <p class="text-muted small mb-0">Seguimiento de matrículas, validación de expedientes y estatus de egreso por cohorte.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/bank-reconciliation" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #20c997 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(32, 201, 151, 0.1);">
                        <i class="bi bi-bank fs-2" style="color: #20c997;"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #20c997;">Conciliación Bancaria</h5>
                    <p class="text-muted small mb-0">Auditoría cruzada de pagos móviles, detección de remanentes y vinculación de transferencias.</p>
                </div>
            </a>
        </div>

        <!-- Estado de Resultados -->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/estado-resultados" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #533AB7 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background:rgba(83,58,183,0.1);color:#533AB7">
                        <i class="bi bi-bar-chart-steps fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#533AB7">Estado de Resultados</h5>
                    <p class="text-muted small mb-0">Ingresos, egresos y saldo por período. Exportable a PDF.</p>
                </div>
            </a>
        </div>

        <!-- Libro de Egresos -->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/libro-egresos" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width:fit-content">
                        <i class="bi bi-journal-minus fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Libro de Egresos</h5>
                    <p class="text-muted small mb-0">Registro detallado de todos los pagos realizados.</p>
                </div>
            </a>
        </div>

        <!-- Reporte de Pagos -->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/pagos-reporte" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width:fit-content">
                        <i class="bi bi-cash-coin fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Reporte de Pagos</h5>
                    <p class="text-muted small mb-0">Pagos validados por período, diplomado, cohorte y estudiante.</p>
                </div>
            </a>
        </div>

        <!--Línea de Tiempo -->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/managerial/linea-tiempo" class="card-managerial-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #20c997 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background:rgba(32,201,151,0.1);color:#20c997">
                        <i class="bi bi-person-lines-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#20c997">Línea de Tiempo</h5>
                    <p class="text-muted small mb-0">Ciclo completo de vida de un estudiante dentro del diplomado.</p>
                </div>
            </a>
        </div>

    </div>
</div>