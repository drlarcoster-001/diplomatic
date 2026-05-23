<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/views/financial/payment_validations/index.php
 * PROPÓSITO: Panel principal con tarjetas horizontales centradas y botón de regreso.
 * VERSIÓN: 1.3.1 - Fix de layout: Integración de botón "Volver" y eliminación de tarjeta de reportes inactiva.
 */

declare(strict_types=1);

$basePath = '/diplomatic/public';
$counts = $data['counts'] ?? [];
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_payment_validations.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 900px;">
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
                    <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                    <li class="breadcrumb-item small"><a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Finanzas</a></li>
                    <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Bandeja de Validación</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        <i class="bi bi-shield-check text-primary me-2"></i> Bandeja de Validación de Pagos
                    </h4>
                    <p class="text-muted small">Seleccione un método de pago para verificar y aprobar las transacciones reportadas.</p>
                </div>
                <a href="<?= $basePath ?>/financial" class="btn btn-light btn-sm rounded-pill px-3 border shadow-sm text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 justify-content-center">
        <div class="col-12" style="max-width: 900px;">
            
            <div class="card border-0 shadow-sm rounded-4 mb-3 card-financial-option" data-route="pagomovil">
                <div class="card-body p-4 d-flex align-items-center border-start border-4 border-primary rounded-start-4 bg-white">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                        <i class="bi bi-phone-vibrate fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">Pago Móvil</h5>
                        <p class="text-muted small mb-0">Conciliación de transferencias interbancarias nacionales en Bolívares.</p>
                    </div>
                    <div class="ms-3 d-flex align-items-center">
                        <?php $c_pm = $counts['PAGOMOVIL'] ?? 0; ?>
                        <span class="badge bg-danger rounded-pill px-3 py-2 me-3 shadow-sm <?= $c_pm > 0 ? 'animate__animated animate__headShake' : 'd-none' ?>" id="badge-PAGOMOVIL">
                            <?= $c_pm ?> Pendiente<?= $c_pm !== 1 ? 's' : '' ?>
                        </span>
                        <i class="bi bi-chevron-right fs-4 text-primary"></i>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-3 card-financial-option" data-route="zelle">
                <div class="card-body p-4 d-flex align-items-center border-start border-4 border-info rounded-start-4 bg-white">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                        <i class="bi bi-currency-dollar fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">Zelle</h5>
                        <p class="text-muted small mb-0">Verificación de transferencias internacionales (BOA y otros bancos USD).</p>
                    </div>
                    <div class="ms-3 d-flex align-items-center">
                        <?php $c_zelle = $counts['ZELLE'] ?? 0; ?>
                        <span class="badge bg-danger rounded-pill px-3 py-2 me-3 shadow-sm <?= $c_zelle > 0 ? 'animate__animated animate__headShake' : 'd-none' ?>" id="badge-ZELLE">
                            <?= $c_zelle ?> Pendiente<?= $c_zelle !== 1 ? 's' : '' ?>
                        </span>
                        <i class="bi bi-chevron-right fs-4 text-info"></i>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-3 card-financial-option" data-route="binance">
                <div class="card-body p-4 d-flex align-items-center border-start border-4 border-warning rounded-start-4 bg-white">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                        <i class="bi bi-currency-bitcoin fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">Binance Pay</h5>
                        <p class="text-muted small mb-0">Validación de pagos mediante criptoactivos y saldos en USDT.</p>
                    </div>
                    <div class="ms-3 d-flex align-items-center">
                        <?php $c_binance = $counts['BINANCE'] ?? 0; ?>
                        <span class="badge bg-danger rounded-pill px-3 py-2 me-3 shadow-sm <?= $c_binance > 0 ? 'animate__animated animate__headShake' : 'd-none' ?>" id="badge-BINANCE">
                            <?= $c_binance ?> Pendiente<?= $c_binance !== 1 ? 's' : '' ?>
                        </span>
                        <i class="bi bi-chevron-right fs-4 text-warning"></i>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-3 card-financial-option" data-route="efectivo">
                <div class="card-body p-4 d-flex align-items-center border-start border-4 border-success rounded-start-4 bg-white">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1">Efectivo / Taquilla</h5>
                        <p class="text-muted small mb-0">Confirmación de cobros presenciales y depósitos en divisas.</p>
                    </div>
                    <div class="ms-3 d-flex align-items-center">
                        <?php $c_efectivo = $counts['EFECTIVO'] ?? 0; ?>
                        <span class="badge bg-danger rounded-pill px-3 py-2 me-3 shadow-sm <?= $c_efectivo > 0 ? 'animate__animated animate__headShake' : 'd-none' ?>" id="badge-EFECTIVO">
                            <?= $c_efectivo ?> Pendiente<?= $c_efectivo !== 1 ? 's' : '' ?>
                        </span>
                        <i class="bi bi-chevron-right fs-4 text-success"></i>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/financial_payment_validations.js"></script>