<?php
/**
 * MÓDULO: FINANCIERO / TASA DE CAMBIO
 * ARCHIVO: app/views/financial/exchange_rates/index.php
 * PROPÓSITO: Gestión de tasas BCV con interactividad en grid y soporte de paginación.
 * VERSIÓN: 2.4.0 - Inclusión de columna correlativa y atributos de Euro para detalle.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$fechaHoy = date('d/m/Y');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_exchange_rates.css?v=<?= time() ?>">

<div class="financial-exchange-rates-scope">
    <div class="container-fluid py-4">

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
                <li class="breadcrumb-item small">
                    <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted fw-medium">
                        <i class="bi bi-house-door me-1"></i> Inicio
                    </a>
                </li>
                <li class="breadcrumb-item small">
                    <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted fw-medium">
                        Panel Financiero
                    </a>
                </li>
                <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">
                    Tasas de Cambio
                </li>
            </ol>
        </nav>

        <div class="d-flex align-items-center mb-4">
            <a href="<?= $basePath ?>/financial" class="btn-back-financial shadow-sm d-flex align-items-center justify-content-center text-decoration-none">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="ms-3">
                <h2 class="h3 fw-bold mb-0 text-dark">Tasas de Cambio BCV</h2>
                <p class="text-muted mb-0">Monitoreo y registro oficial de divisas.</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
            <div class="flex-fill">
                <div class="info-card-pm bg-white border-start border-5 border-primary rounded shadow-sm p-3">
                    <span class="label-pm text-primary text-uppercase">Fecha Sistema</span>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-calendar3 text-primary me-2 fs-4"></i>
                        <span class="value-pm"><?= $fechaHoy ?></span>
                    </div>
                </div>
            </div>

            <div class="flex-fill">
                <div class="info-card-pm bg-white border-start border-5 border-info rounded shadow-sm p-3">
                    <span class="label-pm text-info text-uppercase">Reloj Local</span>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-clock text-info me-2 fs-4"></i>
                        <span class="value-pm font-monospace" id="real-time-clock">--:--:--</span>
                    </div>
                </div>
            </div>

            <div class="flex-fill">
                <div class="info-card-pm bg-white border-start border-5 border-success rounded shadow-sm p-3">
                    <span class="label-pm text-success text-uppercase">Último Dólar BCV</span>
                    <div class="d-flex align-items-center mt-1">
                        <i class="bi bi-currency-exchange text-success me-2 fs-4"></i>
                        <span class="value-pm" id="display-usd">
                            <?= number_format((float)($last_usd ?? 0), 2, ',', '.') ?> 
                            <small class="text-muted fs-6">Bs.</small>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-auto d-flex gap-2">
                <button type="button" id="btnConsultarBCV" class="btn btn-primary rounded-3 shadow-sm px-4 fw-bold d-flex align-items-center justify-content-center border-0" style="height: 85px;">
                    <div class="text-center">
                        <i class="bi bi-search d-block mb-1 fs-5"></i>
                        <span style="font-size: 0.7rem; letter-spacing: 1px;">CONSULTAR</span>
                    </div>
                </button>

                <button type="button" id="btnManualRegister" class="btn btn-dark rounded-3 shadow-sm px-4 fw-bold d-flex align-items-center justify-content-center border-0" style="height: 85px;">
                    <div class="text-center">
                        <i class="bi bi-pencil-square d-block mb-1 fs-5"></i>
                        <span style="font-size: 0.7rem; letter-spacing: 1px;">REGISTRO</span>
                    </div>
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
            <div class="card-body py-3 px-4">
                <form class="row g-3 align-items-center justify-content-center" method="GET" action="">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 small fw-bold text-muted">DESDE</span>
                            <input type="date" name="desde" class="form-control border-0 shadow-none rounded-end-pill" value="<?= $_GET['desde'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 small fw-bold text-muted">HASTA</span>
                            <input type="date" name="hasta" class="form-control border-0 shadow-none rounded-end-pill" value="<?= $_GET['hasta'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Filtrar</button>
                    </div>
                    <?php if(isset($_GET['desde'])): ?>
                        <div class="col-md-1">
                            <a href="<?= $basePath ?>/financial/exchange_rates" class="btn btn-link btn-sm text-decoration-none text-danger fw-bold">Limpiar</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-white">
                        <tr>
                            <th class="ps-4 text-start fw-bold text-muted py-3" style="font-size: 0.75rem; width: 50px;">#</th>
                            <th class="text-start fw-bold text-muted py-3" style="font-size: 0.75rem;">FECHA REGISTRO</th>
                            <th class="fw-bold text-muted" style="font-size: 0.75rem;">DÓLAR BCV</th>
                            <th class="pe-4 text-end fw-bold text-muted" style="font-size: 0.75rem;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="gridTasas">
                        <?php if (!empty($history)): ?>
                            <?php 
                            // Lógica para numeración correlativa basada en la página actual
                            $currentPage = $pagination['current_page'] ?? 1;
                            $itemsPerPage = 25; // Asumiendo 25 por página según el controlador
                            $counter = ($currentPage - 1) * $itemsPerPage + 1;
                            ?>
                            <?php foreach ($history as $row): ?>
                                <tr class="rate-row-clickable" style="cursor: pointer;"
                                    data-date="<?= date('d/m/Y', strtotime($row['rate_date'])) ?>"
                                    data-time="<?= date('h:i:s A', strtotime($row['created_at'])) ?>"
                                    data-usd="<?= number_format((float)$row['dolar_bcv'], 2, ',', '.') ?>"
                                    data-eur="<?= number_format((float)$row['euro_bcv'], 2, ',', '.') ?>">
                                    
                                    <td class="ps-4 text-start">
                                        <span class="text-muted fw-medium"><?= $counter++ ?></span>
                                    </td>

                                    <td class="text-start">
                                        <span class="fw-bold fs-6"><?= date('d/m/Y', strtotime($row['rate_date'])) ?></span>
                                        <span class="text-muted small ms-2"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 fs-6 border-0">
                                            <?= number_format((float)$row['dolar_bcv'], 2, ',', '.') ?> Bs.
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-delete-rate" 
                                                data-id="<?= $row['id'] ?>"
                                                data-day="<?= date('d/m/Y', strtotime($row['rate_date'])) ?>"
                                                onclick="event.stopPropagation();">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-5 text-center text-muted">
                                    <i class="bi bi-info-circle me-1"></i> No se encontraron registros.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <div class="card-footer bg-white border-top-0 py-3 px-4 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Mostrando desde el registro <b><?= $pagination['start_index'] ?></b>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link shadow-none border-0 rounded-circle mx-1" 
                                           href="?page=<?= $i ?><?= isset($_GET['desde']) ? '&desde='.$_GET['desde'] : '' ?><?= isset($_GET['hasta']) ? '&hasta='.$_GET['hasta'] : '' ?>">
                                           <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/financial_exchange_rates.js?v=<?= time() ?>"></script>