/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / DOCUMENTOS RECHAZADOS
 * ARCHIVO: public/assets/js/administrative_rejected.js
 * PROPÓSITO: Manejo de búsqueda multicriterio en grid y reversión de estatus vía AJAX.
 * VERSIÓN: 1.3.5 - Soporte para Formulario de Filtro y lógica condicionada por método de pago.
 */

$(document).ready(function() {
    "use strict";
    let currentId = null;

    // 1. Lógica del Filtro de Búsqueda
    $('#filter-form-docs').on('submit', function(e) {
        e.preventDefault();
        const searchText = $('#search-text').val().toLowerCase().trim();
        
        $("#rejected-table tbody tr.item-row").each(function() {
            const rowContent = $(this).find('.search-field-name').text() + ' ' + 
                               $(this).find('.search-field-id').text() + ' ' + 
                               $(this).find('.search-field-diploma').text();
            
            $(this).toggle(rowContent.toLowerCase().indexOf(searchText) > -1);
        });
    });

    // 2. Limpiar Filtros
    $('#btn-clear-filters').on('click', function() {
        $('#search-text').val('');
        $("#rejected-table tbody tr.item-row").show();
    });

    // 3. Abrir Popup de Detalle (Clic en Fila)
    $('.clickable-row').on('click', function() {
        currentId = $(this).data('id');
        const name = $(this).data('name');
        const cedula = $(this).data('cedula');
        const diplomado = $(this).data('diplomado');
        const payment = $(this).data('payment');
        const obs = $(this).data('obs');

        $('#modal-body-content').html(`
            <div class="mb-3">
                <label class="small text-muted d-block text-uppercase fw-bold">Participante</label>
                <div class="fw-bold fs-5 text-dark">${name} <span class="small text-muted">(${cedula})</span></div>
            </div>
            <div class="mb-3">
                <label class="small text-muted d-block text-uppercase fw-bold">Programa</label>
                <div class="text-secondary fw-bold">${diplomado}</div>
            </div>
            <div class="mb-3">
                <label class="small text-muted d-block text-uppercase fw-bold">Método de Pago</label>
                <span class="badge bg-dark px-3">${payment}</span>
            </div>
            <div class="p-3 bg-danger bg-opacity-10 border-start border-danger border-4 rounded-end">
                <label class="small text-danger d-block text-uppercase fw-bold mb-1">Motivo del Rechazo</label>
                <div class="text-dark italic small">"${obs}"</div>
            </div>
        `);

        new bootstrap.Modal(document.getElementById('modalDetail')).show();
    });

    // 4. Botón CAMBIAR ESTATUS (AJAX)
    $('#btn-change-status').on('click', function() {
        Swal.fire({
            title: '¿Confirmar Reversión?',
            text: "El expediente volverá a REVISIÓN (Digital) o COMPROMISO (Cash) automáticamente.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, Cambiar Estatus',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${window.location.origin}/diplomatic/public/administrative/rejected/changeStatus`,
                    method: 'POST',
                    data: { id: currentId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.ok) {
                            Swal.fire('Éxito', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});