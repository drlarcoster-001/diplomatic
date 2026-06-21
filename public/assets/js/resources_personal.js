/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: public/assets/js/resources_personal.js
 * PROPÓSITO: Gestionar la interactividad del directorio de personal operativo.
 * VERSIÓN: 1.4.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/resources/personal';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. ALERTAS POR URL ===
    const urlParams   = new URLSearchParams(window.location.search);
    const successType = urlParams.get('success');
    const errorType   = urlParams.get('error');

    if (successType === 'inactivated') {
        MySwal.fire({ icon:'success', title:'¡Operación Exitosa!', text:'El registro ha sido inactivado correctamente.', timer:2000, showConfirmButton:false });
    }
    if (successType === 'deleted') {
    MySwal.fire({ icon:'success', title:'Eliminado', text:'El registro fue eliminado permanentemente.', timer:2000, showConfirmButton:false });
    }
    if (errorType === 'duplicate') {
        MySwal.fire({ icon:'error', title:'Cédula Duplicada', text:'Ya existe un registro con esa cédula de identidad.', confirmButtonColor:'#a855f7' });
    }
    if (errorType === 'db') {
        MySwal.fire({ icon:'error', title:'Error de Sistema', text:'No se pudo procesar la solicitud.', confirmButtonColor:'#e74a3b' });
    }
    if (errorType === 'tiene_sesiones') {
    const count = urlParams.get('count') || '';
    MySwal.fire({
        icon: 'warning',
        title: 'No se puede eliminar',
        text: `Este personal tiene ${count} sesión(es) programada(s). Debes reasignarlas o eliminarlas primero desde Programar Sesiones.`,
        confirmButtonColor: '#a855f7'
    });
    }
    if (urlParams.get('updated')) {
        MySwal.fire({ icon:'success', title:'¡Guardado!', text:'Expediente actualizado correctamente.', timer:1500, showConfirmButton:false });
    }
    if (urlParams.get('created')) {
        MySwal.fire({ icon:'success', title:'¡Registro Creado!', text:'Complete el expediente con los datos adicionales.', timer:2000, showConfirmButton:false });
    }

    // === 1. BOTÓN INACTIVAR ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const id   = this.dataset.id;
            const name = this.dataset.name;
            MySwal.fire({
                title: '¿Eliminar registro?',
                html: `Se eliminará permanentemente a: <b>${name}</b>.<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
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

    // === 2. PREVIEW DE FOTO ===
    window.previewFoto = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('profile-img-preview');
                if (img) img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
            guardarConTab();
        }
    };

    // === 3. GUARDAR RECORDANDO TAB ===
    window.guardarConTab = function() {
    const tabActivo = document.querySelector('#expedienteTabs .nav-link.active');
    const tabId     = tabActivo ? tabActivo.getAttribute('data-bs-target').replace('#tab-', '') : 'datos';
    const form      = document.getElementById('formPersonal');
    if (!form) return;

    MySwal.fire({
        title: '¿A dónde desea ir?',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Guardar y quedarme',
        denyButtonText: 'Guardar e ir al directorio',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#a855f7',
        denyButtonColor: '#6c757d',
    }).then(result => {
        if (result.isConfirmed) {
            let input = form.querySelector('input[name="tab"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tab';
                form.appendChild(input);
            }
            input.value = tabId;
            form.submit();
        } else if (result.isDenied) {
            let input = form.querySelector('input[name="redirect"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'redirect';
                form.appendChild(input);
            }
            input.value = 'index';
            form.submit();
        }
    });
};

    // === 4. MODAL CARNET ===
    let carnetIdActual = null;

    document.querySelectorAll('.btn-carnet').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            carnetIdActual = id;

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.ok) return;
                    const p = data.persona;

                    const avatar = p.foto
                    ? '/diplomatic/public/' + p.foto
                    : (p.profesor_foto
                        ? p.profesor_foto
                        : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(p.first_name + ' ' + p.last_name) + '&background=a855f7&color=fff&size=200&bold=true');

                    const set = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.innerText = val || '—';
                    };

                    const imgEl = document.getElementById('carnet-foto');
                    if (imgEl) imgEl.src = avatar;

                    set('carnet-nombre',      p.first_name + ' ' + p.last_name);
                    set('carnet-cedula',      p.document_id);
                    set('carnet-exp',         p.expediente);
                    set('carnet-tipo',        p.tipo_nombre);
                    set('carnet-email',       p.email);
                    set('carnet-tel',         p.telefono_celular);
                    set('carnet-desde',       p.fecha_inicio);
                    set('carnet-instruccion', p.grado_instruccion);

                    const hoy = new Date().toLocaleDateString('es-VE');
                    const footer = document.getElementById('carnet-footer');
                    if (footer) footer.innerText = 'Generado: ' + hoy + ' — Sistema DIPLOMATIC';

                    resetCarnet();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCarnet')).show();
                });
        });
    });

    // === 5. ZOOM Y DRAG DEL CARNET ===
    let carnetScale = 1;
    let isDragging  = false;
    let startX, startY, translateX = 0, translateY = 0;

    window.zoomCarnet = function(delta) {
        carnetScale = Math.min(Math.max(carnetScale + delta, 0.5), 2.5);
        aplicarTransform();
    };

    window.resetCarnet = function() {
        carnetScale = 1; translateX = 0; translateY = 0;
        aplicarTransform();
    };

    window.imprimirCarnet = function() {
        if (carnetIdActual) {
            window.open(`${basePath}/carnet?id=${carnetIdActual}`, '_blank');
        }
    };

    function aplicarTransform() {
        const inner = document.getElementById('carnet-inner');
        if (inner) inner.style.transform = `translate(${translateX}px, ${translateY}px) scale(${carnetScale})`;
    }

    document.addEventListener('mousedown', e => {
        const visor = document.getElementById('carnet-visor');
        if (visor && visor.contains(e.target)) {
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            visor.style.cursor = 'grabbing';
        }
    });

    document.addEventListener('mousemove', e => {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        aplicarTransform();
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        const visor = document.getElementById('carnet-visor');
        if (visor) visor.style.cursor = 'grab';
    });

    document.addEventListener('wheel', e => {
        const visor = document.getElementById('carnet-visor');
        if (visor && visor.contains(e.target)) {
            e.preventDefault();
            zoomCarnet(e.deltaY > 0 ? -0.1 : 0.1);
        }
    }, { passive: false });

    const modalCarnet = document.getElementById('modalCarnet');
    if (modalCarnet) {
        modalCarnet.addEventListener('hidden.bs.modal', () => resetCarnet());
    }
});