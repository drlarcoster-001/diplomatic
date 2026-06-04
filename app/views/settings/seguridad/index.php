<?php
/**
 * MÓDULO: CONFIGURACIÓN / SEGURIDAD
 * ARCHIVO: app/views/settings/seguridad/index.php
 * PROPÓSITO: Panel de administración de pre-users y tokens vencidos.
 */
declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$summary = $summary ?? ['total_pre_users' => 0, 'pending' => 0, 'verified' => 0, 'tokens_vencidos' => 0];

?>

<div class="container-fluid py-4 animate__animated animate__fadeIn">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/settings" class="text-decoration-none text-muted">Configuración</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Seguridad</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Seguridad del Sistema</h2>
            <p class="text-muted small mb-0">Administración de pre-usuarios y tokens de activación vencidos.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary fw-bold rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-danger fw-bold rounded-pill px-4 shadow-sm" id="btn-clean-expired">
                <i class="bi bi-trash3-fill me-1"></i> Limpiar Tokens Vencidos
            </button>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center">
                <div class="fs-2 fw-bold text-dark"><?= $summary['total_pre_users'] ?></div>
                <div class="small text-muted">Total Pre-Usuarios</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center">
                <div class="fs-2 fw-bold text-warning"><?= $summary['pending'] ?></div>
                <div class="small text-muted">Pendientes</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center">
                <div class="fs-2 fw-bold text-success"><?= $summary['verified'] ?></div>
                <div class="small text-muted">Verificados</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 text-center">
                <div class="fs-2 fw-bold text-danger"><?= $summary['tokens_vencidos'] ?></div>
                <div class="small text-muted">Tokens Vencidos</div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="filter-text" placeholder="Nombre, email, cédula...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Estatus</label>
                    <select class="form-select form-select-sm" id="filter-status">
                        <option value="">Todos</option>
                        <option value="PENDING">Pendiente</option>
                        <option value="VERIFIED">Verificado</option>
                        <option value="EXPIRED">Expirado</option>
                        <option value="BLOCKED">Bloqueado</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-secondary btn-sm me-2 fw-bold" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-dark btn-sm fw-bold px-4" id="btn-search">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill text-warning me-2"></i>Registro de Pre-Usuarios</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-pre-users">
                    <thead class="table-light sticky-top" style="font-size: 0.75rem;">
                        <tr class="text-uppercase text-muted">
                            <th class="ps-4">#</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Cédula</th>
                            <th class="text-center">Estatus</th>
                            <th class="text-center">Token</th>
                            <th class="text-center">Usuario Real</th>
                            <th>Registro</th>
                            <th class="text-center pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="spinner-border text-warning" role="status"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_PATH = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/settings_security.js"></script>
