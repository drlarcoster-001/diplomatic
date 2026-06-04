<?php
/**
 * MÓDULO: ESTUDIANTES
 * ARCHIVO: app/views/students/index.php
 * PROPÓSITO: Vista principal del panel estudiantil con tarjetas de acceso a los submódulos.
 * VERSIÓN: 1.1.3 - Enlace corregido para Estado de Cuenta Estudiantil.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/students.css">

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Panel Estudiantil</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-person-workspace text-primary me-2"></i> Mi Autogestión Estudiantil
        </h4>
        <p class="text-muted small">Bienvenido a tu portal. Selecciona la operación que deseas realizar.</p>
    </div>

    <div class="row g-3">
        
        <div class="col-12">
            <a href="<?= $basePath ?>/students/inscriptions" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-primary rounded-start-4 bg-white">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-journal-check fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Inscribir</h5>
                            <p class="text-muted small mb-0">Explora la oferta académica disponible y realiza tu inscripción de forma totalmente online.</p>
                        </div>
                        <div class="ms-3 text-primary flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12">
            <a href="<?= $basePath ?>/students/payment_registration" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-success rounded-start-4 bg-white">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-credit-card fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Registrar Pagos</h5>
                            <p class="text-muted small mb-0">Reporta tus transferencias, Pago Móvil o depósitos en divisas para la validación administrativa.</p>
                        </div>
                        <div class="ms-3 text-success flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12">
            <a href="<?= $basePath ?>/students/payment_history" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-info rounded-start-4 bg-white">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-clock-history fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Consultar Pagos</h5>
                            <p class="text-muted small mb-0">Revisa el historial y estatus de todos tus reportes de pago enviados.</p>
                        </div>
                        <div class="ms-3 text-info flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12">
            <a href="<?= $basePath ?>/students/student_statement" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-secondary rounded-start-4 bg-white">
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-receipt fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Consultar Estado de Cuenta</h5>
                            <p class="text-muted small mb-0">Revisa tu historial de transacciones, cuotas pendientes y descarga tu solvencia administrativa.</p>
                        </div>
                        <div class="ms-3 text-secondary flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12">
            <a href="<?= $basePath ?>/students/certificates" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-warning rounded-start-4 bg-white">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-award fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Mis Constancias</h5>
                            <p class="text-muted small mb-0">Genera y descarga en formato PDF tus constancias de inscripción y constancias de estudios oficiales.</p>
                        </div>
                        <div class="ms-3 text-warning flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12">
            <a href="<?= $basePath ?>/students/documents" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-info rounded-start-4 bg-white">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-folder2-open fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Gestión de Documentos</h5>
                            <p class="text-muted small mb-0">Carga aquí tu Cédula, Título y CV. Es necesario para que tu expediente esté completo.</p>
                            
                            <?php if (isset($enrollment_data['document_status']) && $enrollment_data['document_status'] === 'INCOMPLETE'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger mt-2 border border-danger border-opacity-25">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i> Tienes documentos pendientes
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="ms-3 text-info flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!--<div class="col-12">
            <a href="#" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 hover-lift card-student-option">
                    <div class="card-body p-4 d-flex align-items-center border-start border-4 border-info rounded-start-4 bg-white">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center me-4 flex-shrink-0" style="width: 65px; height: 65px;">
                            <i class="bi bi-megaphone fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1">Notificaciones y Noticias</h5>
                            <p class="text-muted small mb-0">Mantente al tanto con los últimos anuncios, cronogramas y noticias del campus.</p>
                        </div>
                        <div class="ms-3 text-info flex-shrink-0">
                            <i class="bi bi-chevron-right fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>-->

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/students.js?v=<?= time() ?>"></script>