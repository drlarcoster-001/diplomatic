/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: public/assets/js/financial_gasto_conceptos.js
 * PROPÓSITO: Interactividad del catálogo de conceptos de gasto.
 * VERSIÓN: 1.0.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/financial/gasto-conceptos';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('created') || urlParams.get('updated')) {
        MySwal.fire({ icon: 'success', title: '¡Guardado!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('deleted')) {
        MySwal.fire({ icon: 'success', title: 'Eliminado correctamente.', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('error') === 'duplicate') {
        MySwal.fire({ icon: 'error', title: 'Registro duplicado', text: 'Ya existe un concepto con ese código.', confirmButtonColor: '#0d6efd' });
    }

    // === 1. BOTÓN NUEVO ===
    document.getElementById('btnNuevo').addEventListener('click', function () {
        const form = document.getElementById('formConcepto');
        form.reset();
        document.getElementById('field_id').value = '';
        form.action = `${basePath}/save`;
        document.getElementById('modalTitle').innerText = 'Nuevo Concepto de Gasto';
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
                    const c    = data.concepto;
                    const form = document.getElementById('formConcepto');
                    form.action = `${basePath}/update`;

                    document.getElementById('field_id').value          = c.id;
                    document.getElementById('field_categoria').value   = c.categoria_id;
                    document.getElementById('field_codigo').value      = c.codigo;
                    document.getElementById('field_nombre').value      = c.nombre;
                    document.getElementById('field_descripcion').value = c.descripcion ?? '';
                    document.getElementById('modalTitle').innerText    = 'Editar Concepto de Gasto';

                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalForm')).show();
                });
        });
    });

    // === 3. BOTÓN ELIMINAR ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id   = this.dataset.id;
            const name = this.dataset.name;

            MySwal.fire({
                title: '¿Eliminar concepto?',
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

    // === 5. AUTO-COMPLETAR CÓDIGO AL SELECCIONAR CATEGORÍA ===
    const selectCategoria = document.getElementById('field_categoria');
    if (selectCategoria) {
        selectCategoria.addEventListener('change', function () {
            const codigoActual = document.getElementById('field_codigo').value;
            if (!codigoActual) {
                const opt = this.options[this.selectedIndex];
                const codigoCat = opt.text.split(' — ')[0].trim();
                document.getElementById('field_codigo').value = codigoCat + '-';
                document.getElementById('field_codigo').focus();
            }
        });
    }
});