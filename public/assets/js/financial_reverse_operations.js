/**
 * MÓDULO: GESTIÓN FINANCIERA / REVERSO DE OPERACIONES
 * ARCHIVO: public/assets/js/financial_reverse_operations.js
 * PROPÓSITO: Manejo de interfaz dual, carga de bandejas y ejecución de reverso mediante clic en fila.
 * VERSIÓN: 3.2.0 - UX Fix: Disparo de modal por fila completa (Row-Click) y optimización de delegación.
 */

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
                loadCuotas();
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
    async function loadCuotas() {
        const tbody = document.querySelector('#resultsCuotas');
        const searchInput = document.getElementById('search-cuota').value.trim();
        
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Buscando pagos...</p></td></tr>';

        const urlSearch = (`${BASE_URL}/financial/reverse_operations/search_cuotas`).replace(/\/+/g, '/');

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
            let data = JSON.parse(responseText);

            if(data.ok) {
                if(data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted small"><i class="bi bi-info-circle d-block fs-3 mb-2"></i> No se encontraron pagos.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.data.forEach(item => {
                    const montoFormat = parseFloat(item.monto).toLocaleString('es-VE', {minimumFractionDigits: 2});
                    const tr = document.createElement('tr');
                    tr.className = 'row-cuota-clickable'; // Gatillo para el clic
                    tr.dataset.paymentId = item.payment_id;
                    tr.dataset.nombre = item.participante;
                    
                    tr.innerHTML = `
                        <td class="ps-3"><span class="badge bg-light text-dark border">${item.fecha_pago}</span></td>
                        <td class="fw-bold">${item.participante}</td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.75rem;">CUOTA / PAGO GENERAL</span></td>
                        <td><span class="badge bg-light text-dark border" style="font-size: 0.65rem;">${item.metodo_pago}</span></td>
                        <td><span class="fw-bold text-primary">${montoFormat} ${item.moneda}</span></td>
                        <td class="text-end pe-3">
                            <button class="btn btn-reverse">
                                <i class="bi bi-arrow-clockwise"></i>
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

    // 4. EVENTOS DE BÚSQUEDA
    document.getElementById('search-inscripcion').addEventListener('keyup', (e) => { if(e.key === 'Enter') loadInscripciones(); });
    document.getElementById('search-cuota').addEventListener('keyup', (e) => { if(e.key === 'Enter') loadCuotas(); });

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

        // --- TRIGGER PARA CUOTAS ---
        const rowCuota = e.target.closest('.row-cuota-clickable');
        if (rowCuota) {
            const pId = rowCuota.dataset.paymentId;
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
                    executeAction('/financial/reverse_operations/reverse_cuota', { payment_id: pId }, loadCuotas);
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