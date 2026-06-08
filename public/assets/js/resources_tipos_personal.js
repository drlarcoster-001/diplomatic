/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: public/assets/js/resources_tipos_personal.js
 * PROPÓSITO: Gestionar la interactividad del catálogo de tipos de personal operativo.
 * VERSIÓN: 1.0.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/resources/tipos-personal';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('created') || urlParams.get('updated')) {
        MySwal.fire({ icon: 'success', title: '¡Guardado!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('deleted')) {
        MySwal.fire({ icon: 'success', title: 'Eliminado correctamente.', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('error') === 'in_use') {
        MySwal.fire({ icon: 'error', title: 'No se puede eliminar', text: 'Este tipo tiene personal vinculado activo.', confirmButtonColor: '#f59e0b' });
    }
    if (urlParams.get('error') === 'duplicate') {
        MySwal.fire({ icon: 'error', title: 'Registro duplicado', text: 'Ya existe un tipo con ese nombre o siglas.', confirmButtonColor: '#f59e0b' });
    }

    // === 1. BOTÓN NUEVO ===
    document.getElementById('btnNuevoTipo').addEventListener('click', function () {
        const form = document.getElementById('formTipo');
        form.reset();
        document.getElementById('field_id').value = '';
        form.action = `${basePath}/save`;
        document.getElementById('modalTipoTitle').innerText = 'Nuevo Tipo de Personal';
    });

    // === 2. BOTÓN EDITAR ===
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id = this.dataset.id;

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.ok) return;
                    const t    = data.tipo;
                    const form = document.getElementById('formTipo');
                    form.action = `${basePath}/update`;

                    document.getElementById('field_id').value          = t.id;
                    document.getElementById('field_nombre').value      = t.nombre;
                    document.getElementById('field_siglas').value      = t.siglas;
                    document.getElementById('field_descripcion').value = t.descripcion ?? '';
                    document.getElementById('modalTipoTitle').innerText = 'Editar Tipo de Personal';

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTipoForm')).show();
                });
        });
    });

    // === 3. BOTÓN ELIMINAR ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id    = this.dataset.id;
            const name  = this.dataset.name;
            const count = parseInt(this.dataset.count);

            if (count > 0) {
                MySwal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    text: `"${name}" tiene ${count} personal vinculado activo.`,
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }

            MySwal.fire({
                title: '¿Eliminar tipo?',
                html: `Se eliminará permanentemente: <b>${name}</b>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor:  '#858796',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = `${basePath}/delete`;
                    const i  = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    });

    // === 4. SIGLAS EN MAYÚSCULAS AUTOMÁTICO ===
    const fieldSiglas = document.getElementById('field_siglas');
    if (fieldSiglas) {
        fieldSiglas.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
});