/**
 * MÓDULO: GESTIÓN FINANCIERA / PROVEEDORES
 * ARCHIVO: public/assets/js/financial_proveedores.js
 * PROPÓSITO: Interactividad del módulo de proveedores.
 * VERSIÓN: 1.0.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/financial/proveedores';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('created')) {
        MySwal.fire({ icon: 'success', title: '¡Proveedor creado!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('updated')) {
        MySwal.fire({ icon: 'success', title: '¡Cambios guardados!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('deleted')) {
        MySwal.fire({ icon: 'success', title: 'Proveedor eliminado.', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('error') === 'duplicate') {
        MySwal.fire({ icon: 'error', title: 'RIF/Cédula duplicado', text: 'Ya existe un proveedor con ese RIF o cédula.', confirmButtonColor: '#fd7e14' });
    }

    // === 1. SUBMIT CON REDIRECT ===
    window.submitForm = function(redirect) {
        const input = document.getElementById('redirect_input');
        if (input) input.value = redirect;
        document.getElementById('formProveedor')?.submit();
    };

    // === 2. PREVIEW NOMBRE EN TIEMPO REAL ===
    const inputNombre = document.querySelector('input[name="nombre"]');
    const previewNombre = document.getElementById('preview-nombre');
    if (inputNombre && previewNombre) {
        inputNombre.addEventListener('input', function () {
            previewNombre.innerText = this.value || 'Nuevo Proveedor';
        });
    }

    // === 3. ELIMINAR DESDE EL INDEX ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id   = this.dataset.id;
            const name = this.dataset.name;

            MySwal.fire({
                title: '¿Eliminar proveedor?',
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
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    });
});
