/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / SUSPENSIONES
 * ARCHIVO: assets/js/administrative_suspension.js
 * PROPÓSITO: Lógica de Ficha (Popup) con notificaciones secuenciales independientes.
 * VERSIÓN: 3.1.0 - UX: Modal persistente. Eliminación de "Cancelar" tras el cambio de estatus.
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. CONFIGURACIÓN INICIAL Y RUTAS
    const basePath = window.location.pathname.split('/administrative')[0];
    
    // Referencia al modal de Bootstrap (Ficha del estudiante)
    const fichaModalElement = document.getElementById('modalFichaEstudiante');
    const fichaModal = fichaModalElement ? new bootstrap.Modal(fichaModalElement) : null;

    // 2. LÓGICA DE FILTROS EN TIEMPO REAL (NOMBRE Y CÉDULA)
    const filterNombre = document.getElementById('filterNombre');
    const filterCedula = document.getElementById('filterCedula');

    if (filterNombre && filterCedula) {
        [filterNombre, filterCedula].forEach(el => {
            el.addEventListener('keyup', () => {
                const valNombre = filterNombre.value.toLowerCase();
                const valCedula = filterCedula.value.toLowerCase();
                document.querySelectorAll('.student-row').forEach(row => {
                    const nombre = row.querySelector('.student-name').innerText.toLowerCase();
                    const cedula = row.querySelector('.student-cedula').innerText.toLowerCase();
                    row.style.display = (nombre.includes(valNombre) && cedula.includes(valCedula)) ? "" : "none";
                });
            });
        });
    }

    window.limpiarFiltros = function() {
        filterNombre.value = "";
        filterCedula.value = "";
        document.querySelectorAll('.student-row').forEach(row => row.style.display = "");
    };

