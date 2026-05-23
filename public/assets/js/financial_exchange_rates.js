/**
 * MÓDULO: FINANCIERO / TASA DE CAMBIO
 * ARCHIVO: public/assets/js/financial_exchange_rates.js
 * PROPÓSITO: Scraper BCV, Popup de Detalle y Eliminación Física con Auditoría.
 * VERSIÓN: 2.6.0 - Implementación de Detalle de Fila y Borrado Físico.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("JS de Tasas de Cambio: Inicializado con funciones de Grid.");

    // --- 1. RELOJ EN TIEMPO REAL ---
    const clockElement = document.getElementById('real-time-clock');
    if (clockElement) {
        setInterval(() => {
            clockElement.textContent = new Date().toLocaleTimeString('en-US', { hour12: true });
        }, 1000);
    }

    // --- 2. LÓGICA DE CONSULTA (SCRAPER) ---
    const btnConsultar = document.getElementById('btnConsultarBCV');
    if (btnConsultar) {
        btnConsultar.onclick = async function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Consultando BCV...',
                html: '<div class="spinner-border text-primary mb-2"></div><p class="text-muted small">Conectando con el portal oficial...</p>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('exchange_rates/fetchBCV'); 
                const data = await response.json();
                if (data.status === 'error' || !data.dolar) throw new Error(data.message);
                
                lanzarPopupConfirmacion(data.dolar, data.euro, data.fecha);
            } catch (error) {
                Swal.fire('Error de Conexión', error.message, 'error');
            }
        };
    }

    // --- 3. DETALLE DE REGISTRO (CLIC EN FILA) ---
    const rateRows = document.querySelectorAll('.rate-row-clickable');
    rateRows.forEach(row => {
        row.addEventListener('click', function() {
            const d = this.dataset; 

            Swal.fire({
                title: '<span class="h4 fw-bold text-primary">Información de Tasa</span>',
                html: `
                    <div class="text-start py-2 px-3">
                        <div class="d-flex justify-content-between mb-2">
                            <b class="text-muted">Fecha:</b> <span>${d.date}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <b class="text-muted">Hora:</b> <span>${d.time || '--:--'}</span>
                        </div>
                        <hr class="my-2">
                        <div class="alert alert-primary d-flex justify-content-between align-items-center mb-2 border-0 shadow-sm">
                            <small class="fw-bold">DOLAR BCV:</small>
                            <span class="h5 mb-0 fw-bold">${d.usd} Bs.</span>
                        </div>
                        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0 border-0 shadow-sm">
                            <small class="fw-bold">EURO BCV:</small>
                            <span class="h5 mb-0 fw-bold">${d.eur || '0,00'} Bs.</span>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Volver',
                confirmButtonColor: '#6c757d',
                customClass: { confirmButton: 'rounded-pill px-5 fw-bold' }
            });
        });
    });

    // --- 4. ELIMINACIÓN FÍSICA ---
    const deleteButtons = document.querySelectorAll('.btn-delete-rate');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // event.stopPropagation() ya viene desde el HTML inline para no activar el popup de detalle
            const id = this.dataset.id;
            const dia = this.dataset.day;

            Swal.fire({
                title: '¿Desea Eliminar?',
                text: `Desea Eliminar las tasas correspondiente a este dia (${dia})?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'rounded-pill px-4 fw-bold',
                    cancelButton: 'rounded-pill px-4 fw-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarEliminacion(id);
                }
            });
        });
    });

    // --- FUNCIONES AUXILIARES (GUARDAR / ELIMINAR) ---

    function lanzarPopupConfirmacion(usd, eur, fecha) {
        const fmt = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2 });
        Swal.fire({
            title: '<span class="h4 fw-bold">Confirmar Registro BCV</span>',
            html: `<div class="py-2">
                <div class="alert alert-primary d-flex justify-content-between align-items-center mb-3 py-3 border-0 shadow-sm">
                    <b class="small">USD:</b> <span class="h3 mb-0 fw-bold">${fmt.format(usd)} Bs.</span>
                </div>
                <div class="alert alert-warning d-flex justify-content-between align-items-center py-3 border-0 shadow-sm">
                    <b class="small">EUR:</b> <span class="h3 mb-0 fw-bold">${fmt.format(eur || 0)} Bs.</span>
                </div>
                <p class="text-muted small mt-3">Fecha: <b>${fecha}</b>. ¿Registrar tasa oficial?</p>
            </div>`,
            showCancelButton: true,
            confirmButtonText: 'Guardar y Auditar',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#0d6efd',
            reverseButtons: true,
            customClass: { confirmButton: 'rounded-pill px-4 fw-bold', cancelButton: 'rounded-pill px-4 fw-bold' }
        }).then((result) => {
            if (result.isConfirmed) ejecutarGuardado(usd, eur || 0);
        });
    }

    async function ejecutarGuardado(usd, eur) {
        Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });
        try {
            const fd = new FormData();
            fd.append('dolar_bcv', usd);
            fd.append('euro_bcv', eur || 0);

            const response = await fetch('exchange_rates/store', { method: 'POST', body: fd });
            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({ icon: 'success', title: '¡Tasa Guardada!', timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else { throw new Error(result.message); }
        } catch (error) { Swal.fire('Error', error.message, 'error'); }
    }

    async function ejecutarEliminacion(id) {
        Swal.fire({ title: 'Eliminando registro...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData();
            fd.append('id', id);

            const response = await fetch('exchange_rates/delete', {
                method: 'POST',
                body: fd
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: 'El registro ha sido removido y auditado.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(result.message || "No se pudo eliminar el registro.");
            }
        } catch (error) {
            Swal.fire('Error de Sistema', error.message, 'error');
        }
    }

    // Registro Manual: Captura datos, valida y envía con feedback visual
    // Registro Manual: Diseño corregido y alineado
    const btnManual = document.getElementById('btnManualRegister');
    if (btnManual) {
        btnManual.onclick = function() {
            Swal.fire({
                title: 'Registro Manual',
                html: `
                    <div class="text-start px-2">
                        <div class="mb-3">
                            <label class="d-block small fw-bold text-muted mb-1">FECHA</label>
                            <input type="date" id="manual_date" class="swal2-input m-0 w-100" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="mb-3">
                            <label class="d-block small fw-bold text-muted mb-1">DÓLAR (BS.)</label>
                            <input type="number" id="manual_usd" class="swal2-input m-0 w-100" placeholder="Ej: 500.46" step="0.01">
                        </div>
                        <div class="mb-0">
                            <label class="d-block small fw-bold text-muted mb-1">EURO (BS.)</label>
                            <input type="number" id="manual_eur" class="swal2-input m-0 w-100" placeholder="Ej: 589.27" step="0.01">
                        </div>
                    </div>
                `,
                confirmButtonText: 'Guardar Tasa',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
                reverseButtons: true,
                customClass: {
                    input: 'custom-swal-input'
                },
                preConfirm: () => {
                    const fecha = document.getElementById('manual_date').value;
                    const usd = document.getElementById('manual_usd').value;
                    const eur = document.getElementById('manual_eur').value;

                    if (!fecha || !usd || usd <= 0) {
                        Swal.showValidationMessage('La fecha y la tasa del dólar son obligatorias');
                        return false;
                    }
                    return { fecha, usd, eur: eur || 0 };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarManual(result.value.usd, result.value.eur, result.value.fecha);
                }
            });
        };
    }


    async function enviarManual(usd, eur, fecha) {
        // Feedback visual para que sepas que está procesando
        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const fd = new FormData();
        fd.append('dolar_bcv', usd);
        fd.append('euro_bcv', eur);
        fd.append('rate_date', fecha);

        try {
            const response = await fetch('exchange_rates/store', { method: 'POST', body: fd });
            const res = await response.json();
            
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Tasa Registrada', timer: 1000, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else {
                Swal.fire('Error', res.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error de Sistema', 'No hay conexión con el servidor', 'error');
        }
    }
});