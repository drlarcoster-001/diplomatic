/**
 * MÓDULO: GESTIÓN OPERATIVA / NEWS (CARTELERA)
 * ARCHIVO: public/assets/js/operational_news.js
 * PROPÓSITO: Lógica de la grid, publicación en web y gestión de contenidos.
 * VERSIÓN: 1.3.0 - UX Fix: Sustitución de terminología técnica por "Página Web" y "Publicación".
 */

window.newsData = {};

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('news-list');
    const btnFilter = document.getElementById('btn-filter');
    const btnClear = document.getElementById('btn-clear');

    loadNews();

    btnFilter.addEventListener('click', loadNews);
    btnClear.addEventListener('click', () => {
        document.getElementById('filter-form').reset();
        loadNews();
    });

    async function loadNews() {
        const search = document.getElementById('search-input').value;
        const incomplete = document.getElementById('incomplete-filter').checked;

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div> Cargando publicaciones...</td></tr>';

        try {
            const url = `${BASE_URL}/operational/news/list?search=${search}&incomplete=${incomplete}`;
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
                console.error("Respuesta no válida del servidor:", text);
                showError("El servidor devolvió una respuesta inválida.");
            }

        } catch (error) {
            console.error("Error de red:", error);
            showError("No se pudo conectar con el servidor.");
        }
    }

    function showError(msg) {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle"></i> ${msg}</td></tr>`;
    }

    function renderTable(data) {
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay publicaciones registradas.</td></tr>';
            return;
        }

        tableBody.innerHTML = '';
        window.newsData = {}; 

        data.forEach(news => {
            window.newsData[news.id] = news;

            const hasPhoto = parseInt(news.has_image) === 1;
            const hasContent = parseInt(news.has_content) === 1;
            const isReady = (hasPhoto && hasContent);
            const isSynced = news.wp_post_id !== null && parseInt(news.wp_post_id) > 0;
            const photoSrc = news.image_url ? news.image_url : `${BASE_URL}/assets/img/placeholder_news.png`;

            const row = `
                <tr class="${isSynced ? 'table-light' : ''}">
                    <td class="ps-4"><input type="checkbox" class="news-check" value="${news.id}"></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${photoSrc}" class="avatar-news me-3" onerror="this.src='${BASE_URL}/assets/img/placeholder_news.png'">
                            <div>
                                <div class="fw-bold ${isSynced ? 'text-success' : 'text-dark'} text-truncate" style="max-width: 300px;" title="${news.title}">
                                    ${isSynced ? '<i class="bi bi-check-circle-fill me-1"></i>' : ''} ${news.title}
                                </div>
                                <small class="text-muted d-block text-truncate" style="max-width: 300px;">${news.excerpt || 'Sin extracto'}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="resource-indicator">
                            <div class="resource-item" title="${hasPhoto ? 'Portada lista' : 'Falta foto principal'}">
                                <i class="bi bi-image resource-icon ${hasPhoto ? 'active-photo' : 'missing'}"></i>
                            </div>
                            <div class="resource-item" title="${hasContent ? 'Contenido listo' : 'Falta contenido'}">
                                <i class="bi bi-text-paragraph resource-icon ${hasContent ? 'active-content' : 'missing'}"></i>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <i class="bi bi-circle-fill ${isReady ? 'text-success' : 'text-danger'}" 
                           style="font-size: 1.1rem; filter: ${isReady ? 'drop-shadow(0 0 6px rgba(25,135,84,0.7))' : 'drop-shadow(0 0 4px rgba(220,53,69,0.5))'};" 
                           title="${isReady ? 'Completada' : 'Incompleta'}">
                        </i>
                    </td>
                    <td class="text-center">
                        <span class="wp-status-badge ${isSynced ? 'status-online' : 'status-offline'}">
                            <i class="bi bi-globe"></i> ${isSynced ? 'Publicada' : 'Borrador'}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-light btn-action me-1" onclick="openPreviewModal(${news.id})" title="Vista Previa">
                            <i class="bi bi-eye text-dark"></i>
                        </button>

                        <button class="btn btn-light btn-action me-1" 
                                ${isSynced ? 'disabled' : ''} 
                                onclick="openTextModal(${news.id})" 
                                title="${isSynced ? 'Bloqueado: Publicación en la Web' : 'Editar Textos'}">
                            <i class="bi bi-pencil-square ${isSynced ? 'text-secondary opacity-50' : 'text-primary'}"></i>
                        </button>

                        <button class="btn btn-light btn-action me-1" 
                                ${isSynced ? 'disabled' : ''} 
                                onclick="openImageModal(${news.id})" 
                                title="${isSynced ? 'Bloqueado: Publicación en la Web' : 'Recortar Portada'}">
                            <i class="bi bi-crop ${isSynced ? 'text-secondary opacity-50' : 'text-info'}"></i>
                        </button>

                        <button class="btn btn-light btn-action me-1" 
                                ${isSynced ? 'disabled' : ''}
                                onclick="deleteNews(${news.id})" 
                                title="${isSynced ? 'Debe retirar la publicación de la web primero' : 'Eliminar Publicación'}">
                            <i class="bi bi-trash ${isSynced ? 'text-secondary opacity-50' : 'text-danger'}"></i>
                        </button>

                        ${isSynced ? `
                            <button class="btn btn-outline-danger btn-action" onclick="unpublishNews(${news.id})" title="Bajar de la página web">
                                <i class="bi bi-cloud-arrow-down"></i>
                            </button>
                        ` : `
                            <button class="btn btn-light btn-action" ${!isReady ? 'disabled' : ''} onclick="publishNews(${news.id})" title="Enviar a página web">
                                <i class="bi bi-cloud-arrow-up text-success"></i>
                            </button>
                        `}
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    window.loadNews = loadNews;
});

// ==========================================
// 1. MODAL DE TEXTOS (CREAR O EDITAR)
// ==========================================
window.openTextModal = function(id) {
    if (id > 0 && window.newsData[id] && window.newsData[id].wp_post_id > 0) {
        return Swal.fire('Acción Bloqueada', 'No puede editar una publicación ya visible en la web.', 'warning');
    }

    let currentTitle = '', currentExcerpt = '', currentContent = '';
    let modalTitle = 'Redactar Nueva Publicación';

    if (id > 0 && window.newsData[id]) {
        const n = window.newsData[id];
        currentTitle = n.title;
        currentExcerpt = n.excerpt;
        currentContent = n.content;
        modalTitle = 'Editar Publicación';
    }

    Swal.fire({
        title: modalTitle,
        html: `
            <form id="form-news-text" class="text-start">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Título de la Publicación</label>
                    <input type="text" id="news_title" class="form-control" placeholder="Ej: Nueva modalidad de pago..." value="${currentTitle}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Extracto Breve</label>
                    <textarea id="news_excerpt" class="form-control" rows="2" placeholder="Resumen para la cartelera...">${currentExcerpt}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Contenido Completo</label>
                    <textarea id="news_content" class="form-control" rows="8" placeholder="Redacte la información aquí..." required>${currentContent}</textarea>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-save"></i> Guardar Borrador',
        cancelButtonText: 'Cancelar',
        width: '700px',
        preConfirm: () => {
            const title = document.getElementById('news_title').value;
            const excerpt = document.getElementById('news_excerpt').value;
            const content = document.getElementById('news_content').value;
            if (!title || !content) return Swal.showValidationMessage(`El Título y el Contenido son obligatorios`);
            return { id: id, title: title, excerpt: excerpt, content: content };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Guardando datos...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            try {
                const formData = new FormData();
                formData.append('id', result.value.id);
                formData.append('title', result.value.title);
                formData.append('excerpt', result.value.excerpt);
                formData.append('content', result.value.content);

                const response = await fetch(`${BASE_URL}/operational/news/saveTexts`, { method: 'POST', body: formData });
                const resData = await response.json();

                if (resData.ok) {
                    Swal.fire('¡Guardado!', 'La publicación ha sido guardada localmente.', 'success');
                    window.loadNews(); 
                } else {
                    Swal.fire('Error', resData.message, 'error');
                }
            } catch (error) { Swal.fire('Error', 'Fallo de conexión.', 'error'); }
        }
    });
};

