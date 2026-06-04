<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / VISTAS
 * ARCHIVO: app/views/students/payment_registration/index.php
 * PROPÓSITO: Vista maestra del wizard de pagos para estudiantes.
 * VERSIÓN: 1.0.2 - FIX: Inclusión directa de modales para evitar saltos silenciosos.
 */

declare(strict_types=1);

$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/students_payment_registration.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/students_payment_confirmation.css?v=<?= time() ?>">

<div class="inscription-manual-scope container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0 m-0">
                <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                <li class="breadcrumb-item small"><a href="<?= $basePath ?>/students" class="text-decoration-none text-muted">Panel Estudiantil</a></li>
                <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Reportar pago</li>
            </ol>
        </nav>
        
        <a href="<?= $basePath ?>/students" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm btn-sm">
            <i class="bi bi-arrow-left-circle me-2"></i>Volver al Panel
        </a>
    </div>

    <div class="card wizard-card-container shadow-lg rounded-4 border-0">
        
        <div class="card-header bg-white p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary rounded-pill px-3 py-2 shadow-sm" id="stepIndicator">Paso 1 de 3</span>
                <div class="w-75 mx-3">
                    <div class="progress border-0 bg-light" style="height: 10px; border-radius: 10px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="wizardProgress" style="width: 33%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body wizard-body-content p-5">
            <form id="formRegistrationPayment" enctype="multipart/form-data">
                <input type="hidden" name="current_step" id="current_step_val" value="1">
                <input type="hidden" name="user_id" id="user_id_val" value="<?= $_SESSION['user']['id'] ?? '' ?>">
                <input type="hidden" name="offering_id" id="offering_id_val" value="">
                
                <input type="hidden" name="amount" id="amount" value="0.00">
                <input type="hidden" name="payment_method_type" id="payment_method_type" value="">
                <input type="hidden" id="payment_metadata" name="payment_metadata" value="">
                
                <input type="hidden" id="student_code_hidden" value="<?= $_SESSION['user']['student_code'] ?? '' ?>">
                <input type="hidden" id="full_name_hidden" value="<?= $_SESSION['user']['name'] ?? '' ?>">
                <input type="hidden" id="user_id_hidden" value="<?= $_SESSION['user']['id'] ?? '' ?>">
                <input type="hidden" id="document_id_hidden" value="<?= $_SESSION['user']['document_id'] ?? '' ?>">

                <?php require __DIR__ . '/registry_s1.php'; ?>
                <?php require __DIR__ . '/registry_s2.php'; ?>
                <?php require __DIR__ . '/registry_s4.php'; ?>
            </form>
        </div>

        <div class="card-footer wizard-footer-actions d-flex justify-content-between align-items-center p-4 bg-white border-top-0">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold d-none" id="btnPrev">
                <i class="bi bi-arrow-left me-1"></i> Anterior
            </button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-link text-danger fw-bold text-decoration-none px-3" id="btnCancel" onclick="location.href='<?= $basePath ?>/students'">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" id="btnNext" disabled>
                    Siguiente <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="button" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm d-none" id="btnSubmit">
                    <i class="bi bi-check-circle me-1"></i> Enviar Pago
                </button>
            </div>
        </div>
    </div>
</div>
<?php 
    // Ahora sí, apuntando a la carpeta "modals" y a los archivos sin la "s" extra
    require __DIR__ . '/modals/modal_digital.php';
    require __DIR__ . '/modals/modal_account_status.php';
    require __DIR__ . '/modals/modal_payment_confirmation.php';
?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>const BASE_URL = '<?= $basePath ?>';</script>

<script src="<?= $basePath ?>/assets/js/students_payment_registration_s1.js?v=<?= time() ?>"></script>
<script src="<?= $basePath ?>/assets/js/students_payment_registration_s2.js?v=<?= time() ?>"></script>

<script src="<?= $basePath ?>/assets/js/students_payment_registration_s4_utils.js?v=<?= time() ?>"></script>
<script src="<?= $basePath ?>/assets/js/students_payment_registration_s4_ui.js?v=<?= time() ?>"></script>
<script src="<?= $basePath ?>/assets/js/students_payment_registration_s4_handlers.js?v=<?= time() ?>"></script>
<script src="<?= $basePath ?>/assets/js/students_payment_registration_s4_main.js?v=<?= time() ?>"></script>

<script src="<?= $basePath ?>/assets/js/students_payment_registration.js?v=<?= time() ?>"></script>