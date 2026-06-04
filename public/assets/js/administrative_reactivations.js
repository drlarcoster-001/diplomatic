/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: public/assets/js/administrative_reactivations.js
 * PROPÓSITO: Lógica de filtrado de cohortes y procesamiento de reactivación masiva (EGRESADO/APROBADO -> ACTIVO/CURSANDO).
 * VERSIÓN: 2.2.0 - Sincronización con flujo de tarjetas, filtro reactivo y blindaje de peticiones masivas.
 */

(function() {
    "use strict";

    const state = { 
        baseUrl: window.BASE_URL || '/diplomatic/public',
        isProcessing: false
    };

    // Inicialización al cargar el DOM
    document.addEventListener('DOMContentLoaded', () => {
        
        // 1. Buscador de Cohortes (Filtro reactivo de tarjetas)
        const cohortSearch = document.getElementById('search-cohort');
        if (cohortSearch) {
            cohortSearch.addEventListener('keyup', function(e) {
                const term = e.target.value.toLowerCase();
                const cards = document.querySelectorAll('.diplomado-card');
                
                cards.forEach(card => {
                    const text = card.innerText.toLowerCase();
                    card.style.display = text.includes(term) ? 'block' : 'none';
                });
            });
        }

        // 2. Escuchador para el botón de reactivación masiva en manage.php
        const btnMassive = document.getElementById('btn-reactivate-massive');
        if (btnMassive) {
            btnMassive.addEventListener('click', function() {
                const offeringId = this.getAttribute('data-offering-id');
                if (offeringId) {
                    window.app.reactivateAll(parseInt(offeringId));
                }
            });
        }
    });

    window.app = {
        /**
         * PROCESO MAESTRO: Reactiva a todos los estudiantes de una cohorte.
         * Envía el offering_id al controlador para ejecutar la transacción ACID.
         */
        reactivateAll: async (offeringId) => {
            if (state.isProcessing) return;

            const confirm = await Swal.fire({
                title: '¿Abrir Matrícula Masiva?',
                html: `Esta acción restaurará a <b>TODOS</b> los estudiantes de esta cohorte al estatus <b>ACTIVO</b> y sus matrículas a <b>CURSANDO</b>.<br><br><small class='text-muted'>Uso exclusivo para corrección de egresos accidentales o reaperturas.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, reactivar cohorte',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });

            if (confirm.isConfirmed) {
                state.isProcessing = true;
                
                Swal.fire({
                    title: 'Procesando cambios...',
                    text: 'Sincronizando estados académicos y de usuario',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                try {
                    const response = await fetch(`${state.baseUrl}/administrative/reactivations/processMassive`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ offering_id: offeringId })
                    });

                    // Si el servidor devuelve HTML por error de ruta, esto fallará y el catch atrapará el error de parseo
                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cohorte Reactivada!',
                            text: result.message,
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            // Redireccionamos al index para refrescar los contadores de las tarjetas
                            window.location.href = `${state.baseUrl}/administrative/reactivations`;
                        });
                    } else {
                        Swal.fire('Error de Proceso', result.message, 'error');
                    }
                } catch (error) {
                    console.error("Fallo técnico:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Comunicación',
                        text: 'El servidor no respondió correctamente. Verifique que la ruta processMassive esté en el Bootstrap.'
                    });
                } finally {
                    state.isProcessing = false;
                }
            }
        }
    };
})();