/**
 * MÓDULO: GESTIÓN FINANCIERA / RECHAZOS DE PAGO
 * ARCHIVO: public/assets/js/financial_payment_rejection.js
 * PROPÓSITO: Lógica frontend, peticiones AJAX dinámicas y manejo de alertas de seguridad (SweetAlert2) para eliminación física.
 * VERSIÓN: 1.2.0 - Fix: Parseo avanzado de JSON payment_metadata para extraer montos precisos (USD/BS), tasa y referencia. Soporte íntegro de /diplomatic/public/.
 */

window.rejectionsIns = {};
window.rejectionsReg = {};

$(document).ready(function() {
    loadInscripciones();
    loadRegulares();

    let timerIns, timerReg;
    $('#searchInscripciones').on('keyup', function() {
        clearTimeout(timerIns);
        let val = $(this).val();
        timerIns = setTimeout(() => loadInscripciones(val), 400);
    });

    $('#searchRegulares').on('keyup', function() {
        clearTimeout(timerReg);
        let val = $(this).val();
        timerReg = setTimeout(() => loadRegulares(val), 400);
    });
});

/* ================== FUNCIONES AUXILIARES ================== */

/**
 * Extrae información del JSON de auditoría (payment_metadata) 
 * SIN modificar el registro original. Solo para visualización.
 */
