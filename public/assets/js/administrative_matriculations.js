/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: public/assets/js/administrative_matriculations.js
 * PROPÓSITO: Gestión de búsqueda, procesamiento masivo de actas y cambios de estatus sincronizados.
 * VERSIÓN: 1.2.0 - Final: Integración total con SweetAlert2 y validación estricta de flujo masivo.
 */

$(document).ready(function () {
    "use strict";

    // Detectamos la URL base desde el objeto window (inyectado en la vista) o fallback
    const URL_BASE = window.BASE_URL || '/diplomatic/public';

    /**
     * 1. FILTRADO DINÁMICO DE COHORTES (INDEX)
     */
    $('#search-cohort').on('keyup', function () {
        const searchTerm = $(this).val().toLowerCase();

        $('.cohort-card-horizontal').each(function () {
            const cardText = $(this).text().toLowerCase();
            $(this).toggle(cardText.indexOf(searchTerm) > -1);
        });
    });

    /**
     * 2. LIMPIAR FILTROS
     */
    $('#btn-clear-cohort-filters').on('click', function () {
        $('#search-cohort').val('').trigger('keyup');
    });

    /**
     * 3. PROCESAMIENTO MASIVO DE NOTAS (VISTA MANAGE)
     * Recolecta las notas de los alumnos activos y cierra el acta.
     */
    $('#btn-procesar-acta').on('click', function (e) {
        e.preventDefault();
        
        const notasData = {};
        let valid = true;
        let count = 0;

        // Recorremos solo los inputs que no estén deshabilitados (los que están CURSANDO)
        $('.input-final-grade').each(function () {
            if (!$(this).prop('disabled')) {
                // Capturamos el ID de la matrícula (data-id)
                const matriculaId = $(this).data('id'); 
                const notaValue = $(this).val();

                // Validación de rango académico (0-20) y campos vacíos
                if (notaValue === "" || parseFloat(notaValue) < 0 || parseFloat(notaValue) > 20) {
                    valid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                    notasData[matriculaId] = notaValue;
                    count++;
                }
            }
        });

        // Verificamos si hay registros para procesar
        if (count === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Nada que procesar',
                text: 'No se encontraron nuevas calificaciones cargadas o todos los alumnos ya tienen acta cerrada.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        if (!valid) {
            Swal.fire({
                icon: 'warning',
                title: 'Notas Inválidas',
                text: 'Por favor, asigne notas válidas entre 0 y 20 a todos los alumnos seleccionados.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // Confirmación Institucional con SweetAlert2
        Swal.fire({
            title: '¿Confirmar Cierre de Acta?',
            html: `Se procesarán <b>${count}</b> alumnos.<br><br><small class="text-muted">Al confirmar, las notas serán registradas y los alumnos pasarán a estatus <b>EGRESADO</b> de forma irreversible.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, procesar acta',
            cancelButtonText: 'Revisar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${URL_BASE}/administrative/matriculations/procesarNotas`,
                    method: 'POST',
                    data: { notas: notasData },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Procesando Acta...',
                            text: 'Actualizando historial académico y estatus de egreso.',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                confirmButtonColor: '#198754'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo completar el proceso.', 'error');
                        }
                    },
                    error: function (xhr) {
                        console.error("Error en Acta:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Servidor',
                            text: 'No se pudo comunicar con el controlador. Verifique su conexión o reporte al soporte técnico.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    });

    /**
     * 4. CAMBIO DE ESTADO INDIVIDUAL (CONGELAR / RETIRAR)
     * Maneja las excepciones individuales desde la tabla.
     */
    $('.btn-change-status').on('click', function () {
        const mid = $(this).data('mid');
        const sid = $(this).data('sid');
        const estado = $(this).data('status'); 

        const config = {
            'RETIRADO': { color: '#dc3545', label: 'RETIRAR', icon: 'warning' },
            'CONGELADO': { color: '#ffc107', label: 'CONGELAR', icon: 'info' }
        };

        Swal.fire({
            title: `¿Desea ${config[estado].label} al alumno?`,
            text: `Esta acción se sincronizará con la ficha maestra del estudiante y su estatus pasará a ${estado}.`,
            icon: config[estado].icon,
            showCancelButton: true,
            confirmButtonColor: config[estado].color,
            confirmButtonText: `Sí, confirmar ${estado}`,
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${URL_BASE}/administrative/matriculations/cambiarEstado`,
                    method: 'POST',
                    data: {
                        matricula_id: mid,
                        student_id: sid,
                        estado: estado
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sincronizado',
                                text: `Estatus cambiado a ${estado} correctamente.`,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo actualizar el registro.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Problema al conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });
});