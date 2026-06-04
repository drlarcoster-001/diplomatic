<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES / VISTAS
 * ARCHIVO: app/views/students/inscriptions/create.php
 * PROPÓSITO: Orquestador del asistente (Wizard). Carga dinámica de sub-vistas y scripts JS con fix de rutas.
 * VERSIÓN: 1.2.8 - Inclusión del paso "s6" en el array de carga dinámica para habilitar el disparo de correos asíncronos.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$urlBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;

// Capturamos el ID de la oferta
$selectedOfferingId = $_GET['id'] ?? '';

// Extraemos identidad con llaves alternativas para asegurar compatibilidad
$userSession = $_SESSION['user'] ?? [];
$userId      = $userSession['id'] ?? $userSession['user_id'] ?? '';
$documentId  = $userSession['document_id'] ?? 'N/A';
$displayName = $userSession['name'] ?? 'Estudiante';
$avatar      = $userSession['avatar'] ?? 'default.png';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/students_inscriptions_create.css?v=<?= time() ?>">

<div class="inscription-manual-scope">
    <div class="container py-4">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
                <li class="breadcrumb-item small">
                    <a href="<?= $urlBase ?>/dashboard" class="text-decoration-none text-muted">Inicio</a>
                </li>
                <li class="breadcrumb-item small">
                    <a href="<?= $urlBase ?>/students/inscriptions" class="text-decoration-none text-muted">Mis Inscripciones</a>
                </li>
                <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Nueva Inscripción</li>
            </ol>
        </nav>

        <div class="card wizard-card-container shadow-lg rounded-4 border-0">
            
            <div class="card-header bg-white p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Asistente de Inscripción Académica</h5>
                        <p class="text-muted small mb-0">Confirma tus datos y completa los requisitos del programa.</p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2 shadow-sm" id="stepIndicator">Paso 1 de 5</span>
                </div>
                <div class="progress border-0 bg-light" style="height: 8px; border-radius: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="wizardProgress" style="width: 20%;"></div>
                </div>
            </div>

            <div class="card-body wizard-body-content">
                <form id="formAtomicInscription" enctype="multipart/form-data">
                    
                    <input type="hidden" name="offering_id" value="<?= htmlspecialchars((string)$selectedOfferingId) ?>">
                    <input type="hidden" name="current_step" id="current_step_val" value="1">
                    <input type="hidden" name="user_id" id="user_id_val" value="<?= htmlspecialchars((string)$userId) ?>"> 
                    <input type="hidden" name="document_id_hidden" id="document_id_hidden" value="<?= htmlspecialchars((string)$documentId) ?>">

                    <?php 
                    /**
                     * CARGA DE SUB-VISTAS (s1 a s5)
                     * NOTA: 's6' es solo un JS asíncrono, no tiene vista HTML, por lo que no fallará aquí.
                     */
                    $steps = ['s1', 's2', 's3', 's4', 's5', 's6'];
                    foreach ($steps as $step) {
                        $file = __DIR__ . "/create_{$step}.php";
                        if (file_exists($file)) {
                            require $file;
                        } else {
                            if ($step !== 's6') {
                                // Placeholder de seguridad para evitar que el JS falle
                                echo "<div class='wizard-step-content d-none' id='step" . substr($step, 1) . "'></div>";
                            }
                        }
                    }
                    ?>
                </form> 
            </div>

            <div class="card-footer wizard-footer-actions d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold d-none" id="btnPrev">
                    <i class="bi bi-arrow-left me-1"></i> Anterior
                </button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-link text-danger fw-bold text-decoration-none px-3" id="btnCancel">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" id="btnNext">
                        Siguiente <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm d-none" id="btnSubmit">
                        Finalizar Inscripción
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/students_inscriptions_create.js?v=<?= time() ?>"></script>

<?php 
/**
 * CARGA DINÁMICA DE SCRIPTS DE PASOS
 * Se utiliza dirname(__DIR__, 4) para subir desde app/views/students/inscriptions a la raíz
 * y verificar la existencia en /public/assets/js/
 */
foreach ($steps as $step): 
    $jsPath = dirname(__DIR__, 4) . "/public/assets/js/students_inscriptions_create_{$step}.js";
    if (file_exists($jsPath)): ?>
        <script src="<?= $basePath ?>/assets/js/students_inscriptions_create_<?= $step ?>.js?v=<?= time() ?>"></script>
    <?php endif; 
endforeach; ?>