// ==========================================
// 2. MODAL DE VISTA PREVIA
// ==========================================
window.openPreviewModal = function(id) {
    const n = window.newsData[id];
    const photo = n.image_url ? n.image_url : `${BASE_URL}/assets/img/placeholder_news.png`;
    const title = n.title || 'Sin título';
    const excerpt = n.excerpt ? `<h3 style="color: #64748b; font-size: 1.25rem; font-weight: 500; margin-bottom: 20px; line-height: 1.4;">${n.excerpt}</h3>` : '';
    let content = n.content ? n.content : '<i class="text-muted">Sin contenido...</i>';
    if (!content.includes('<p>')) {
        content = content.split('\n\n').map(p => `<p style="margin-bottom:1.2rem;">${p.replace(/\n/g, '<br>')}</p>`).join('');
    }

    Swal.fire({
        html: `
            <div style="text-align: left; padding: 10px;">
                <div style="width: 100%; margin-bottom: 25px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <img src="${photo}" style="width: 100%; height: auto; display: block;">
                </div>
                <h1 style="color: #1e293b; font-size: 2.2rem; font-weight: 800; margin-bottom: 10px;">${title}</h1>
                ${excerpt}
                <hr style="border-top: 2px solid #f1f5f9; margin: 25px 0;">
                <div style="font-size: 1.1rem; color: #334155; line-height: 1.8;">${content}</div>
            </div>
        `,
        showConfirmButton: false, showCloseButton: true, width: '850px',
    });
};

