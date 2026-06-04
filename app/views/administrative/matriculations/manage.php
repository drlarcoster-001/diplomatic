<?php
/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: app/views/administrative/matriculations/manage.php
 * PROPÓSITO: Interfaz profesional para carga masiva de calificaciones y gestión de estados.
 * VERSIÓN: 2.6.0 - Fix: Sincronización total de selectores y optimización de UI.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
// Extraemos el nombre del diplomado del primer estudiante si existe
$nombreDiplomado = !empty($data['students']) ? htmlspecialchars($data['students'][0]['diplomado'] ?? 'Cohorte') : 'Gestión de Notas';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_matriculations.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light p-2 rounded shadow-sm border mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item">
                    <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                        <i class="bi bi-house-door"></i> Inicio
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= $basePath ?>/administrative/matriculations" class="text-decoration-none text-muted">Matrícula Académica</a>
                </li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Carga de Notas</li>
            </ol>
        </nav>
        
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/administrative/matriculations/imprimirAsistencia?id=<?= $data['offering_id'] ?>" 
               target="_blank" 
               class="btn btn-outline-success btn-sm fw-bold shadow-sm px-3">
                <i class="bi bi-card-checklist me-1"></i> Listado de Asistencia
            </a>

            <a href="<?= $basePath ?>/administrative/matriculations/imprimirListado?id=<?= $data['offering_id'] ?>" 
               target="_blank" 
               class="btn btn-outline-danger btn-sm fw-bold shadow-sm px-3">
                <i class="bi bi-file-earmark-pdf me-1"></i> Listado de Inscritos
            </a>
            <a href="<?= $basePath ?>/administrative/matriculations" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm px-3">
                <i class="bi bi-arrow-left-circle me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark"><?= $nombreDiplomado ?></h2>
        <p class="text-muted small mb-0">
            Asigne las calificaciones finales. El sistema procesará el estatus académico y promoverá a los alumnos a <b>EGRESADO</b> automáticamente.
        </p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-person-check-fill text-primary me-2"></i> Estudiantes Matriculados
            </h6>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm rounded-pill" 
                        id="btn-procesar-acta" 
                        data-offering-id="<?= $data['offering_id'] ?>">
                    <i class="bi bi-cpu-fill me-1"></i> PROCESAR ACTA
                </button>
                
                <a href="<?= $basePath ?>/administrative/matriculations/imprimirActa?id=<?= $data['offering_id'] ?>" 
                   target="_blank" 
                   class="btn btn-dark btn-sm px-4 fw-bold shadow-sm rounded-pill">
                    <i class="bi bi-printer-fill me-1"></i> IMPRIMIR ACTA
                </a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <form id="form-acta-notas">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr style="font-size: 0.75rem;" class="text-uppercase text-muted border-bottom">
                                <th class="ps-4 py-3">ID / Cédula</th>
                                <th>Nombre Completo</th>
                                <th class="text-center">Estado Académico</th>
                                <th class="text-center" style="width: 160px;">Nota Final (0-20)</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.85rem;">
                            <?php if (!empty($data['students'])): ?>
                                <?php foreach ($data['students'] as $student): ?>
                                    <?php 
                                        // Bloqueo de edición para estados terminales
                                        $isDisabled = in_array($student['academic_status'], ['RETIRADO', 'CONGELADO', 'APROBADO', 'REPROBADO']);
                                        
                                        $statusBadge = match($student['academic_status']) {
                                            'CURSANDO'  => 'bg-primary bg-opacity-10 text-primary border-primary',
                                            'APROBADO'  => 'bg-success bg-opacity-10 text-success border-success',
                                            'REPROBADO' => 'bg-danger bg-opacity-10 text-danger border-danger',
                                            'CONGELADO' => 'bg-warning bg-opacity-10 text-dark border-warning',
                                            'RETIRADO'  => 'bg-secondary bg-opacity-10 text-secondary border-secondary',
                                            default     => 'bg-light text-dark'
                                        };
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= htmlspecialchars($student['cedula']) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge border px-3 py-2 <?= $statusBadge ?>" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                                <?= $student['academic_status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center">
                                                <input type="number" 
                                                       step="0.01" min="0" max="20" 
                                                       class="form-control form-control-sm text-center fw-bold input-final-grade shadow-none border-2" 
                                                       style="max-width: 90px; height: 35px;"
                                                       data-id="<?= $student['matricula_id'] ?>" 
                                                       value="<?= $student['final_grade'] ?? '' ?>"
                                                       <?= $isDisabled ? 'disabled' : '' ?>>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm border rounded-pill overflow-hidden shadow-sm">
                                                <button type="button" 
                                                        class="btn btn-white text-warning btn-change-status" 
                                                        title="Congelar"
                                                        data-mid="<?= $student['matricula_id'] ?>"
                                                        data-sid="<?= $student['student_internal_id'] ?>"
                                                        data-status="CONGELADO"
                                                        <?= $isDisabled ? 'disabled' : '' ?>>
                                                    <i class="bi bi-pause-circle-fill"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-white text-danger btn-change-status" 
                                                        title="Retirar"
                                                        data-mid="<?= $student['matricula_id'] ?>"
                                                        data-sid="<?= $student['student_internal_id'] ?>"
                                                        data-status="RETIRADO"
                                                        <?= $isDisabled ? 'disabled' : '' ?>>
                                                    <i class="bi bi-person-x-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                        No hay estudiantes matriculados en esta oferta académica.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

        <div class="card-footer bg-light bg-opacity-50 py-3 border-top">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-info-square-fill text-primary me-1"></i>
                        <strong>Nota:</strong> Los alumnos con estatus "Cursando" son los únicos habilitados para la carga de notas masiva.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="text-muted small">Registros en pantalla: <strong><?= count($data['students'] ?? []) ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> 
    // Inyectamos la URL base para el archivo JS externo
    window.BASE_URL = '<?= $basePath ?>'; 
</script>
<script src="<?= $basePath ?>/assets/js/administrative_matriculations.js"></script>