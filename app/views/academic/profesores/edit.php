<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/edit.php
 * Propósito: Vista para la edición detallada de expedientes docentes.
 */
$p = $profesor; 
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_profesores_edit.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Expediente del Docente</h2>
            <p class="text-muted small">ID: #<?= $p['id'] ?> | Registro: <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic/profesores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm"><i class="fas fa-arrow-left me-1"></i> Volver</a>
            <button class="btn btn-success rounded-pill px-4 shadow-sm" onclick="document.getElementById('formBasicData').submit();"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm text-center p-4">
                <?php $avatar = !empty($p['photo_path']) ? $p['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . ' ' . $p['last_name']) . '&background=4e73df&color=fff&size=150'; ?>
                <div class="position-relative mx-auto mb-3" style="width: 150px; height: 150px;">
                    <img src="<?= $avatar ?>" id="profile-img-preview" class="rounded-circle object-fit-cover shadow-sm w-100 h-100 border border-3 border-white">
                    <button type="button" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 shadow btn-change-photo" style="width:35px; height:35px;"><i class="fas fa-camera"></i></button>
                    <input type="file" id="inputPhotoUpload" accept="image/*" style="display:none;">
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($p['full_name']) ?></h5>
                <span class="badge bg-primary rounded-pill px-3 py-2 w-100 mb-2"><?= $p['professor_type'] ?></span>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <ul class="nav nav-tabs fw-bold" id="expedienteTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#datos" type="button"><i class="fas fa-id-card me-2"></i> Datos</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#formacion" type="button"><i class="fas fa-graduation-cap me-2"></i> Formación</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#experiencia" type="button"><i class="fas fa-briefcase me-2"></i> Experiencia</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#especialidades" type="button"><i class="fas fa-star me-2"></i> Especialidades</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#documentos" type="button"><i class="fas fa-folder me-2"></i> Documentos</button></li>
                    </ul>
                </div>
                
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="datos">
                            <form id="formBasicData" action="/diplomatic/public/academic/profesores/updateBase" method="POST" class="row g-4">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <div class="col-md-4"><label class="form-label small fw-bold">IDENTIFICACIÓN</label><input type="text" name="identification" class="form-control" value="<?= htmlspecialchars($p['identification']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">NOMBRES</label><input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($p['first_name']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">APELLIDOS</label><input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($p['last_name']) ?>" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">EMAIL</label><input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($p['contact']['email'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">TELÉFONO</label><input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($p['contact']['phone'] ?? '') ?>"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">LINKEDIN</label><input type="url" name="contact_linkedin" class="form-control" value="<?= htmlspecialchars($p['contact']['linkedin_url'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">OTRO CONTACTO</label><input type="text" name="other_contact" class="form-control" value="<?= htmlspecialchars($p['contact']['other_contact'] ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">TIPO DE PROFESOR</label>
                                    <select name="professor_type" class="form-select">
                                        <option value="Docente" <?= $p['professor_type'] == 'Docente' ? 'selected' : '' ?>>Docente</option>
                                        <option value="Coordinador" <?= $p['professor_type'] == 'Coordinador' ? 'selected' : '' ?>>Coordinador</option>
                                        <option value="Invitado" <?= $p['professor_type'] == 'Invitado' ? 'selected' : '' ?>>Invitado</option>
                                        <option value="Tutor" <?= $p['professor_type'] == 'Tutor' ? 'selected' : '' ?>>Tutor</option>
                                    </select>
                                </div>
                                <div class="col-12"><label class="form-label small fw-bold">BIOGRAFÍA</label><textarea name="biography" class="form-control" rows="3"><?= htmlspecialchars($p['biography'] ?? '') ?></textarea></div>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade" id="formacion">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0">Formación Académica</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalFormation">+ Añadir</button></div>
                            <table class="table table-sm bg-white border rounded table-hover">
                                <thead class="bg-light"><tr><th>Título</th><th>Área</th><th>Institución</th><th>Año</th><th class="text-end"></th></tr></thead>
                                <tbody>
                                    <?php foreach($p['formations'] as $f): ?>
                                    <tr class="edit-row" data-target-modal="#modalFormation" data-json='<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;" title="Clic para modificar">
                                        <td class="align-middle"><?= htmlspecialchars($f['degree_title']) ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($f['study_area'] ?? '-') ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($f['institution']) ?></td>
                                        <td class="align-middle"><?= $f['year_obtained'] ?></td>
                                        <td class="text-end align-middle">
                                            <form action="/diplomatic/public/academic/profesores/deleteFormation" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                <button type="button" class="btn btn-sm text-danger btn-delete-record" title="Eliminar"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="experiencia">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0">Trayectoria Laboral</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalWork">+ Añadir</button></div>
                            <table class="table table-sm bg-white border rounded table-hover">
                                <thead class="bg-light"><tr><th>Cargo</th><th>Empresa</th><th>Período</th><th class="text-end"></th></tr></thead>
                                <tbody>
                                    <?php foreach($p['work_experiences'] as $w): ?>
                                    <tr class="edit-row" data-target-modal="#modalWork" data-json='<?= htmlspecialchars(json_encode($w), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;" title="Clic para modificar">
                                        <td class="align-middle"><?= htmlspecialchars($w['job_title']) ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($w['institution']) ?></td>
                                        <td class="align-middle"><?= $w['start_date'] ?> / <?= $w['is_current'] ? 'Hoy' : $w['end_date'] ?></td>
                                        <td class="text-end align-middle">
                                            <form action="/diplomatic/public/academic/profesores/deleteWork" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $w['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                <button type="button" class="btn btn-sm text-danger btn-delete-record" title="Eliminar"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="especialidades">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0">Áreas de Conocimiento</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalSpecialty">+ Añadir</button></div>
                            <table class="table table-sm bg-white border rounded table-hover">
                                <thead class="bg-light"><tr><th>Especialidad</th><th>Principal</th><th class="text-end"></th></tr></thead>
                                <tbody>
                                    <?php foreach($p['specialties'] as $s): ?>
                                    <tr class="edit-row" data-target-modal="#modalSpecialty" data-json='<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;" title="Clic para modificar">
                                        <td class="align-middle"><?= htmlspecialchars($s['specialty_name']) ?></td>
                                        <td class="align-middle"><?= $s['is_main'] ? 'SÍ' : 'NO' ?></td>
                                        <td class="text-end align-middle">
                                            <form action="/diplomatic/public/academic/profesores/deleteSpecialty" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                <button type="button" class="btn btn-sm text-danger btn-delete-record" title="Eliminar"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="documentos">
                            <div class="d-flex justify-content-between mb-3 align-items-center"><h6 class="fw-bold mb-0">Soportes Digitales</h6><button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-add-modal" data-target-modal="#modalDocument">+ Subir</button></div>
                            <table class="table table-sm bg-white border rounded table-hover">
                                <thead class="bg-light"><tr><th>Tipo</th><th>Nombre</th><th>Ver</th><th class="text-end"></th></tr></thead>
                                <tbody>
                                    <?php foreach($p['documents'] as $d): ?>
                                    <tr class="edit-row" data-target-modal="#modalDocument" data-json='<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>' style="cursor:pointer;" title="Clic para modificar datos">
                                        <td class="align-middle"><?= htmlspecialchars($d['document_type']) ?></td>
                                        <td class="align-middle"><?= htmlspecialchars($d['document_name']) ?></td>
                                        <td class="align-middle"><a href="<?= $d['file_path'] ?>" target="_blank" class="btn btn-link py-0 px-0 text-primary no-edit">Ver Archivo</a></td>
                                        <td class="text-end align-middle">
                                            <form action="/diplomatic/public/academic/profesores/deleteDocument" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>">
                                                <button type="button" class="btn btn-sm text-danger btn-delete-record" title="Eliminar"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
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

<div class="modal fade" id="modalCrop" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg text-center"><div class="modal-header bg-dark text-white border-0"><h5 class="modal-title fw-bold">Ajustar Foto</h5></div><div class="modal-body p-0 bg-light"><img id="imageToCrop" src="" style="max-width: 100%;"></div><div class="modal-footer border-0"><button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary px-4 rounded-pill" id="btnSaveCrop">Guardar</button></div></div></div></div>

<div class="modal fade" id="modalFormation" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form action="/diplomatic/public/academic/profesores/saveFormation" method="POST" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white border-0"><h5 class="modal-title">Añadir Título</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">TÍTULO</label><input type="text" name="degree_title" class="form-control" required></div><div class="mb-3"><label class="small fw-bold">ÁREA DE ESTUDIO</label><input type="text" name="study_area" class="form-control"></div><div class="mb-3"><label class="small fw-bold">NIVEL</label><select name="academic_level" class="form-select"><option>Pregrado</option><option>Especialista</option><option>Magister</option><option>Doctorado</option></select></div><div class="mb-3"><label class="small fw-bold">INSTITUCIÓN</label><input type="text" name="institution" class="form-control" required></div><div class="mb-3"><label class="small fw-bold">AÑO</label><input type="number" name="year_obtained" class="form-control"></div></div><div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalWork" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form action="/diplomatic/public/academic/profesores/saveWork" method="POST" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white border-0"><h5 class="modal-title">Experiencia Laboral</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">CARGO</label><input type="text" name="job_title" class="form-control" required></div><div class="mb-3"><label class="small fw-bold">EMPRESA</label><input type="text" name="institution" class="form-control" required></div><div class="mb-3"><label class="small fw-bold">DESCRIPCIÓN</label><textarea name="description" class="form-control" rows="2"></textarea></div><div class="row g-3"><div class="col-6"><label class="small fw-bold">INICIO</label><input type="date" name="start_date" class="form-control" required></div><div class="col-6"><label class="small fw-bold">FIN</label><input type="date" name="end_date" id="work_end_date" class="form-control"></div></div><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="is_current" id="check_current" value="1"><label class="form-check-label small fw-bold">Cargo Actual</label></div></div><div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalSpecialty" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="/diplomatic/public/academic/profesores/saveSpecialty" method="POST" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white border-0"><h5 class="modal-title">Especialidad</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">NOMBRE DE ESPECIALIDAD</label><input type="text" name="specialty_name" class="form-control" required></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_main" value="1" id="isMainCheck"><label class="form-check-label small fw-bold" for="isMainCheck">Marcar como Principal</label></div></div><div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalDocument" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="/diplomatic/public/academic/profesores/uploadDocument" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg"><div class="modal-header bg-primary text-white border-0"><h5 class="modal-title">Subir Documento</h5></div><div class="modal-body p-4"><input type="hidden" name="id"><input type="hidden" name="professor_id" value="<?= $p['id'] ?>"><div class="mb-3"><label class="small fw-bold">TIPO</label><select name="document_type" class="form-select" required><option>CV</option><option>Reseña</option><option>Certificación Académica</option><option>Documento de Identidad</option><option>Otro</option></select></div><div class="mb-3"><label class="small fw-bold">NOMBRE DESCRIPTIVO</label><input type="text" name="document_name" class="form-control" required></div><div class="mb-3" id="file_input_wrapper"><label class="small fw-bold">ARCHIVO</label><input type="file" name="document_file" class="form-control" required accept=".pdf,.doc,.docx"></div></div><div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary rounded-pill px-4 btn-submit-modal">Subir</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="/diplomatic/public/assets/js/academic_profesores_edit.js"></script>