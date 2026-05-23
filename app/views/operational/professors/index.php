<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / PROFESSORS
 * ARCHIVO: app/views/operational/professors/index.php
 * PROPÓSITO: Interfaz de usuario para la gestión de docentes en el sitio público (Staff Web).
 * VERSIÓN: 1.1.0 - UX Fix: Terminología amigable "Página Web" y sincronización de BASE_URL.
 */

declare(strict_types=1);

// Estandarización de la ruta base para el ecosistema del proyecto
$basePath = '/diplomatic/public';
$specialties = $data['specialties'] ?? []; // Recibido desde OperationalProfessorsController
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_professors.css?v=<?= time() ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Panel Operativo</a></li>
                    <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Staff Web</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Gestión de Staff en Página Web</h4>
        </div>
        <div>
            <a href="<?= $basePath ?>/operational" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Búsqueda rápida</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="search-input" class="form-control border-start-0" placeholder="Nombre, apellido o ID...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Filtrar por Especialidad</label>
                    <select id="specialty-filter" class="form-select">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($specialties as $s): ?>
                            <option value="<?= htmlspecialchars((string)$s) ?>"><?= htmlspecialchars((string)$s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" id="incomplete-filter">
                        <label class="form-check-label small fw-bold" for="incomplete-filter">Ver Incompletos</label>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" id="btn-clear" class="btn btn-light rounded-pill px-3 me-1 fw-bold">
                        <i class="bi bi-eraser"></i>
                    </button>
                    <button type="button" id="btn-filter" class="btn btn-dark rounded-pill px-4 fw-bold">
                        Filtrar Lista
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-professors">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="ps-4" style="width: 40px;"><input type="checkbox" id="check-all" class="form-check-input"></th>
                        <th>Docente / Datos de Contacto</th>
                        <th>Especialidad</th>
                        <th class="text-center">Recursos Web</th>
                        <th class="text-center">¿Listo?</th>
                        <th class="text-center">Estatus Web</th>
                        <th class="text-end pe-4">Acciones Operativas</th>
                    </tr>
                </thead>
                <tbody id="professors-list" class="border-top-0">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Sincronizando lista de docentes...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script> 
    /**
     * IMPORTANTE: Esta constante permite que el JS externo sepa dónde realizar
     * las peticiones sin importar el entorno (Local o Servidor).
     */
    const BASE_URL = '<?= $basePath ?>'; 
</script>

<script src="<?= $basePath ?>/assets/js/operational_professors.js?v=<?= time() ?>"></script>