/**
 * MÓDULO: CONFIGURACIÓN / JS
 * ARCHIVO: public/assets/js/settings_wordpress.js
 * PROPÓSITO: Lógica de validación AJAX para el Bridge de WordPress y Sincronización de Profesores.
 * VERSIÓN: 1.5.0 - Integración completa con tabla de profesores y blindaje anti-HTML.
 */

document.addEventListener('DOMContentLoaded', function() {
    "use strict";

    const btnTest = document.getElementById('btnTestConn');
    const btnTestPush = document.getElementById('btnTestPush');
    const form = document.getElementById('formWpConfig');
    
    // Uso de BASE_URL dinámica si existe, o la ruta hardcodeada como fallback
    const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '/diplomatic/public';

    // =========================================================
    // 1. EVENTO: PROBAR CONEXIÓN BÁSICA (HANDSHAKE)
    // =========================================================
    if (btnTest) {
        btnTest.addEventListener('click', function() {
            const wpUrl = document.getElementById('wp_url').value.trim();
            const wpUser = document.getElementById('wp_user').value.trim();
            const wpPass = document.getElementById('wp_pass').value.trim();

            if (!wpUrl || !wpUser || !wpPass) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, ingrese la URL, Usuario y Token para probar la conexión.'
                });
                return;
            }

            btnTest.disabled = true;
            btnTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Probando...';

            const formData = new FormData(form);

            fetch(`${baseUrl}/settings/wordpress/test`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async response => {
                // BLINDAJE ANTI-HTML (Unexpected token <)
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error("El servidor no devolvió JSON válido. Posible error 500 o sesión expirada.");
                }
            })
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Conexión Exitosa!',
                        text: data.message,
                        confirmButtonColor: '#0d6efd'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Fallo de Autenticación',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'warning',
                    title: 'Error de Servidor',
                    text: error.message
                });
            })
            .finally(() => {
                btnTest.disabled = false;
                btnTest.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Probar Conexión';
            });
        });
    }

    // =========================================================
    // 2. EVENTO: GUARDAR CONFIGURACIÓN
    // =========================================================
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = form.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method,
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error("Respuesta inválida del servidor.");
                }
            })
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error al Guardar', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', error.message, 'error');
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
            });
        });
    }

    // =========================================================
    // 3. EVENTO: TEST PUSH (Prueba de inyección de contenido)
    // =========================================================
    if (btnTestPush) {
        btnTestPush.addEventListener('click', function() {
            Swal.fire({
                title: 'Ejecutando Test Push...',
                text: 'Enviando post de prueba a WordPress',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`${baseUrl}/settings/wordpress/test-push`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.ok) {
                    Swal.fire('¡Éxito!', `Post creado en WP. ID: ${data.post_id}`, 'success');
                } else {
                    Swal.fire('Fallo', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Fallo de red al intentar Test Push.', 'error');
            });
        });
    }

    // =========================================================
    // 4. EVENTO: SINCRONIZAR PROFESOR INDIVIDUAL
    // =========================================================
    document.querySelectorAll('.btn-sync-prof').forEach(btn => {
        btn.addEventListener('click', function() {
            const profId = this.getAttribute('data-id');
            
            Swal.fire({
                title: '¿Sincronizar Profesor?',
                text: "Se actualizará o creará el perfil en WordPress.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, sincronizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    Swal.fire({
                        title: 'Sincronizando...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData();
                    formData.append('id', profId);

                    fetch(`${baseUrl}/settings/wordpress/sync-prof`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.ok) {
                            Swal.fire({
                                title: '¡Sincronizado!',
                                text: `Profesor publicado en WP (ID: ${data.post_id})`,
                                icon: 'success'
                            }).then(() => {
                                window.location.reload(); // Recarga para actualizar la tabla
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error de Red', 'No se pudo contactar al servidor.', 'error');
                    });
                }
            });
        });
    });

});