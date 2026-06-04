<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/edit.php
 * Propósito: Gestión integral de expedientes docentes con navegación jerárquica.
 * Version: 1.2.0 - Integración de Breadcrumbs, rutas dinámicas e iconos unificados.
 */
$p = $profesor; 
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_profesores_edit.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic/profesores" class="text-decoration-none text-muted">Profesores</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Editar Profesor</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Expediente del Docente</h2>
            <p class="text-muted small">ID: #<?= $p['id'] ?> | Registro: <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/academic/profesores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-success rounded-pill px-4 shadow-sm" onclick="document.getElementById('formBasicData').submit();">
                <i class="bi bi-check-circle me-1"></i> Guardar Cambios
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm text-center p-4">
                <?php $avatar = !empty($p['photo_path']) ? $p['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . ' ' . $p['last_name']) . '&background=4e73df&color=fff&size=150'; ?>
                <div class="position-relative mx-auto mb-3" style="width: 150px; height: 150px;">
                    <img src="<?= $avatar ?>" id="profile-img-preview" class="rounded-circle object-fit-cover shadow-sm w-100 h-100 border border-3 border-white">
                    <button type="button" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 shadow btn-change-photo" style="width:35px; height:35px;"><i class="bi bi-camera"></i></button>
                    <input type="file" id="inputPhotoUpload" accept="image/*" style="display:none;">
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['full_name'] ?? ($p['first_name'] . ' ' . $p['last_name'])) ?></h5>
                <span class="badge bg-primary rounded-pill px-3 py-2 w-100 mb-2"><?= $p['professor_type'] ?></span>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <ul class="nav nav-tabs fw-bold" id="expedienteTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#datos" type="button"><i class="bi bi-person-vcard me-2"></i> Datos</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#formacion" type="button"><i class="bi bi-mortarboard me-2"></i> Formación</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#experiencia" type="button"><i class="bi bi-briefcase me-2"></i> Experiencia</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#especialidades" type="button"><i class="bi bi-patch-check me-2"></i> Especialidades</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documentos" type="button"><i class="bi bi-folder2-open me-2"></i> Documentos</button></li>
                    </ul>
                </div>
                
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div class="tab-content" id="expedienteTabsContent">
                        <div class="tab-pane fade show active" id="datos" role="tabpanel">
                            <form id="formBasicData" action="<?= $basePath ?>/academic/profesores/updateBase" method="POST" class="row g-4">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <div class="col-md-4"><label class="form-label small fw-bold">IDENTIFICACIÓN</label><input type="text" name="identification" class="form-control" value="<?= htmlspecialchars($p['identification']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">NOMBRES</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($p['first_name']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">APELLIDOS</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($p['last_name']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">EMAIL</label><input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($p['contact']['email'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">TELÉFONO</label><input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($p['contact']['phone'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">TIPO DE PROFESOR</label>
                                    <select name="professor_type" class="form-select">
                                        <option value="Docente" <?= ($p['professor_type'] == 'Docente') ? 'selected' : '' ?>>Docente</option>
                                        <option value="Coordinador" <?= ($p['professor_type'] == 'Coordinador') ? 'selected' : '' ?>>Coordinador</option>
                                        <option value="Invitado" <?= ($p['professor_type'] == 'Invitado') ? 'selected' : '' ?>>Invitado</option>
                                        <option value="Tutor" <?= ($p['professor_type'] == 'Tutor') ? 'selected' : '' ?>>Tutor</option>
                                    </select>
                                </div>
                                <div class="col-12"><label class="form-label small fw-bold">BIOGRAFÍA</label><textarea name="biography" class="form-control" rows="3"><?= htmlspecialchars($p['biography'] ?? '') ?></textarea></div>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="formacion" role="tabpanel">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0 text-secondary">Formación Académica</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalFormation"><i class="bi bi-plus-lg"></i> Añadir</button></div>
                            <div class="table-responsive">
                                <table class="table table-sm bg-white border rounded table-hover">
                                    <thead class="bg-light"><tr><th>Título</th><th>Nivel</th><th>Institución</th><th>Año</th><th class="text-end"></th></tr></thead>
                                    <tbody>
                                        <?php foreach($p['formations'] as $f): ?>
                                        <tr class="edit-row" data-target-modal="#modalFormation" data-json='<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;">
                                            <td><?= htmlspecialchars($f['degree_title']) ?></td>
                                            <td><?= htmlspecialchars($f['academic_level']) ?></td>
                                            <td><?= htmlspecialchars($f['institution']) ?></td>
                                            <td><?= $f['year_obtained'] ?></td>
                                            <td class="text-end">
                                                <form action="<?= $basePath ?>/academic/profesores/deleteFormation" method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                    <button type="button" class="btn btn-sm text-danger btn-delete-record"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="experiencia" role="tabpanel">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0 text-secondary">Trayectoria Laboral</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalWork"><i class="bi bi-plus-lg"></i> Añadir</button></div>
                            <div class="table-responsive">
                                <table class="table table-sm bg-white border rounded table-hover">
                                    <thead class="bg-light"><tr><th>Cargo</th><th>Empresa</th><th>Período</th><th class="text-end"></th></tr></thead>
                                    <tbody>
                                        <?php foreach($p['work_experiences'] as $w): ?>
                                        <tr class="edit-row" data-target-modal="#modalWork" data-json='<?= htmlspecialchars(json_encode($w), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;">
                                            <td><?= htmlspecialchars($w['job_title']) ?></td>
                                            <td><?= htmlspecialchars($w['institution']) ?></td>
                                            <td><?= $w['start_date'] ?> / <?= $w['is_current'] ? 'Hoy' : $w['end_date'] ?></td>
                                            <td class="text-end">
                                                <form action="<?= $basePath ?>/academic/profesores/deleteWork" method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $w['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                    <button type="button" class="btn btn-sm text-danger btn-delete-record"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="especialidades" role="tabpanel">
                             <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0 text-secondary">Áreas de Conocimiento</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalSpecialty"><i class="bi bi-plus-lg"></i> Añadir</button></div>
                             <div class="table-responsive">
                                <table class="table table-sm bg-white border rounded table-hover">
                                    <thead class="bg-light"><tr><th>Especialidad</th><th>Principal</th><th class="text-end"></th></tr></thead>
                                    <tbody>
                                        <?php foreach($p['specialties'] as $s): ?>
                                        <tr class="edit-row" data-target-modal="#modalSpecialty" data-json='<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;">
                                            <td><?= htmlspecialchars($s['specialty_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= $s['is_main'] ? 'SÍ' : 'NO' ?></span></td>
                                            <td class="text-end"><form action="<?= $basePath ?>/academic/profesores/deleteSpecialty" method="POST" class="d-inline"><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><button type="button" class="btn btn-sm text-danger btn-delete-record"><i class="bi bi-trash"></i></button></form></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                             </div>
                        </div>

                        <div class="tab-pane fade" id="documentos" role="tabpanel">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0 text-secondary">Soportes Digitales</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalDocument"><i class="bi bi-cloud-upload"></i> Subir</button></div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle bg-white border rounded">
                                    <thead class="bg-light"><tr><th>Tipo</th><th>Nombre</th><th>Archivo</th><th class="text-end pe-3">Acciones</th></tr></thead>
                                    <tbody>
                                        <?php foreach($p['documents'] as $d): ?>
                                        <tr>
                                            <td class="small fw-bold"><?= htmlspecialchars($d['document_type']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($d['document_name']) ?></td>
                                            <td><a href="<?= $d['file_path'] ?>" target="_blank" class="btn btn-xs btn-link text-primary p-0 text-decoration-none small"><i class="bi bi-file-earmark-pdf me-1"></i>Ver</a></td>
                                            <td class="text-end pe-3"><form action="<?= $basePath ?>/academic/profesores/deleteDocument" method="POST" class="d-inline"><input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><button type="button" class="btn btn-sm text-danger btn-delete-record border-0 bg-transparent"><i class="bi bi-trash"></i></button></form></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrop" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-crop me-2"></i>Ajustar Foto</h5></div><div class="modal-body p-0"><img id="imageToCrop" src="" style="max-width: 100%;"></div><div class="modal-footer bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary rounded-pill px-4" id="btnSaveCrop">Guardar</button></div></div></div></div>

<div class="modal fade" id="modalFormation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $basePath ?>/academic/profesores/saveFormation" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-mortarboard me-2"></i>Formación Académica</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                <div class="mb-3"><label class="small fw-bold">TÍTULO OBTENIDO</label><input type="text" name="degree_title" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">NIVEL ACADÉMICO</label><select name="academic_level" class="form-select" required><option value="Pregrado">Pregrado</option><option value="Especialista">Especialista</option><option value="Magister">Magister</option><option value="Doctorado">Doctorado</option><option value="Postdoctorado">Postdoctorado</option><option value="Diplomado">Diplomado</option></select></div>
                <div class="mb-3"><label class="small fw-bold">ÁREA DE ESTUDIO</label><input type="text" name="study_area" class="form-control"></div>
                <div class="mb-3"><label class="small fw-bold">INSTITUCIÓN</label><input type="text" name="institution" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">AÑO</label><input type="number" name="year_obtained" class="form-control" min="1950" max="2100" value="<?= date('Y') ?>"></div>
            </div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Guardar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalWork" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= $basePath ?>/academic/profesores/saveWork" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-briefcase me-2"></i>Experiencia Laboral</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                <div class="mb-3"><label class="small fw-bold">CARGO</label><input type="text" name="job_title" class="form-control" required></div>
                <div class="mb-3"><label class="small fw-bold">EMPRESA / INSTITUCIÓN</label><input type="text" name="institution" class="form-control" required></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_current" id="check_current" value="1"><label class="form-check-label small fw-bold">Cargo Actual</label></div>
                <div class="row g-3"><div class="col-6"><label class="small fw-bold">INICIO</label><input type="date" name="start_date" id="work_start_date" class="form-control" required></div><div class="col-6"><label class="small fw-bold">FIN</label><input type="date" name="end_date" id="work_end_date" class="form-control"></div></div>
            </div>
            <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Guardar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalSpecialty" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form action="<?= $basePath ?>/academic/profesores/saveSpecialty" method="POST" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-patch-check me-2"></i>Especialidad</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">NOMBRE DE ESPECIALIDAD</label><input type="text" name="specialty_name" class="form-control" required></div><div class="form-check"><input class="form-check-input" type="checkbox" name="is_main" value="1" id="isMainCheck"><label class="form-check-label small fw-bold">Marcar como Especialidad Principal</label></div></div><div class="modal-footer bg-light"><button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button></div></form></div></div>
<div class="modal fade" id="modalDocument" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form action="<?= $basePath ?>/academic/profesores/uploadDocument" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Subir Documento</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">TIPO DE DOCUMENTO</label><select name="document_type" class="form-select"><option>CV</option><option>Identidad</option><option>Certificación</option></select></div><div class="mb-3"><label class="small fw-bold">NOMBRE DESCRIPTIVO</label><input type="text" name="document_name" class="form-control" required></div><div class="mb-3"><label class="small fw-bold">ARCHIVO (PDF o Imagen)</label><input type="file" name="document_file" class="form-control"></div></div><div class="modal-footer bg-light"><button type="submit" class="btn btn-primary rounded-pill px-4">Subir Documento</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="<?= $basePath ?>/assets/js/academic_profesores_edit.js?v=<?= time() ?>"></script>