// ==========================================
// 3. MODAL DE IMAGEN CON CROPPER
// ==========================================
let cropper = null; 
window.openImageModal = function(id) {
    if (id === 0) return Swal.fire('Atención', 'Primero debe guardar los textos.', 'info');
    if (window.newsData[id] && window.newsData[id].wp_post_id > 0) {
        return Swal.fire('Acción Bloqueada', 'No puede cambiar la portada de una publicación activa en la web.', 'warning');
    }

    Swal.fire({
        title: `Portada de Publicación (16:9)`,
        html: `
            <div class="text-start">
                <input type="file" id="imageInput" class="form-control mb-3" accept="image/*">
                <div style="height: 400px; width: 100%; overflow: hidden; background: #f8f9fa; border: 2px dashed #dee2e6; display: flex; justify-content: center; align-items: center;">
                    <img id="imageToCrop" style="max-width: 100%; display: none;">
                    <div id="cropPlaceholder" class="text-center text-muted">
                        <i class="bi bi-cloud-upload" style="font-size: 3rem;"></i>
                        <p class="small mt-2">Cargue la imagen aquí</p>
                    </div>
                </div>
            </div>
        `,
        width: '800px', showCancelButton: true, confirmButtonText: 'Aplicar Recorte', confirmButtonColor: '#0dcaf0',
        didOpen: () => {
            const input = document.getElementById('imageInput');
            input.addEventListener('change', (e) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    document.getElementById('cropPlaceholder').style.display = 'none';
                    const img = document.getElementById('imageToCrop');
                    img.src = event.target.result; img.style.display = 'block';
                    if (cropper) cropper.destroy();
                    setTimeout(() => {
                        cropper = new Cropper(img, { aspectRatio: 16 / 9, viewMode: 1 });
                    }, 150);
                };
                reader.readAsDataURL(e.target.files[0]);
            });
        },
        preConfirm: () => {
            if (!cropper) return Swal.showValidationMessage('Debe cargar una imagen.');
            return cropper.getCroppedCanvas({ width: 1280, height: 720, fillColor: '#fff' }).toDataURL('image/jpeg', 0.90); 
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Subiendo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const formData = new FormData();
            formData.append('id', id); formData.append('image', result.value);
            try {
                const response = await fetch(`${BASE_URL}/operational/news/saveImage`, { method: 'POST', body: formData });
                const res = await response.json();
                if (res.ok) { Swal.fire('¡Éxito!', 'Portada guardada.', 'success'); window.loadNews(); }
                else { Swal.fire('Error', res.message, 'error'); }
            } catch (e) { Swal.fire('Error', 'Fallo de red.', 'error'); }
        }
    });
};

// ==========================================
// 4. PUBLICAR EN WEB
// ==========================================
window.publishNews = function(id) {
    Swal.fire({
        title: '¿Publicar en la Web?',
        text: "Esta Publicación será mostrada en la cartelera y será visible para el público.",
        icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, publicar ahora', confirmButtonColor: '#198754'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Sincronizando en Página Web...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const formData = new FormData(); formData.append('id', id);
            try {
                const response = await fetch(`${BASE_URL}/operational/news/publish`, { method: 'POST', body: formData });
                const res = await response.json();
                if (res.ok) {
                    Swal.fire('¡Publicada!', 'La Publicación ya está en la página web.', 'success');
                    window.loadNews();
                } else { Swal.fire('Fallo', res.message, 'error'); }
            } catch (e) { Swal.fire('Error', 'Fallo de comunicación.', 'error'); }
        }
    });
};

// ==========================================
// 5. RETIRAR DE LA WEB
// ==========================================
window.unpublishNews = function(id) {
    Swal.fire({
        title: '¿Retirar de la Web?',
        text: "Se eliminará la publicación de la página web, ¿está de acuerdo?",
        icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, bajar de la web', confirmButtonColor: '#dc3545'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Retirando de la página web...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const formData = new FormData(); formData.append('id', id);
            try {
                const response = await fetch(`${BASE_URL}/operational/news/unpublish`, { method: 'POST', body: formData });
                const res = await response.json();
                if (res.ok) {
                    Swal.fire('Retirada', 'La publicación fue retirada de la web con éxito.', 'success');
                    window.loadNews();
                } else { Swal.fire('Error', res.message, 'error'); }
            } catch (e) { Swal.fire('Error', 'Fallo de servidor.', 'error'); }
        }
    });
};

// ==========================================
// 6. ELIMINAR (LOCAL)
// ==========================================
window.deleteNews = function(id) {
    if (window.newsData[id] && window.newsData[id].wp_post_id > 0) {
        return Swal.fire('Atención', 'No puede eliminar una publicación activa. Retírela de la web primero.', 'error');
    }
    Swal.fire({
        title: '¿Eliminar publicación?',
        text: "Se borrará permanentemente de la base de datos local.",
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, eliminar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Borrando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const formData = new FormData(); formData.append('id', id);
            try {
                const response = await fetch(`${BASE_URL}/operational/news/delete`, { method: 'POST', body: formData });
                const res = await response.json();
                if (res.ok) { Swal.fire('Borrada', 'Eliminación completada.', 'success'); window.loadNews(); }
                else { Swal.fire('Error', res.message, 'error'); }
            } catch (e) { Swal.fire('Error', 'Error de red.', 'error'); }
        }
    });
};