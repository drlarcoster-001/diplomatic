<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/views/students/inscriptions/create_s1.php
 * PROPÓSITO: Confirmación de identidad con datos específicos solicitados.
 * VERSIÓN: 1.2.0 - Limpieza de etiquetas, fix de ruta de avatar y visualización de Cédula.
 */

// Recuperamos los datos de la sesión
$avatarFile = $_SESSION['user']['avatar'] ?? 'default.png';
$fullName   = $_SESSION['user']['name'] ?? 'Estudiante';
$documentId = $_SESSION['user']['document_id'] ?? 'Falta actualizar perfil';
$email      = $_SESSION['user']['email'] ?? 'S/D';
?>

<div class="wizard-step-content" id="step1">
    <div class="text-center mb-5">
        <h4 class="fw-bold text-dark">Paso 1: Confirmación de Identidad</h4>
        <p class="text-muted small">Verifique que su información personal sea correcta para proceder con la inscripción.</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div id="identity-status-container">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="bg-white border shadow-sm rounded-4 p-4 d-flex align-items-center position-relative w-100 border-start border-primary border-5">
                        
                        <div class="profile-avatar-wrapper me-4">
                            <img id="preview_avatar_s1" 
                                 src="<?= $basePath ?>/assets/img/avatars/<?= htmlspecialchars($avatarFile) ?>" 
                                 class="rounded-circle border shadow-sm" 
                                 style="width: 100px; height: 100px; object-fit: cover;"
                                 onerror="this.src='<?= $basePath ?>/assets/img/avatars/default.png'">
                        </div>
                        
                        <div class="text-start flex-grow-1">
                            <div class="mb-1">
                                <label class="smallest text-primary fw-bold d-block">NOMBRE:</label>
                                <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars(strtoupper($fullName)) ?></h3>
                            </div>

                            <div class="row mt-3">
                                <div class="col-sm-6">
                                    <label class="smallest text-primary fw-bold d-block">ID / CÉDULA:</label>
                                    <span class="text-dark fw-bold" style="font-size: 1.1rem;"><?= htmlspecialchars($documentId) ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="smallest text-primary fw-bold d-block">CORREO:</label>
                                    <span class="text-muted"><?= htmlspecialchars($email) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded-4 border border-dashed text-center">
                <p class="text-muted small mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> 
                    Si observa algún error en sus datos, por favor detenga el proceso y comuníquese con el <strong>Departamento de Control de Estudios</strong>.
                </p>
            </div>
        </div>
    </div>
</div>