function extractPaymentData(row) {
    let mBs = '-';
    let mUsd = '-';
    let tasa = 'N/A';
    let referencia = 'N/A';
    let motivo = row.rejection_reason || 'Sin motivo especificado';

    // 1. Si es CASH (Efectivo), tomamos el monto directo de la fila
    if (row.metodo_pago === 'CASH') {
        mUsd = row.monto ? parseFloat(row.monto).toLocaleString('en-US', {minimumFractionDigits: 2}) : '-';
        referencia = 'EFECTIVO (VENTANILLA)';
        mBs = '-';
    } 
    // 2. Si es PAGOMOVIL o similar, leemos la estructura de tu JSON
    else if (row.payment_metadata) {
        try {
            const meta = JSON.parse(row.payment_metadata);

            // Monto en Dólares del sistema
            if (meta.monto_sistema_usd) {
                mUsd = parseFloat(meta.monto_sistema_usd).toLocaleString('en-US', {minimumFractionDigits: 2});
            }

            // Tasa de cambio registrada
            if (meta.tasa_cambio) {
                tasa = parseFloat(meta.tasa_cambio).toLocaleString('es-VE', {minimumFractionDigits: 2}) + ' BS';
            }

            // Datos específicos de la transferencia
            if (meta.detalles_transaccion) {
                referencia = meta.detalles_transaccion.referencia || 'N/A';
                
                // Monto nativo en Bolívares
                if (meta.detalles_transaccion.monto_nativo) {
                    mBs = parseFloat(meta.detalles_transaccion.monto_nativo).toLocaleString('es-VE', {minimumFractionDigits: 2});
                }
            }
        } catch (e) {
            console.warn("Aviso: El metadata de este registro no es un JSON estándar o está corrupto.");
        }
    }

    // Fallback: Si después de todo mUsd sigue vacío, usamos el valor de la tabla
    if (mUsd === '-' && row.monto) {
        mUsd = parseFloat(row.monto).toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    return { mBs, mUsd, tasa, referencia, motivo };
}


/* ================== CARGA DE TABLAS ================== */
function loadInscripciones(search = '') {
    const $tbody = $('#resultsInscripciones');
    $tbody.html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-dark"></div></td></tr>');
    
    $.post(BASE_URL + '/financial/payment_rejections/search_inscripciones', { search }, function(res) {
        if (res.ok) {
            let html = '';
            window.rejectionsIns = {}; 

            if (res.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center text-muted">No hay inscripciones rechazadas.</td></tr>';
            } else {
                res.data.forEach(r => {
                    window.rejectionsIns[r.payment_id] = r; 
                    let ext = extractPaymentData(r);

                    html += `
                    <tr class="clickable-row" onclick="showDetailsIns(${r.payment_id})">
                        <td class="small">${r.fecha_pago}</td>
                        <td class="small fw-bold">${r.cedula}</td>
                        <td class="small">${r.participante}</td>
                        <td class="small text-truncate" style="max-width:200px;" title="${r.diplomado}">${r.diplomado}</td>
                        <td class="small text-end fw-bold text-dark">${ext.mBs}</td>
                        <td class="small text-end fw-bold text-success">${ext.mUsd}</td>
                        <td class="small text-center"><span class="badge bg-secondary">${r.metodo_pago}</span></td>
                        <td class="text-center"><i class="bi bi-chevron-right text-muted"></i></td>
                    </tr>`;
                });
            }
            $tbody.html(html);
        } else {
            $tbody.html(`<tr><td colspan="8" class="text-center text-danger">${res.message}</td></tr>`);
        }
    }).fail(err => {
        const msg = err.responseJSON ? err.responseJSON.message : "Error de red.";
        $tbody.html(`<tr><td colspan="8" class="text-center text-danger">${msg}</td></tr>`);
    });
}

function loadRegulares(search = '') {
    const $tbody = $('#resultsRegulares');
    $tbody.html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm text-dark"></div></td></tr>');
    
    $.post(BASE_URL + '/financial/payment_rejections/search_regulares', { search }, function(res) {
        if (res.ok) {
            let html = '';
            window.rejectionsReg = {}; 

            if (res.data.length === 0) {
                html = '<tr><td colspan="8" class="text-center text-muted">No hay pagos regulares rechazados.</td></tr>';
            } else {
                res.data.forEach(r => {
                    window.rejectionsReg[r.payment_id] = r;
                    let ext = extractPaymentData(r);

                    html += `
                    <tr class="clickable-row" onclick="showDetailsReg(${r.payment_id})">
                        <td class="small">${r.fecha_pago}</td>
                        <td class="small fw-bold">${r.expediente}</td>
                        <td class="small">${r.participante}</td>
                        <td class="small text-truncate" style="max-width:200px;" title="${r.diplomado}">${r.diplomado}</td>
                        <td class="small text-end fw-bold text-dark">${ext.mBs}</td>
                        <td class="small text-end fw-bold text-success">${ext.mUsd}</td>
                        <td class="small text-center"><span class="badge bg-secondary">${r.metodo_pago}</span></td>
                        <td class="text-center"><i class="bi bi-chevron-right text-muted"></i></td>
                    </tr>`;
                });
            }
            $tbody.html(html);
        }
    }).fail(err => {
        const msg = err.responseJSON ? err.responseJSON.message : "Error de red.";
        $tbody.html(`<tr><td colspan="8" class="text-center text-danger">${msg}</td></tr>`);
    });
}

/* ================== MODALES DE DETALLE ================== */

function showDetailsIns(pId) {
    let r = window.rejectionsIns[pId];
    let ext = extractPaymentData(r);

    Swal.fire({
        title: 'Detalle de Inscripción Rechazada',
        html: `
            <div class="text-start px-3 bg-light p-3 rounded border">
                <p class="mb-2"><b>Fecha:</b> <span class="text-muted">${r.fecha_pago}</span></p>
                <p class="mb-2"><b>Cédula:</b> ${r.cedula}</p>
                <p class="mb-2"><b>Aspirante:</b> ${r.participante}</p>
                <p class="mb-2"><b>Oferta:</b> ${r.diplomado}</p>
                <hr class="my-2">
                <p class="mb-2"><b>Método:</b> <span class="badge bg-secondary">${r.metodo_pago}</span></p>
                <p class="mb-2"><b>Referencia:</b> <span class="text-primary fw-bold">${ext.referencia}</span></p>
                <p class="mb-2"><b>Tasa Aplicada:</b> ${ext.tasa}</p>
                <div class="d-flex justify-content-between mt-3 px-2 py-2 bg-white border rounded">
                    <div><b>BS:</b> <span class="text-dark fw-bold">${ext.mBs}</span></div>
                    <div><b>USD:</b> <span class="text-success fw-bold">${ext.mUsd}</span></div>
                </div>
            </div>
            <p class="mt-3 small text-muted">Seleccione la acción correctiva a ejecutar:</p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="bi bi-arrow-repeat"></i> Incorporar',
        denyButtonText: '<i class="bi bi-trash"></i> Eliminar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#198754',
        denyButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        width: '34em'
    }).then((result) => {
        if (result.isConfirmed) incorporarIns(r.payment_id, r.enrollment_id);
        else if (result.isDenied) eliminarIns(r.payment_id, r.enrollment_id);
    });
}

function showDetailsReg(pId) {
    let r = window.rejectionsReg[pId];
    let ext = extractPaymentData(r);

    Swal.fire({
        title: 'Detalle de Pago Regular Rechazado',
        html: `
            <div class="text-start px-3 bg-light p-3 rounded border">
                <p class="mb-2"><b>Fecha:</b> <span class="text-muted">${r.fecha_pago}</span></p>
                <p class="mb-2"><b>Expediente:</b> ${r.expediente}</p>
                <p class="mb-2"><b>Estudiante:</b> ${r.participante}</p>
                <p class="mb-2"><b>Oferta:</b> ${r.diplomado}</p>
                <hr class="my-2">
                <p class="mb-2"><b>Método:</b> <span class="badge bg-secondary">${r.metodo_pago}</span></p>
                <p class="mb-2"><b>Referencia:</b> <span class="text-primary fw-bold">${ext.referencia}</span></p>
                <p class="mb-2"><b>Tasa Aplicada:</b> ${ext.tasa}</p>
                <div class="d-flex justify-content-between mt-3 px-2 py-2 bg-white border rounded">
                    <div><b>BS:</b> <span class="text-dark fw-bold">${ext.mBs}</span></div>
                    <div><b>USD:</b> <span class="text-success fw-bold">${ext.mUsd}</span></div>
                </div>
            </div>
            <p class="mt-3 small text-muted">Seleccione la acción correctiva a ejecutar:</p>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="bi bi-arrow-repeat"></i> Incorporar',
        denyButtonText: '<i class="bi bi-trash"></i> Eliminar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#198754',
        denyButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        width: '34em'
    }).then((result) => {
        if (result.isConfirmed) incorporarReg(r.payment_id);
        else if (result.isDenied) eliminarReg(r.payment_id);
    });
}

/* ================== EJECUCIÓN DE ACCIONES ================== */

function incorporarIns(pId, eId) {
    Swal.fire({
        title: '¿Confirmar Incorporación?',
        text: "El pago volverá a PENDIENTE para revisión administrativa.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, reactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/financial/payment_rejections/incorporar_inscripcion', { payment_id: pId, enrollment_id: eId }, function(res) {
                if(res.ok) {
                    Swal.fire('¡Incorporado!', res.message, 'success');
                    loadInscripciones();
                } else Swal.fire('Error', res.message, 'error');
            });
        }
    });
}

