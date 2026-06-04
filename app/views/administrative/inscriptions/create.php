<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create.php
 * PROPÓSITO: Layout principal del orquestador. Centralización de inputs de estado y carga de lógica S1-S5.
 * VERSIÓN: 2.4.1 - FIX: Conexión dinámica del input exchange_rate con la tasa del día proporcionada por el controlador.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$selectedOfferingId = $_GET['offering_id'] ?? '';

// TASA DINÁMICA: Se recibe desde el AdministrativeInscriptionsController
$defaultExRate = $tasaDelDia ?? ($data['tasaDelDia'] ?? 36.50); 
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_inscriptions_create.css?v=<?= time() ?>">

<div class="inscription-manual-scope">
    <div class="container py-4">
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
                <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                <li class="breadcrumb-item small"><a href="<?= $basePath ?>/administrative/inscriptions" class="text-decoration-none text-muted">Inscripciones</a></li>
                <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Nueva Inscripción</li>
            </ol>
        </nav>

        <div class="card wizard-card-container shadow-lg rounded-4 border-0">
            <div class="card-header bg-white p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Asistente de Inscripción de Estudiantes</h5>
                        <p class="text-muted small mb-0">Gestión de expediente profesional de estudiantes.</p>
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

                    <input type="hidden" id="user_name_hidden" value="<?= $_SESSION['user']['name'] ?? 'Usuario'; ?>">
                    
                    <input type="hidden" name="user_id" id="user_id_val" value=""> 
                    <input type="hidden" name="document_id_hidden" id="document_id_hidden" value="">
                    <input type="hidden" name="display_name_hidden" id="display_name_hidden" value="">
                    <input type="hidden" name="avatar_hidden" id="avatar_hidden" value="default.png">

                    <input type="hidden" name="payment_method_type" id="payment_method_type" value="">
                    <input type="hidden" name="amount" id="amount" value="0.00">
                    <input type="hidden" name="exchange_rate" id="exchange_rate" value="<?= htmlspecialchars((string)$defaultExRate) ?>">
                    <textarea name="payment_metadata" id="payment_metadata" class="d-none"></textarea>

                    <?php require __DIR__ . '/create_s1.php'; ?>
                    <?php require __DIR__ . '/create_s2.php'; ?>
                    <?php require __DIR__ . '/create_s3.php'; ?>
                    <?php require __DIR__ . '/create_s4.php'; ?>
                    <?php require __DIR__ . '/create_s5.php'; ?>
                </form> 
            </div>

            <div class="card-footer wizard-footer-actions d-flex justify-content-between align-items-center p-4 bg-white">
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
                        <i class="bi bi-check-circle me-1"></i> Finalizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s1.js?v=<?= time() ?>"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s2.js?v=<?= time() ?>"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s3.js?v=<?= time() ?>"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s4.js?v=<?= time() ?>"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s5.js?v=<?= time() ?>"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions_create_s6.js?v=<?= time() ?>"></script>