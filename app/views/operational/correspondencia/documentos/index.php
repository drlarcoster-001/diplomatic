<?php
/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: app/views/operational/correspondencia/documentos/index.php
 * PROPÓSITO: Historial de todos los documentos generados, con buscador
 *            (por código o nombre de plantilla) y descarga/reimpresión.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;

function buildUrlCD(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_correspondencia_documentos.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Correspondencia</a></li>
            <li class="breadcrumb-item active fw-bold text-primary">Documentos Generados</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Documentos Generados</h2>
            <p class="text-muted small mb-0">Historial de cartas, memos, oficios, actas, reconocimientos y constancias emitidas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/operational/correspondencia/plantillas" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-file-earmark-text me-1"></i> Plantillas
            </a>
            <a href="<?= $basePath ?>/operational/correspondencia/documentos/create" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Generar Documentos
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por código o plantilla..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-dark w-100 rounded-pill">Buscar</button></div>
            </form>

            <div style="max-height:520px;overflow-y:auto">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-secondary text-uppercase" style="position:sticky;top:0;z-index:1">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Plantilla</th>
                            <th style="width:120px">Tipo</th>
                            <th style="width:150px">Tabla</th>
                            <th style="width:110px">Generado</th>
                            <th style="width:150px" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i> No hay documentos generados todavía.
                            </td></tr>
                        <?php else: foreach ($documentos as $d): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($d['codigo']) ?></td>
                                <td class="small"><?= htmlspecialchars($d['plantilla_nombre']) ?></td>
                                <td><span class="badge rounded-pill bg-light text-dark border"><?= htmlspecialchars($d['tipo_documento']) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars(str_replace('_', ' ', $d['tabla_objetivo'])) ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($d['generated_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?= $basePath ?>/operational/correspondencia/documentos/descargar?id=<?= $d['id'] ?>"
                                       target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 btn-delete-doc"
                                            data-id="<?= $d['id'] ?>" data-codigo="<?= htmlspecialchars($d['codigo']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?> · <?= $total ?> registros</small>
                    <nav><ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= buildUrlCD(['page'=>$page-1]) ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
                            <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= buildUrlCD(['page'=>$i]) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= buildUrlCD(['page'=>$page+1]) ?>"><i class="bi bi-chevron-right"></i></a></li>
                    </ul></nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>window.APP_BASE_PATH = '<?= $basePath ?>';</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/operational_correspondencia_documentos.js?v=<?= time() ?>"></script>