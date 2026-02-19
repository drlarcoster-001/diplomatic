<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/create.php
 */
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Nuevo Registro de Docente</h2>
        <a href="/diplomatic/public/academic/profesores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Cancelar
        </a>
    </div>

    <div class="card border-0 shadow-sm col-lg-6 mx-auto">
        <div class="card-body p-5">
            <form action="/diplomatic/public/academic/profesores/save" method="POST" autocomplete="off">
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Número de Identificación</label>
                    <input type="text" name="identification" class="form-control form-control-lg" required autofocus>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Nombres</label>
                        <input type="text" name="first_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Apellidos</label>
                        <input type="text" name="last_name" class="form-control form-control-lg" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-primary">TIPO DE PROFESOR</label>
                    <select name="professor_type" class="form-select form-select-lg" required>
                        <option value="" selected disabled>Seleccione...</option>
                        <option value="Docente">Docente</option>
                        <option value="Coordinador">Coordinador</option>
                        <option value="Invitado">Invitado</option>
                        <option value="Tutor">Tutor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow">Crear Perfil <i class="fas fa-arrow-right ms-2"></i></button>
            </form>
        </div>
    </div>
</div>