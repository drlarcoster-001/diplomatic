/**
 * MÓDULO: GESTIÓN FINANCIERA / REVERSO DE OPERACIONES
 * ARCHIVO: public/assets/js/financial_reverse_operations.js
 * PROPÓSITO: Manejo de interfaz dual, carga de bandejas y ejecución de reverso mediante clic en fila.
 * VERSIÓN: 3.2.0 - UX Fix: Disparo de modal por fila completa (Row-Click) y optimización de delegación.
 */

window.loadEstudiantesCuotas = null;

document.addEventListener('DOMContentLoaded', () => {
    "use strict";

    // 1. MANEJO VISUAL DE PESTAÑAS (Bootstrap 5)
    const tabElements = document.querySelectorAll('button[data-bs-toggle="tab"]');
    
    tabElements.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', (e) => {
            tabElements.forEach(btn => {
                btn.classList.remove('text-danger', 'text-primary', 'fw-bold');
                btn.classList.add('text-muted');
            });

            const target = e.target.dataset.bsTarget;
            
            if (target === '#tab-inscripciones') {
                e.target.classList.remove('text-muted');
                e.target.classList.add('text-danger', 'fw-bold');
                loadInscripciones();
            } else if (target === '#tab-cuotas') {
                e.target.classList.remove('text-muted');
                e.target.classList.add('text-primary', 'fw-bold');
                loadEstudiantesCuotas(1);
            }
        });
    });

    // 2. FUNCIÓN: CARGAR INSCRIPCIONES
    async function loadInscripciones() {
        const tbody = document.querySelector('#resultsInscripciones');
        const searchInput = document.getElementById('search-inscripcion').value.trim();
        
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-danger"></div><p class="mt-2 text-muted small">Buscando inscripciones...</p></td></tr>';

        const urlSearch = (`${BASE_URL}/financial/reverse_operations/search_inscripciones`).replace(/\/+/g, '/');

        try {
            const params = new URLSearchParams();
            params.append('search', searchInput);

            const response = await fetch(urlSearch, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params
            });

            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                throw new Error("Respuesta no válida del servidor.");
            }

            if(data.ok) {
                if(data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted small"><i class="bi bi-shield-check d-block fs-3 mb-2 text-success"></i> No hay inscripciones pendientes.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.data.forEach(item => {
                    const montoFormat = parseFloat(item.monto).toLocaleString('es-VE', {minimumFractionDigits: 2});
                    const tr = document.createElement('tr');
                    tr.className = 'row-inscripcion-clickable'; // Gatillo para el clic
                    tr.dataset.paymentId = item.payment_id;
                    tr.dataset.enrollmentId = item.enrollment_id;
                    tr.dataset.nombre = item.participante;
                    
                    tr.innerHTML = `
                        <td class="ps-3"><span class="badge bg-light text-dark border">${item.fecha_pago}</span></td>
                        <td class="fw-bold text-dark">${item.participante}</td>
                        <td>${item.cedula}</td>
                        <td><span class="text-muted small">${item.diplomado}</span></td>
                        <td>
                            <span class="fw-bold text-success">${montoFormat} ${item.moneda}</span><br>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.65rem;">${item.metodo_pago}</span>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-reverse">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${error.message}</td></tr>`;
        }
    }

    // 3. FUNCIÓN: CARGAR PAGOS DE CUOTAS
    window.loadEstudiantesCuotas = async function(page = 1) {
    const search = document.getElementById('search-cuota')?.value.trim() || '';
    const tbody  = document.getElementById('cuotas-estudiantes-body');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';

    const params = new URLSearchParams({ search, page });
    const res    = await fetch(`${BASE_URL}/financial/reverse_operations/search_estudiantes_cuotas`, {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params
    });
    const data = await res.json();

    if (!data.ok || !data.data?.data || data.data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted small">No se encontraron estudiantes.</td></tr>';
        return;
    }

    const offset = (page - 1) * 25;
    tbody.innerHTML = data.data.data.map((s, i) => `
        <tr style="cursor:pointer;" class="row-estudiante-cuota" 
            data-user-id="${s.user_id}" data-nombre="${s.participante}">
            <td class="ps-3 text-muted small">${offset + i + 1}</td>
            <td class="fw-bold">${s.cedula}</td>
            <td>${s.participante}</td>
            <td class="text-end pe-3">
                <span class="badge bg-primary bg-opacity-10 text-primary border">${s.total_pagos} pago(s)</span>
            </td>
        </tr>
    `).join('');

    const info = document.getElementById('cuotas-info');
if (info) info.textContent = `${data.data.total} estudiante${data.data.total !== 1 ? 's' : ''}`;
    const paginHTML = buildPaginCuotas(data.data.page, data.data.pages);
    
    document.getElementById('cuotas-paginacion-top').innerHTML  = paginHTML;
    document.getElementById('cuotas-paginacion-bottom').innerHTML = paginHTML;

    document.getElementById('cuotas-estudiantes-area').classList.remove('d-none');
    document.getElementById('cuotas-pagos-area').classList.add('d-none');
}