function eliminarIns(pId, eId) {
    Swal.fire({
        title: 'Eliminación Física Definitiva',
        html: "Se borrará la inscripción y el soporte.<br><br>Escribe <b>ELIMINAR</b> para confirmar.",
        input: 'text',
        inputAttributes: { autocapitalize: 'off' },
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Eliminar Registro',
        preConfirm: (text) => {
            if (text !== 'ELIMINAR') Swal.showValidationMessage('La palabra clave de seguridad no coincide.');
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/financial/payment_rejections/eliminar_inscripcion', { payment_id: pId, enrollment_id: eId }, function(res) {
                if(res.ok) {
                    Swal.fire('¡Eliminado!', res.message, 'success');
                    loadInscripciones();
                } else Swal.fire('Error', res.message, 'error');
            });
        }
    });
}

function incorporarReg(pId) {
    Swal.fire({
        title: '¿Confirmar Incorporación?',
        text: "El pago regresará a taquilla para su validación.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, reactivar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/financial/payment_rejections/incorporar_regular', { payment_id: pId }, function(res) {
                if(res.ok) {
                    Swal.fire('¡Reactivado!', res.message, 'success');
                    loadRegulares();
                } else Swal.fire('Error', res.message, 'error');
            });
        }
    });
}

function eliminarReg(pId) {
    Swal.fire({
        title: 'Eliminar Pago Regular',
        html: "Se borrará físicamente este registro de la base de datos.<br><br>Escribe <b>ELIMINAR</b> para confirmar.",
        input: 'text',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Eliminar Registro',
        preConfirm: (text) => {
            if (text !== 'ELIMINAR') Swal.showValidationMessage('La palabra clave de seguridad no coincide.');
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/financial/payment_rejections/eliminar_regular', { payment_id: pId }, function(res) {
                if(res.ok) {
                    Swal.fire('¡Eliminado!', res.message, 'success');
                    loadRegulares();
                } else Swal.fire('Error', res.message, 'error');
            });
        }
    });
}