<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/views/students/inscriptions/index.php
 * PROPÓSITO: Panel principal de gestión de inscripciones con visualización de ofertas y seguimiento de estatus.
 * VERSIÓN: 1.2.3 - Tarjetas con contorno gris sutil y sombreado base mejorado.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$urlBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;

/**
 * PREPARACIÓN DE IDENTIDAD:
 * Buscamos en todas las llaves posibles para evitar el "Estudiante" genérico.
 */
$user = $_SESSION['user'] ?? [];
$nombre   = $user['first_name'] ?? $user['nombre'] ?? $user['name'] ?? '';
$apellido = $user['last_name']  ?? $user['apellido'] ?? '';

$nombreReal = trim($nombre . ' ' . $apellido);

// Si no hay nombre ni apellido, usamos display_name, y si no, "Estudiante"
$identidadParaJS = !empty($nombreReal) ? $nombreReal : ($user['display_name'] ?? 'Estudiante');
?>

<script>
    /**
     * IMPORTANTE: Esta variable es la que lee el archivo students_inscriptions.js
     * Usamos JSON_UNESCAPED_UNICODE para que los acentos se vean correctamente.
     */
    const NOMBRE_ESTUDIANTE = <?= json_encode($identidadParaJS, JSON_UNESCAPED_UNICODE) ?>;
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/students_inscriptions.css?v=<?= time() ?>">

<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
            <li class="breadcrumb-item small"><a href="<?= $urlBase ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $urlBase ?>/students" class="text-decoration-none text-muted">Panel Estudiantil</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Inscripciones Online</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Mis Inscripciones Online</h2>
            <p class="text-muted small mb-1"><i class="fas fa-info-circle me-1 text-primary"></i> Gestión de inscripciones online</p>
            <p class="text-dark fw-medium mb-0">Seleccione el diplomado que va a inscribir:</p>
        </div>
        <a href="<?= $urlBase ?>/students" class="btn btn-white btn-sm rounded-pill px-3 shadow-sm border fw-bold text-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al panel estudiantil
        </a>
    </div>

    <div class="tab-container-capsule mb-4">
        <ul class="nav nav-pills nav-fill p-1 bg-secondary bg-opacity-10 rounded-pill" id="inscriptionsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active rounded-pill fw-bold py-2" id="inscribir-tab" data-bs-toggle="pill" data-bs-target="#pane-inscribir" type="button" role="tab">Inscribir</button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill fw-bold py-2" id="estatus-tab" data-bs-toggle="pill" data-bs-target="#pane-estatus" type="button" role="tab">Ver Estatus</button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-inscribir" role="tabpanel">
            <div class="bg-white rounded-4 shadow-sm border p-4">
                <?php $ofertasInscritas = array_column($enrollmentStatus ?? [], 'offering_id'); ?>
                <?php if (!empty($openOfferings)): ?>
                    <div class="row g-3">
                        <?php foreach ($openOfferings as $off): ?>
                        <div class="col-12 col-md-6">
                            <?php $yaInscrito = in_array($off['offering_id'], $ofertasInscritas); ?>
                            <div class="card h-100 interactive-row selectable-card rounded-3" 
                                style="cursor:<?= $yaInscrito ? 'not-allowed' : 'pointer' ?>;opacity:<?= $yaInscrito ? '0.6' : '1' ?>"
                                <?= !$yaInscrito ? 'onclick="verDetallesOferta('.htmlspecialchars(json_encode($off), ENT_QUOTES, 'UTF-8').')"' : '' ?>>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="fw-normal text-uppercase text-dark mb-4" style="font-size: 1.50rem; line-height: 1.3;">
                                        <?= htmlspecialchars($off['diplomado_name']) ?>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <?php if (!empty($off['grupos_nombres'])): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php 
                                                $grupos = array_map('trim', explode(',', $off['grupos_nombres']));
                                                foreach ($grupos as $grupo): 
                                                ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.85rem;">
                                                        <i class="fas fa-users me-1"></i><?= htmlspecialchars($grupo) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($yaInscrito): ?>
                                        <div class="mt-3">
                                            <span class="badge bg-success rounded-pill px-3 py-2 w-100" style="font-size:0.85rem">
                                                <i class="fas fa-check-circle me-1"></i> Ya inscrito
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">No hay ofertas disponibles en este momento.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="pane-estatus" role="tabpanel">
            <div class="table-responsive bg-white rounded-4 shadow-sm border">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0">Programa / Cohorte</th>
                            <th class="text-center py-3 border-0">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollmentStatus)): ?>
                            <?php foreach ($enrollmentStatus as $st): 
                                $badge = 'bg-secondary';
                                $statusText = strtoupper($st['status']);
                                if (strpos($statusText, 'REVISI') !== false) $badge = 'bg-warning text-dark';
                                if ($statusText === 'CURSANDO') $badge = 'bg-primary';
                                if ($statusText === 'FINALIZADA') $badge = 'bg-success';
                            ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-bold"><?= htmlspecialchars($st['diplomado_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($st['cohort_name']) ?></div>
                                </td>
                                <td class="text-center px-4">
                                    <span class="badge rounded-pill w-100 py-2 shadow-sm <?= $badge ?>">
                                        <?= htmlspecialchars($st['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted">Aún no tienes inscripciones registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/students_inscriptions.js?v=<?= time() ?>"></script>