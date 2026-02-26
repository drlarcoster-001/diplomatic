<?php
/**
 * MÓDULO: CONFIGURACIÓN - MONITOREO
 * Archivo: app/views/settings/eventos.php
 * Propósito: Consola de auditoría en tiempo real para el seguimiento de eventos y seguridad del sistema.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/events.css?v=<?= time() ?>">

<div class="container-fluid py-4">

    <div class="d-flex justify-content-end mb-3">
        <a href="<?= $basePath ?>/settings" class="btn btn-light px-4 fw-bold rounded-3 border shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
        <form id="filter-events" class="row g-3 align-items-end" data-basepath="<?= $basePath ?>" onsubmit="event.preventDefault(); filterLogs();">
            <div class="col-lg-3">
                <label class="form-label small fw-bold">Palabra clave</label>
                <input type="text" name="search" class="form-control bg-light border-0" placeholder="Escribe y presiona Enter...">
            </div>
            <div class="col-lg-3">
                <label class="form-label small fw-bold">Desde</label>
                <input type="date" name="date_from" class="form-control bg-light border-0">
            </div>
            <div class="col-lg-3">
                <label class="form-label small fw-bold">Hasta</label>
                <input type="date" name="date_to" class="form-control bg-light border-0">
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-bold shadow-sm">Filtrar</button>
                <button type="button" class="btn btn-outline-secondary w-100 fw-bold shadow-sm" onclick="clearFilters()">Limpiar</button>
            </div>
        </form>
    </div>

    <div class="console-wrapper shadow-lg rounded-4 overflow-hidden border border-dark">
        <div class="console-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex gap-2">
                <span class="console-dot dot-red"></span>
                <span class="console-dot dot-yellow"></span>
                <span class="console-dot dot-green"></span>
            </div>
            <span class="console-title fw-mono small opacity-50">DIPLOMATIC_LIVE_MONITOR</span>
        </div>
        
        <div class="console-body p-4" id="event-logs">
            <?php if (!empty($logs)): foreach($logs as $log): ?>
                <div class="log-line mb-1">
                    <span class="log-date">[<?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>]</span>
                    <span class="log-ip mx-2" style="color: #00ffff !important;">[IP: <?= $log['ip_address'] ?>]</span>
                    <span class="log-module" style="color: #ffcc00 !important; font-weight: bold;">[<?= $log['module'] ?>]</span>
                    <span class="text-info fw-bold">@<?= $log['user_id'] ?? '1' ?></span>
                    <span class="text-success fw-bold mx-2">:: <?= strtoupper($log['action']) ?></span>
                    <span class="text-white fw-bold"><?= htmlspecialchars($log['description']) ?></span>
                </div>
            <?php endforeach; endif; ?>
            <div class="cursor">_</div>
        </div>

        <div class="console-footer d-flex justify-content-end p-2 bg-dark">
            <button class="btn btn-outline-success btn-xs fw-mono py-2 px-3" onclick="exportConsoleToCSV()">
                <i class="bi bi-download me-1"></i> DESCARGAR LOGS (CSV)
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/events.js?v=<?= time() ?>"></script>