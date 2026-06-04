/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions.js
 * PROPÓSITO: Renderizado de popup detallado con desglose de planes de pago e inicio de flujo de inscripción.
 * VERSIÓN: 1.2.6 - Limpieza profunda de caracteres invisibles (NBSP) y compatibilidad absoluta de sintaxis.
 */

/**
 * Formatea fechas evitando el desfase de zona horaria (UTC fix).
 */
function formatearFecha(f) {
    if (!f || f === '0000-00-00') return 'N/A';
    var date = new Date(f + 'T00:00:00'); 
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/**
 * Muestra el SweetAlert con todos los detalles de la oferta académica.
 */
window.verDetallesOferta = function(o) {
    var baseUrl = window.location.origin + '/diplomatic/public';
    var uploadsPath = baseUrl + '/assets/uploads/profesores/'; 
    var defaultAvatar = baseUrl + '/assets/img/avatars/default.png';

    var costoFormatted = parseFloat(o.total_cost || 0).toLocaleString('es-ES', { minimumFractionDigits: 2 });
    var moneda = o.currency_code || 'USD';

    // 1. Construcción de Tabla: Modalidad de Pago
    var tablaPagos = '<table class="table table-sm table-bordered mt-2 small">';
    tablaPagos += '<thead class="table-light"><tr><th>Descripción</th><th>Monto</th><th>Vencimiento</th></tr></thead>';
    tablaPagos += '<tbody>';
    
    if (o.payment_plans && o.payment_plans.length > 0) {
        o.payment_plans.forEach(function(p) {
            tablaPagos += '<tr>' +
                '<td>' + p.name + '</td>' +
                '<td class="fw-bold">' + parseFloat(p.amount).toFixed(2) + ' ' + moneda + '</td>' +
                '<td>' + formatearFecha(p.due_date) + '</td>' +
            '</tr>';
        });
    } else {
        tablaPagos += '<tr><td colspan="3" class="text-center text-muted italic">No hay planes de pago definidos para esta oferta</td></tr>';
    }
    tablaPagos += '</tbody></table>';

    // 2. Construcción de Tabla: Profesores
    var tablaProfs = '<table class="table table-sm table-borderless align-middle mt-2 small"><tbody>';
    if (o.professors_list && o.professors_list.length > 0) {
        o.professors_list.forEach(function(p) {
            var finalImg = p.photo_path ? uploadsPath + p.photo_path.split('/').pop() : defaultAvatar;
            tablaProfs += '<tr>' +
                '<td style="width:40px">' +
                    '<img src="' + finalImg + '" class="rounded-circle border" width="35" height="35" style="object-fit:cover" onerror="this.src=\'' + defaultAvatar + '\'">' +
                '</td>' +
                '<td class="fw-bold text-dark">' + p.first_name + ' ' + p.last_name + '</td>' +
            '</tr>';
        });
    } else {
        tablaProfs += '<tr><td class="text-muted italic small p-2">No hay profesores asignados públicamente</td></tr>';
    }
    tablaProfs += '</tbody></table>';

    // 3. Unión de Sedes y Grupos
    var sedesStr = (Array.isArray(o.sedes_list)) ? o.sedes_list.join(', ') : 'N/A';
    var gruposStr = (Array.isArray(o.grupos_list)) ? o.grupos_list.join(', ') : 'N/A';
    
    // CIRUGÍA LÁSER: Preparamos el string del horario
    var horarioStr = o.grupos_descripciones ? '<div class="mb-2 small text-dark" style="line-height: 1.3;"><strong>Horario:</strong> ' + o.grupos_descripciones.replace(/\n/g, '<br>') + '</div>' : '';

    // 4. Renderizado del Popup
    Swal.fire({
        title: '<div class="text-primary fw-bold text-uppercase">' + o.diplomado_name + '</div>' +
               '<div class="small text-muted fs-6 fw-normal">' + o.cohort_name + '</div>',
        html: 
            '<div class="text-start px-2" style="max-height: 480px; overflow-y: auto; overflow-x: hidden;">' +

            
                '<div class="mb-2 small"><strong>Modalidad:</strong> ' + o.general_modality + '</div>' +
                '<div class="mb-2 small"><strong>Sedes:</strong> ' + sedesStr + '</div>' +
                '<div class="mb-2 small"><strong>Grupos:</strong> ' + gruposStr + '</div>' +
                horarioStr + // <--- AQUÍ SE IMPRIME EL HORARIO
                '<div class="mb-2 small"><strong>Costo Total:</strong> <span class="text-success fw-bold">' + costoFormatted + ' ' + moneda + '</span></div>' +
                
                '<div class="mt-3 py-1 bg-light px-2 rounded fw-bold small text-uppercase" style="font-size:0.75rem">Modalidad de Pago:</div>' +
                tablaPagos +
                
                '<div class="mt-2 small"><strong>Cupos Disponibles:</strong> <span class="badge bg-info text-dark">' + o.available_seats + '</span></div>' +
                
                '<div class="mt-3 py-1 bg-light px-2 rounded fw-bold small text-uppercase" style="font-size:0.75rem">Profesores del Diplomado:</div>' +
                tablaProfs +
            '</div>',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Inscribir',
        cancelButtonText: 'Cerrar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-primary rounded-pill px-4 fw-bold shadow-sm ms-2',
            cancelButton: 'btn btn-secondary rounded-pill px-4 fw-bold shadow-sm'
        },
        width: '580px'
    }).then(function(result) {
        if (result.isConfirmed) {
            iniciarInscripcion(o.offering_id, o.diplomado_name);
        }
    });
};

/**
 * Función de confirmación previa a la redirección al Wizard.
 */
window.iniciarInscripcion = function(id, diplomadoName) {
    var nombreParaMostrar = "el estudiante";
    
    if (typeof NOMBRE_ESTUDIANTE !== 'undefined' && NOMBRE_ESTUDIANTE) {
        nombreParaMostrar = NOMBRE_ESTUDIANTE;
    }

    Swal.fire({
        title: '¿Quiere continuar?',
        html: 'Se procederá a inscribir a <b>' + nombreParaMostrar + '</b> al diplomado <b>"' + diplomadoName + '"</b>.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'No, cancelar',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-success rounded-pill px-5 fw-bold shadow-sm me-2',
            cancelButton: 'btn btn-danger rounded-pill px-5 fw-bold shadow-sm'
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = window.location.origin + '/diplomatic/public/students/inscriptions/create?id=' + id;
        }
    });
};