/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: public/assets/js/financial_gasto_categorias.js
 * PROPÓSITO: Interactividad del catálogo de categorías de gasto.
 * VERSIÓN: 1.0.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/financial/gasto-categorias';
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
        MySwal.fire({ icon: 'error', title: 'No se puede eliminar', text: 'Esta categoría tiene conceptos vinculados.', confirmButtonColor: '#198754' });
    }
    if (urlParams.get('error') === 'duplicate') {
        MySwal.fire({ icon: 'error', title: 'Registro duplicado', text: 'Ya existe una categoría con ese código o nombre.', confirmButtonColor: '#198754' });
    }

    // === 1. BOTÓN NUEVO ===
    document.getElementById('btnNueva').addEventListener('click', function () {
        const form = document.getElementById('formCategoria');
        form.reset();
        document.getElementById('field_id').value = '';
        form.action = `${basePath}/save`;
        document.getElementById('modalTitle').innerText = 'Nueva Categoría de Gasto';
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
                    const c    = data.categoria;
                    const form = document.getElementById('formCategoria');
                    form.action = `${basePath}/update`;

                    document.getElementById('field_id').value          = c.id;
                    document.getElementById('field_codigo').value      = c.codigo;
                    document.getElementById('field_nombre').value      = c.nombre;
                    document.getElementById('field_descripcion').value = c.descripcion ?? '';
                    document.getElementById('modalTitle').innerText    = 'Editar Categoría de Gasto';

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalForm')).show();
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
                    text: `"${name}" tiene ${count} concepto(s) vinculado(s).`,
                    confirmButtonColor: '#198754'
                });
                return;
            }

            MySwal.fire({
                title: '¿Eliminar categoría?',
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

    // === 4. CÓDIGO EN MAYÚSCULAS ===
    const fieldCodigo = document.getElementById('field_codigo');
    if (fieldCodigo) {
        fieldCodigo.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
});