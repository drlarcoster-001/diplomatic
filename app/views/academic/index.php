<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/index.php
 * Propósito: Interfaz de dashboard modular para el acceso centralizado a las entidades académicas.
 * Version: 1.3.0 - Estandarización visual de tarjetas (bordes superiores coloridos, fondos circulares y colores vibrantes para Profesores y Sedes).
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$userRole = $_SESSION['user']['role'] ?? '';
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/public/assets/css/settings_panel.css">

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel Académico</h2>
        <p class="text-muted small">Administración de programas, períodos, grupos, personal docente y sedes institucionales.</p>
    </div>

    <div class="row g-4">
        
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/oferta" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #6f42c1 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="bi bi-briefcase-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6f42c1;">Oferta Académica</h5>
                    <p class="text-muted small">Apertura y configuración integral de diplomados (Cupos, costos, profesores y grupos).</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/diplomados" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-mortarboard-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Diplomados</h5>
                    <p class="text-muted small">Catálogo base de programas académicos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/periodos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #20c997 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(32, 201, 151, 0.1); color: #20c997;">
                        <i class="bi bi-calendar2-range-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Períodos</h5>
                    <p class="text-muted small">Gestión de períodos institucionales y control de inscripciones.</p>
                </div>
            </a>
        </div>


        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/cohortes" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-calendar-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Cohortes</h5>
                    <p class="text-muted small">Configuración de ejercicios y cronogramas de pago.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/grupos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Grupos</h5>
                    <p class="text-muted small">Control de modalidades y límites de inscripción.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/profesores" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-badge-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Profesores</h5>
                    <p class="text-muted small">Registro y asignación de docentes especialistas.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/campuses" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #fd7e14 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(253, 126, 20, 0.1); color: #fd7e14;">
                        <i class="bi bi-building-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Sedes</h5>
                    <p class="text-muted small">Administración de ubicaciones físicas y virtuales.</p>
                </div>
            </a>
        </div>

        <?php if (in_array($userRole, ['ADMIN', 'OPERATOR'])): ?>
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/cohortes-config" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #d63384 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(214, 51, 132, 0.1); color: #d63384;">
                        <i class="bi bi-gear-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Config. Cohortes</h5>
                    <p class="text-muted small">Gestión de estatus extemporáneos y borrado físico.</p>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/centros-medicos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0aa1dd !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(10, 161, 221, 0.1); color: #0aa1dd;">
                        <i class="bi bi-hospital-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0aa1dd;">Centros Médicos</h5>
                    <p class="text-muted small">Catálogo de centros médicos para Horarios de Práctica.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/horarios-teoricos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #6f42c1 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="bi bi-clock-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6f42c1;">Horarios Teóricos</h5>
                    <p class="text-muted small">Configuración de días y horarios de clase por oferta académica.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/horarios-practicas" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0f6e56 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(15, 110, 86, 0.1); color: #0f6e56;">
                        <i class="bi bi-hospital-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0f6e56;">Horarios de Práctica</h5>
                    <p class="text-muted small">Configuración de grupos y centros médicos por oferta académica.</p>
                </div>
            </a>
        </div>

        <!-- Asignación de Modalidad-->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/profesor-modalidad" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #d63384 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(214, 51, 132, 0.1); color: #d63384;">
                        <i class="bi bi-person-video2 fs-2"></i>
                    </div>
                            <h5 class="fw-bold" style="color: #d63384;">Modalidad Virtual</h5>
                            <p class="text-muted small">Asigna el profesor que dicta la parte virtual de cada oferta.</p>
                </div>
            </a>
        </div>

        <!-- Aprobar Actas -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/aprobar-actas" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-file-earmark-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Aprobar Actas</h5>
                    <p class="text-muted small">Revisa y aprueba las actas de notas enviadas por los profesores.</p>
                </div>
            </a>
        </div>
        
        <!-- Cierre Académico -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/cierre" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #dc3545 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background:rgba(220,53,69,0.1);color:#dc3545">
                        <i class="bi bi-lock-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#dc3545">Cierre Académico</h5>
                    <p class="text-muted small">Proceso de cierre formal de ofertas académicas.</p>
                </div>
            </a>
        </div>

        <!--Histórico-->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/historico" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #6c757d !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background:rgba(108,117,125,0.1);color:#6c757d">
                        <i class="bi bi-archive-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#6c757d">Histórico</h5>
                    <p class="text-muted small">Consulta de diplomados cerrados con notas y resultados finales.</p>
                </div>
            </a>
        </div>

        <!-- Importador de Período -->
        <div class="col-md-6 col-lg-3">
    <a href="<?= htmlspecialchars($basePath) ?>/academic/importador" class="text-decoration-none text-dark">
        <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
            <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background:rgba(25,135,84,0.1);color:#198754">
                <i class="bi bi-arrow-repeat fs-2"></i>
            </div>
            <h5 class="fw-bold" style="color:#198754">Importador de Período</h5>
            <p class="text-muted small mb-0">Clona la configuración de un período anterior al nuevo.</p>
        </div>
    </a>
</div>


    </div>
</div>