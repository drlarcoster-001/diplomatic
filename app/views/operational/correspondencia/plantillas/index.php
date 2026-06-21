<?php
/**
 * MÓDULO: CORRESPONDENCIA / PLANTILLAS
 * ARCHIVO: app/views/operational/correspondencia/plantillas/index.php
 * PROPÓSITO: Listado de plantillas con buscador, paginación a 25, modal
 *            "Ver" y botón eliminar (bloqueado si tiene documentos
 *            generados vinculados).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;

function buildUrlCP(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Correspondencia</a></li>
            <li class="breadcrumb-item active fw-bold text-primary">Plantillas</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Plantillas de Correspondencia</h2>
            <p class="text-muted small mb-0">Cartas, memos, oficios, actas, reconocimientos y constancias.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/operational" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/operational/correspondencia/plantillas/create" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Plantilla
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $basePath ?>/operational/correspondencia/plantillas" class="row g-2 mb-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Buscar</button>
                </div>
            </form>

            <div style="max-height:520px;overflow-y:auto">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-secondary text-uppercase" style="position:sticky;top:0;z-index:1">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th style="width:140px">Tipo</th>
                            <th style="width:160px">Tabla Objetivo</th>
                            <th style="width:110px">Creada</th>
                            <th style="width:160px" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plantillas)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-25"></i>
                                    No hay plantillas registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plantillas as $p):
                                $editUrl = "{$basePath}/operational/correspondencia/plantillas/edit?id={$p['id']}";
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><span class="badge rounded-pill bg-light text-dark border"><?= htmlspecialchars($tiposDocumento[$p['tipo_documento']] ?? $p['tipo_documento']) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars(str_replace('_', ' ', ucwords(strtolower($p['tabla_objetivo']), '_'))) ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-ver"
                                            data-id="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                            data-tipo="<?= htmlspecialchars($tiposDocumento[$p['tipo_documento']] ?? '') ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-2 btn-delete"
                                            data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['nombre']) ?>"
                                            data-count="<?= (int) $p['documentos_count'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?> · <?= $total ?> registros</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildUrlCP(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= buildUrlCP(['page'=>$i]) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlCP(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL VER PLANTILLA -->
<div class="modal fade" id="modalVerPlantilla" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span id="modalVerNombre" class="fw-bold"></span>
                    <span class="badge bg-light text-dark border ms-2" id="modalVerTipo"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-contenido-plantilla"></div>
            <div class="modal-footer">
                <a href="#" id="btn-modal-editar" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
    </div>
</div>

<script>window.APP_BASE_PATH = '<?= $basePath ?>';</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/operational_correspondencia_plantillas.js?v=<?= time() ?>"></script>