/**
     * FUNCIÓN GLOBAL: ABRIR FICHA (POPUP)
     * Versión 4.0 - Incluye cálculo de saldo pendiente (Resta) y diseño de columnas.
     */
    window.abrirFichaEstudiante = function(s, nro) {
        if (!fichaModal) return;

        // Reset del Footer básico
        const modalFooter = document.querySelector('#modalFichaEstudiante .modal-footer');
        modalFooter.innerHTML = `
            <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i> Cancelar
            </button>
            <div id="fAccionContainer"></div>
        `;

        // Datos básicos
        document.getElementById('fNro').innerText = nro;
        const expBadge = document.getElementById('fExp');
        expBadge.innerText = s.expediente || 'S/C';
        document.getElementById('fNombre').innerText = s.participante;
        
        // Estatus financiero con colores
        const solvBadge = document.getElementById('fSolvencia');
        const statusClass = {
            'INSOLVENTE': 'bg-danger',
            'POR_VENCER': 'bg-warning',
            'SOLVENTE': 'bg-success'
        }[s.estatus_financiero] || 'bg-secondary';
        
        solvBadge.innerText = s.estatus_financiero;
        solvBadge.className = `badge rounded-pill px-3 py-2 fs-6 ${statusClass}`;
        
        // --- CÁLCULO DE DEUDA Y DISEÑO DINÁMICO ---
        const totalCuota = parseFloat(s.monto_total_cuota || 0);
        const yaPagado = parseFloat(s.monto_ya_pagado || 0);
        const restaPendiente = (totalCuota - yaPagado).toFixed(2);
        const textoDeudaBase = s.detalle_deuda || 'Sin deudas pendientes registradas.';

        // Inyectamos el diseño de 3 columnas en el campo fDeuda
        document.getElementById('fDeuda').innerHTML = `
            <div class="mb-2"><i class="bi bi-calendar-event me-1"></i> ${textoDeudaBase}</div>
            <div class="row g-0 text-center border-top pt-2 mt-1">
                <div class="col-4">
                    <small class="text-muted d-block" style="font-size: 10px;">DEUDA TOTAL</small>
                    <span class="fw-bold text-dark">$${totalCuota.toFixed(2)}</span>
                </div>
                <div class="col-4 border-start border-end">
                    <small class="text-muted d-block" style="font-size: 10px;">ABONADO</small>
                    <span class="text-success fw-bold">$${yaPagado.toFixed(2)}</span>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block" style="font-size: 10px;">RESTA</small>
                    <span class="text-danger fw-bold fs-5">$${restaPendiente}</span>
                </div>
            </div>
        `;

        // Lógica del botón de acción
        const isSuspended = (s.user_status === 'SUSPENDED');
        const pName = s.participante.replace(/'/g, "\\'");
        
        // El motivo que viajará a las notificaciones incluirá el saldo exacto
        const pDeudaFull = `${textoDeudaBase} - Saldo pendiente: $${restaPendiente}`.replace(/'/g, "\\'");

        document.getElementById('fAccionContainer').innerHTML = `
            <button class="btn ${isSuspended ? 'btn-success' : 'btn-danger'} px-4 shadow-sm fw-bold" 
                    onclick="ejecutarCambioEstatus(${s.user_id}, '${isSuspended ? 'ACTIVE' : 'SUSPENDED'}', '${pName}', '${s.phone}', '${s.email}', '${pDeudaFull}')">
                <i class="bi ${isSuspended ? 'bi-play-fill' : 'bi-stop-fill'} me-1"></i>
                ${isSuspended ? 'Reactivar Estudiante' : 'Suspender Estudiante'}
            </button>
        `;

        fichaModal.show();
    };
    

    /**
     * 4. EJECUCIÓN DEL CAMBIO DE ESTATUS (LÓGICA PERSISTENTE)
     */
    window.ejecutarCambioEstatus = function(userId, targetStatus, name, phone, email, deuda) {
        const accionTexto = (targetStatus === 'SUSPENDED') ? 'SUSPENDER' : 'REACTIVAR';
        
        Swal.fire({
            title: `¿Confirmar ${accionTexto}?`,
            text: `Se procederá a actualizar el estatus de ${name} en el sistema.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: (targetStatus === 'SUSPENDED') ? '#d33' : '#198754',
            confirmButtonText: 'Sí, proceder',
            cancelButtonText: 'No, regresar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('status', targetStatus);

                fetch(`${basePath}/administrative/suspensions/toggleStatus`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // TRANSFORMACIÓN: Quitamos botón Cancelar y ponemos controles de notificación + Finalizar
                        renderizarControlesNotificacion(userId, targetStatus, name, phone, deuda);
                        
                        Swal.fire({
                            title: '¡Estatus Actualizado!',
                            text: 'Cambio aplicado. Ahora puede enviar las notificaciones correspondientes.',
                            icon: 'success',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error de Red', 'No se pudo comunicar con el servidor.', 'error');
                });
            }
        });
    };

    /**
     * 5. RENDERIZADO DE CONTROLES POST-ACCIÓN
     * Elimina el botón "Cancelar" y deja solo opciones de notificación y el cierre definitivo.
     */
    function renderizarControlesNotificacion(userId, status, name, phone, deuda) {
        const modalFooter = document.querySelector('#modalFichaEstudiante .modal-footer');
        const pName = name.replace(/'/g, "\\'");
        const pDeuda = deuda.replace(/'/g, "\\'");

        // Limpiamos todo el footer del modal para evitar que cancelen un proceso ya guardado en DB
        modalFooter.innerHTML = `
            <div class="d-flex justify-content-between w-100 align-items-center">
                <div class="d-flex gap-2">
                    <button class="btn btn-success fw-bold px-3" onclick="dispararWhatsApp('${pName}', '${phone}', '${status}', '${pDeuda}')">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </button>
                    <button class="btn btn-primary fw-bold px-3" onclick="dispararCorreoSMTP(${userId}, '${status}', '${pDeuda}')">
                        <i class="bi bi-envelope"></i> Correo
                    </button>
                </div>
                <button class="btn btn-dark px-5 fw-bold shadow-sm" onclick="location.reload()">
                    <i class="bi bi-check-all me-1"></i> FINALIZAR
                </button>
            </div>
        `;
    }

    /**
     * 6. GENERADOR DE MENSAJES CORPORATIVOS (WA)
     */
    function obtenerMensajeWhatsApp(name, status, deuda) {
        if (status === 'SUSPENDED') {
            return `*NOTIFICACIÓN ADMINISTRATIVA* ⚠️\n\n` +
                   `Estimado(a) *${name}*,\n\n` +
                   `Le informamos que su acceso a nuestra *Plataforma Educativa* ha sido restringido temporalmente por compromisos administrativos pendientes:\n\n` +
                   `📌 *Motivo:* ${deuda}\n\n` +
                   `Para restablecer su estatus y continuar con sus estudios, le agradecemos regularizar su situación a la brevedad. Se ha enviado un correo formal con el detalle.\n\n` +
                   `Atentamente,\n*Administración Académica*`;
        } else {
            return `*NOTIFICACIÓN DE ACTIVACIÓN* ✅\n\n` +
                   `Estimado(a) *${name}*,\n\n` +
                   `¡Es un placer saludarle! Le informamos que su cuenta ha sido *REACTIVADA* exitosamente.\n\n` +
                   `🚀 *Ya puede ingresar al sistema* con sus credenciales habituales para continuar con su formación académica.\n\n` +
                   `Agradecemos su confianza y le deseamos éxito.\n\n` +
                   `Atentamente,\n*Coordinación Académica*`;
        }
    }

    /**
     * 7. DISPARADORES INDEPENDIENTES
     */
    window.dispararWhatsApp = function(name, phone, status, deuda) {
        const cleanPhone = phone.replace(/\D/g, '');
        const mensaje = obtenerMensajeWhatsApp(name, status, deuda);
        window.open(`https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(mensaje)}`, '_blank');
        // El modal no se cierra para permitir enviar correo luego
    };

    window.dispararCorreoSMTP = function(userId, status, deuda) {
        Swal.fire({
            title: 'Enviando Correo...',
            text: 'Procesando notificación profesional por SMTP, por favor espere.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('status', status);
        formData.append('deuda', deuda);

        fetch(`${basePath}/administrative/suspensions/sendEmail`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.status === 'success') {
                Swal.fire('¡Éxito!', 'La notificación por correo ha sido enviada.', 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire('Error de Red', 'No se pudo procesar el envío del correo.', 'error');
        });
    };
});