async function loadPagosByUser(userId, nombre) {
    document.getElementById('cuotas-estudiantes-area').classList.add('d-none');
    document.getElementById('cuotas-pagos-area').classList.remove('d-none');
    document.getElementById('cuotas-pagos-titulo').textContent = nombre;

    const tbody = document.getElementById('cuotas-pagos-body');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';

    const params = new URLSearchParams({ user_id: userId });
    const res  = await fetch(`${BASE_URL}/financial/reverse_operations/get_cuotas_by_user`, {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params
    });
    const data = await res.json();

    if (!data.ok || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay pagos.</td></tr>';
        return;
    }

    tbody.innerHTML = data.data.map((item, i) => `
        <tr style="cursor:pointer;" class="row-cuota-clickable"
            data-payment-id="${item.payment_id}" data-nombre="${item.participante}">
            <td class="ps-3 text-muted small">${i + 1}</td>
            <td><span class="badge bg-light text-dark border">${item.fecha_pago}</span></td>
            <td class="small text-muted">${item.diplomado}</span></td>
            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size:0.7rem;">${item.metodo_pago}</span></td>
            <td class="fw-bold text-primary">${parseFloat(item.monto).toLocaleString('es-VE',{minimumFractionDigits:2})} ${item.moneda}</td>
            <td class="text-end pe-3">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-arrow-counterclockwise"></i> Revertir
                </button>
            </td>
        </tr>
    `).join('');
}

function buildPaginCuotas(page, pages) {
    if (pages <= 1) return '';
    let btns = '';
    btns += `<button class="btn btn-sm btn-light border rounded-pill px-3 me-1" ${page===1?'disabled':''} onclick="loadEstudiantesCuotas(${page-1})">‹</button>`;
    for (let p = 1; p <= pages; p++) {
        if (pages > 7 && p > 2 && p < pages-1 && Math.abs(p-page) > 1) {
            if (p===3||p===pages-2) btns += `<span class="me-1">…</span>`;
            continue;
        }
        btns += `<button class="btn btn-sm ${p===page?'btn-primary':'btn-light border'} rounded-pill px-3 me-1" onclick="loadEstudiantesCuotas(${p})">${p}</button>`;
    }
    btns += `<button class="btn btn-sm btn-light border rounded-pill px-3" ${page===pages?'disabled':''} onclick="loadEstudiantesCuotas(${page+1})">›</button>`;
    return btns;
}

    // 4. EVENTOS DE BÚSQUEDA
    const inputInscripcion = document.getElementById('search-inscripcion');
    inputInscripcion.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') loadInscripciones();
    });
    inputInscripcion.addEventListener('input', () => {
        clearTimeout(window._searchTimer);
        window._searchTimer = setTimeout(() => loadInscripciones(), 400);
    });
    document.getElementById('btn-search-inscripcion')?.addEventListener('click', () => loadInscripciones());
    document.getElementById('btn-clear-inscripcion')?.addEventListener('click', () => {
        inputInscripcion.value = '';
        loadInscripciones();
    });

    document.getElementById('search-cuota').addEventListener('keyup', (e) => { if(e.key === 'Enter') loadEstudiantesCuotas(1); });
    document.getElementById('btn-search-cuota')?.addEventListener('click', () => loadEstudiantesCuotas(1));
    document.getElementById('btn-clear-cuota')?.addEventListener('click', () => {
        document.getElementById('search-cuota').value = '';
        loadEstudiantesCuotas(1);
    });
    document.getElementById('btn-volver-estudiantes')?.addEventListener('click', () => {
        document.getElementById('cuotas-pagos-area').classList.add('d-none');
        document.getElementById('cuotas-estudiantes-area').classList.remove('d-none');
    });

    // 5. EVENTO DELEGADO: CLIC EN FILA COMPLETA
    document.addEventListener('click', (e) => {
        
        // --- TRIGGER PARA INSCRIPCIONES ---
        const rowInscripcion = e.target.closest('.row-inscripcion-clickable');
        if (rowInscripcion) {
            const pId = rowInscripcion.dataset.paymentId;
            const eId = rowInscripcion.dataset.enrollmentId;
            const nombre = rowInscripcion.dataset.nombre;

            Swal.fire({
                title: '¿Reiniciar Inscripción?',
                html: `Se eliminará el <b>Libro Mayor</b> de:<br><b class="text-danger">${nombre}</b>.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, borrar y reiniciar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeAction('/financial/reverse_operations/reverse_inscripcion', { payment_id: pId, enrollment_id: eId }, loadInscripciones);
                }
            });
            return; 
        }

        // --- TRIGGER PARA ESTUDIANTE (ir a sus pagos) ---
        const rowEstudiante = e.target.closest('.row-estudiante-cuota');
        if (rowEstudiante) {
            const userId = rowEstudiante.dataset.userId;
            const nombre = rowEstudiante.dataset.nombre;
            loadPagosByUser(userId, nombre);
            return;
        }

        // --- TRIGGER PARA CUOTAS ---
        const rowCuota = e.target.closest('.row-cuota-clickable');
        if (rowCuota) {
            const pId    = rowCuota.dataset.paymentId;
            const nombre = rowCuota.dataset.nombre;

            Swal.fire({
                title: '¿Revertir este pago?',
                html: `Se restaurará el saldo deudor de:<br><b>${nombre}</b>.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Sí, revertir pago',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeAction('/financial/reverse_operations/reverse_cuota', { payment_id: pId }, () => loadEstudiantesCuotas(1));
                }
            });
        }
        
    });

    // 6. HELPER: EJECUCIÓN ASÍNCRONA
    async function executeAction(endpoint, dataObj, callback) {
        const urlAction = (`${BASE_URL}${endpoint}`).replace(/\/+/g, '/');
        
        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const params = new URLSearchParams();
            for (const key in dataObj) params.append(key, dataObj[key]);

            const response = await fetch(urlAction, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params
            });

            const data = await response.json();

            if(data.ok) {
                Swal.fire('¡Éxito!', data.message, 'success').then(callback);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error Crítico', 'Hubo un fallo en la comunicación con el servidor.', 'error');
        }
    }

    loadInscripciones();
});