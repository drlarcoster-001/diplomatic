/**
 * MÓDULO: GESTIÓN OPERATIVA / PROFESSORS
 * ARCHIVO: public/assets/js/operational_professors.js
 * PROPÓSITO: Lógica de la grid, bloqueo de edición, recorte 592x592 y selector de categoría web.
 * VERSIÓN: 1.5.0 - UX Fix: Sustitución de terminología técnica por "Página Web".
 */

window.professorsData = {};

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('professors-list');
    const btnFilter = document.getElementById('btn-filter');
    const btnClear = document.getElementById('btn-clear');

    loadProfessors();

    btnFilter.addEventListener('click', loadProfessors);
    btnClear.addEventListener('click', () => {
        document.getElementById('filter-form').reset();
        loadProfessors();
    });

    async function loadProfessors() {
        const search = document.getElementById('search-input').value;
        const specialty = document.getElementById('specialty-filter').value;
        const incomplete = document.getElementById('incomplete-filter').checked;

        tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div> Cargando docentes...</td></tr>';

        try {
            const url = `/diplomatic/public/operational/professors/list?search=${search}&specialty=${specialty}&incomplete=${incomplete}`;
            const response = await fetch(url);
            const text = await response.text(); 
            
            try {
                const result = JSON.parse(text);
                if (result.ok) {
                    renderTable(result.data);
                } else {
                    showError(result.message);
                }
            } catch (e) {
                console.error("Respuesta no válida:", text);
                showError("El servidor devolvió una respuesta inválida.");
            }
        } catch (error) {
            showError("No se pudo conectar con el servidor.");
        }
    }

    function showError(msg) {
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle"></i> ${msg}</td></tr>`;
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron profesores.</td></tr>';
            return;
        }

        tableBody.innerHTML = '';
        window.professorsData = {}; 

        data.forEach(prof => {
            window.professorsData[prof.id] = prof;

            const hasPhoto = parseInt(prof.has_web_photo) === 1;
            const hasBio = parseInt(prof.has_web_bio) === 1;
            const isReady = (hasPhoto && hasBio);
            const isSynced = prof.wp_post_id !== null && prof.wp_post_id !== "0" && prof.wp_post_id !== 0;
            const photoSrc = prof.admin_photo ? prof.admin_photo : '/diplomatic/public/assets/img/avatars/default.png';

            // LÓGICA DE BOTÓN DINÁMICO (Página Web)
            const webActionButton = !isSynced 
                ? `<button class="btn btn-light btn-action" ${!isReady ? 'disabled' : ''} onclick="syncIndividual(${prof.id})" title="Enviar a Página Web">
                        <i class="bi bi-cloud-arrow-up text-success"></i>
                   </button>`
                : `<button class="btn btn-light btn-action" onclick="removeFromWP(${prof.id})" title="Eliminar de Página Web">
                        <i class="bi bi-trash text-danger"></i>
                   </button>`;

            // LÓGICA DE BLOQUEO DE EDICIÓN
            const disableAttr = isSynced ? 'disabled' : '';
            const btnStyle = isSynced ? 'opacity: 0.5; cursor: not-allowed;' : '';
            const titleTextEdit = isSynced ? 'Inhabilitado: Quítelo de la página web para editar' : 'Editar Textos';
            const titlePhotoEdit = isSynced ? 'Inhabilitado: Quítelo de la página web para editar' : 'Recortar Foto';
            const colorTextIcon = isSynced ? 'text-secondary' : 'text-primary';
            const colorPhotoIcon = isSynced ? 'text-secondary' : 'text-info';

            const row = `
                <tr>
                    <td class="ps-4"><input type="checkbox" class="prof-check" value="${prof.id}"></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${photoSrc}" class="avatar-admin me-3" onerror="this.src='/diplomatic/public/assets/img/avatars/default.png'">
                            <div>
                                <div class="fw-bold text-dark">${prof.first_name} ${prof.last_name}</div>
                                <small class="text-muted">ID: ${prof.id}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark fw-normal">${prof.specialty || 'Sin especialidad'}</span></td>
                    <td class="text-center">
                        <div class="resource-indicator">
                            <div class="resource-item" title="${hasPhoto ? 'Foto lista (592x592 px)' : 'Falta foto web'}">
                                <i class="bi bi-image resource-icon ${hasPhoto ? 'active-photo' : 'missing'}"></i>
                            </div>
                            <div class="resource-item" title="${hasBio ? 'Bio lista' : 'Falta biografía'}">
                                <i class="bi bi-file-text resource-icon ${hasBio ? 'active-bio' : 'missing'}"></i>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <i class="bi bi-circle-fill ${isReady ? 'text-success' : 'text-danger'}" 
                           style="font-size: 1.1rem; filter: ${isReady ? 'drop-shadow(0 0 6px rgba(25,135,84,0.7))' : 'drop-shadow(0 0 4px rgba(220,53,69,0.5))'};" 
                           title="${isReady ? 'Completado' : 'Incompleto'}">
                        </i>
                    </td>
                    <td class="text-center">
                        <span class="wp-status-badge ${isSynced ? 'status-online' : 'status-offline'}">
                            <i class="bi bi-globe"></i> ${isSynced ? 'Publicado' : 'No enviado'}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-light btn-action me-1" onclick="openPreviewModal(${prof.id})" title="Vista Previa">
                            <i class="bi bi-eye text-dark"></i>
                        </button>
                        <button class="btn btn-light btn-action me-1" ${disableAttr} style="${btnStyle}" onclick="openTextModal(${prof.id})" title="${titleTextEdit}">
                            <i class="bi bi-file-text ${colorTextIcon}"></i>
                        </button>
                        <button class="btn btn-light btn-action me-1" ${disableAttr} style="${btnStyle}" onclick="openImageModal(${prof.id})" title="${titlePhotoEdit}">
                            <i class="bi bi-crop ${colorPhotoIcon}"></i>
                        </button>
                        ${webActionButton}
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    window.loadProfessors = loadProfessors;
});

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function toCamelCase(str) {
    if (!str) return '';
    return str.replace(/\w\S*/g, function(txt){
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
}

// ==========================================
// 1. MODALES (PREVIEW, TEXT, IMAGE)
// ==========================================
function openPreviewModal(id) {
    const prof = window.professorsData[id];
    
    // REGLA: 1 Nombre, 1 Apellido
    const fName = prof.first_name ? prof.first_name.trim().split(' ')[0] : '';
    const lName = prof.last_name ? prof.last_name.trim().split(' ')[0] : '';
    const name = toCamelCase(`${fName} ${lName}`.trim());

    const photo = prof.web_photo_url ? prof.web_photo_url : '/diplomatic/public/assets/img/avatars/default.png';
    const label = prof.web_label || 'Cargo sin definir';
    let bio = prof.web_bio ? prof.web_bio : '<i class="text-muted">Biografía sin definir...</i>';
    if (!bio.includes('<p>')) {
        bio = bio.split('\n\n').map(p => `<p style="margin-bottom:1.2rem;">${p.replace(/\n/g, '<br>')}</p>`).join('');
    }
    Swal.fire({
        html: `<div style="display: flex; flex-wrap: wrap; gap: 40px; text-align: left; padding: 20px 10px;">
                <div style="flex: 1 1 250px;"><img src="${photo}" style="width: 100%; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"></div>
                <div style="flex: 2 1 400px;">
                    <h2 style="margin: 0 0 5px 0; font-size: 2.2rem;">${name}</h2>
                    <h2 style="margin: 0 0 25px 0; font-size: 1.3rem; color: #6c757d;">${toCamelCase(label)}</h2>
                    <div style="font-size: 1.05rem; color: #4a4a4a; line-height: 1.7;">${bio}</div>
                </div>
               </div>`,
        showConfirmButton: false, showCloseButton: true, width: '900px'
    });
}

function openTextModal(id) {
    const prof = window.professorsData[id];
    const currentLabel = escapeHtml(prof.web_label);
    const currentBio = escapeHtml(prof.web_bio);
    Swal.fire({
        title: `Textos Web: ${toCamelCase(prof.first_name)}`,
        html: `<form id="form-web-text" class="text-start">
                <div class="mb-3">
                    <label class="small fw-bold text-primary">Cargo (Se aplicará formato Camel Case)</label>
                    <input type="text" id="web_label" class="form-control" value="${currentLabel}" placeholder="Ej: Personal Administrativo" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Biografía</label>
                    <textarea id="web_bio" class="form-control" rows="6" required>${currentBio}</textarea>
                </div>
               </form>`,
        showCancelButton: true, confirmButtonText: 'Guardar',
        preConfirm: () => { 
            const camelLabel = toCamelCase(document.getElementById('web_label').value);
            return { id: id, label: camelLabel, bio: document.getElementById('web_bio').value }; 
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', result.value.id);
            formData.append('label', result.value.label);
            formData.append('bio', result.value.bio);
            const response = await fetch('/diplomatic/public/operational/professors/saveTexts', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.ok) { Swal.fire('¡Guardado!', '', 'success'); window.loadProfessors(); }
        }
    });
}

let cropper = null; 
function openImageModal(id) {
    const prof = window.professorsData[id];
    Swal.fire({
        title: `Foto Web: ${toCamelCase(prof.first_name)}`,
        html: `<div class="text-start">
                <div class="alert alert-info py-2 small">Asegúrese de subir imagen con <b>fondo blanco</b>. El sistema exportará a 592x592 px.</div>
                <input type="file" id="imageInput" class="form-control mb-3" accept="image/*">
                <div style="height: 350px; background: #fff; border: 1px dashed #ccc; display: flex; justify-content: center; align-items: center;">
                    <img id="imageToCrop" style="max-width: 100%; display: none;"><span id="cropPlaceholder">Seleccione imagen</span>
                </div></div>`,
        didOpen: () => {
            const input = document.getElementById('imageInput');
            input.addEventListener('change', (e) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    document.getElementById('cropPlaceholder').style.display = 'none';
                    const img = document.getElementById('imageToCrop');
                    img.src = event.target.result; img.style.display = 'block';
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(img, { aspectRatio: 1, viewMode: 1 });
                };
                reader.readAsDataURL(e.target.files[0]);
            });
        },
        preConfirm: () => { return cropper ? cropper.getCroppedCanvas({width:592, height:592, fillColor: '#fff'}).toDataURL('image/jpeg', 1.0) : null; }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            const formData = new FormData();
            formData.append('id', id); formData.append('image', result.value);
            const response = await fetch('/diplomatic/public/operational/professors/saveImage', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.ok) { Swal.fire('¡Guardado!', '', 'success'); window.loadProfessors(); }
        }
    });
}

// ==========================================
// 2. ACCIONES WEB (SUBIR & ELIMINAR)
// ==========================================

async function syncIndividual(id) {
    const prof = window.professorsData[id];
    
    const fName = prof.first_name ? prof.first_name.trim().split(' ')[0] : '';
    const lName = prof.last_name ? prof.last_name.trim().split(' ')[0] : '';
    const cleanName = toCamelCase(`${fName} ${lName}`.trim());

    // REGLA: SELECTOR DE CATEGORÍA WEB OBLIGATORIO
    const { value: wpCategory } = await Swal.fire({
        title: 'Categoría para Página Web',
        html: `Seleccione la categoría donde se publicará a <b>${cleanName}</b>:`,
        input: 'select',
        inputOptions: {
            'docente': 'Docente'
            // 'Administrativo': 'Administrativo', // Comentado: Sin plantilla activa
            // 'Coordinador': 'Coordinador'      // Comentado: Sin plantilla activa
        },
        inputPlaceholder: 'Seleccione una opción...',
        showCancelButton: true,
        confirmButtonText: 'Sincronizar',
        confirmButtonColor: '#198754',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            return new Promise((resolve) => {
                if (value) { resolve() }
                else { resolve('Debe seleccionar una categoría obligatoriamente.') }
            })
        }
    });

    if (!wpCategory) return;

    Swal.fire({ title: 'Montando en la página web...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('wp_category', wpCategory);

        const response = await fetch('/diplomatic/public/settings/wordpress/sync-prof', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.ok) {
            Swal.fire('¡Sincronizado!', `Publicado en la sección ${wpCategory}.`, 'success');
            window.loadProfessors(); 
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Fallo de conexión.', 'error');
    }
}

async function removeFromWP(id) {
    const prof = window.professorsData[id];
    const confirm = await Swal.fire({
        title: '¿Eliminar de la Página Web?',
        text: `Se eliminará la entrada de ${prof.first_name} en la página web. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({ title: 'Eliminando de la página web...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch('/diplomatic/public/settings/wordpress/delete-prof', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.ok) {
            Swal.fire('Eliminado', 'El registro ha sido quitado de la página web.', 'success');
            window.loadProfessors(); 
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
    }
}