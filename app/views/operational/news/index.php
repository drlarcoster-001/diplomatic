<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / NEWS (CARTELERA)
 * ARCHIVO: app/views/operational/news/index.php
 * PROPÓSITO: Interfaz principal para la gestión de publicaciones en la cartelera web.
 * VERSIÓN: 1.1.0 - UX Fix: Terminología "Publicación" y "Página Web".
 */
declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_news.css?v=<?= time() ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Panel Operativo</a></li>
                    <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Cartelera Web</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Gestión de Publicaciones</h4>
        </div>
        <div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm me-2 fw-bold" onclick="openTextModal(0)">
                <i class="bi bi-plus-circle me-1"></i> Nueva Publicación
            </button>
            <a href="<?= $basePath ?>/operational" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Buscar por Título</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="search-input" class="form-control border-start-0" placeholder="Ej: Convenio, Inscripciones...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" id="incomplete-filter">
                        <label class="form-check-label small fw-bold" for="incomplete-filter">Solo Incompletas</label>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" id="btn-clear" class="btn btn-light rounded-pill px-3 me-1 fw-bold">Limpiar</button>
                    <button type="button" id="btn-filter" class="btn btn-dark rounded-pill px-4 fw-bold">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-news">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="ps-4" style="width: 40px;"><input type="checkbox" id="check-all" class="form-check-input"></th>
                        <th>Publicación</th>
                        <th class="text-center">Recursos Web</th>
                        <th class="text-center">¿Lista?</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody id="news-list">
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Sincronizando cartelera...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/operational_news.js?v=<?= time() ?>"></script>