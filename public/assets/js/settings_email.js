/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: public/assets/js/settings_email.js
 * Propósito: Gestión de SMTP y plantillas con vista previa técnica (mantiene etiquetas) y envío de prueba.
 */

"use strict";

// Variable global para identificar qué formulario se está previsualizando
let currentPreviewPrefix = '';

/**
 * Guarda la configuración del panel activo
 */
window.saveActiveSettings = async function () {
    const activePane = document.querySelector(".tab-pane.active");
    const form = activePane.querySelector("form");
    const path = form.getAttribute("data-basepath");

    Swal.fire({ title: "Guardando...", didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(`${path}/settings/save-correo`, {
            method: "POST",
            body: new FormData(form),
        });
        const data = await res.json();
        Swal.fire(data.ok ? "Éxito" : "Error", data.msg, data.ok ? "success" : "error");
    } catch (e) {
        Swal.fire("Error", "Fallo de conexión.", "error");
    }
};

/**
 * Abre el Modal XL para la vista previa del correo.
 * Se mantiene el contenido íntegro (etiquetas incluidas) para revisión técnica.
 */
window.previewEmailPopup = function (prefix) {
    const form = document.getElementById(`form-${prefix}`);
    const asunto = form.querySelector('input[name="asunto"]').value;
    const contenido = form.querySelector('textarea[name="contenido"]').value;

    if (!asunto || !contenido) {
        Swal.fire("Atención", "Complete el asunto y el contenido.", "warning");
        return;
    }

    currentPreviewPrefix = prefix;

    // Inyectamos los datos originales (sin reemplazar etiquetas) en el Modal XL
    document.getElementById('preview-subject-text').innerText = `Asunto: ${asunto}`;
    document.getElementById('mail-render-area').innerHTML = contenido;

    // Mostramos el modal desactivando el 'focus' forzado de Bootstrap para permitir el uso del teclado en popups
    const modalEl = document.getElementById('modalPreviewEmail');
    const myModal = new bootstrap.Modal(modalEl, {
        focus: false 
    });
    myModal.show();
};

/**
 * Lógica del botón "Enviar Prueba" ubicado dentro del modal
 */
window.sendTestFromPreview = async function() {
    if (!currentPreviewPrefix) return;

    const { value: email } = await Swal.fire({
        title: 'Enviar Prueba',
        input: 'email',
        inputPlaceholder: 'tu@correo.com',
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d6efd',
        allowOutsideClick: false,
        didOpen: () => {
            const input = Swal.getInput();
            if (input) {
                input.focus();
            }
        }
    });

    if (email) {
        executeTest(currentPreviewPrefix, "template", email);
    }
};

/**
 * Maximiza o restaura el tamaño del modal
 */
window.toggleFullscreenPreview = function() {
    const dialog = document.getElementById('previewDialog');
    if (dialog.classList.contains('modal-xl')) {
        dialog.classList.remove('modal-xl', 'modal-dialog-centered');
        dialog.classList.add('modal-fullscreen');
    } else {
        dialog.classList.add('modal-xl', 'modal-dialog-centered');
        dialog.classList.remove('modal-fullscreen');
    }
};

/**
 * Ejecuta pruebas de envío (Conexión SMTP o Plantilla)
 */
async function executeTest(prefix, mode, emailTarget = "") {
    const form = document.getElementById(`form-${prefix}`);
    const path = form.getAttribute("data-basepath");
    const formData = new FormData(form);
    formData.append("mode", mode);
    formData.append("email_test", emailTarget);

    if (mode === 'connection' && !emailTarget) {
        const { value: email } = await Swal.fire({ 
            title: "Enviar Prueba", 
            input: "email",
            didOpen: () => {
                const input = Swal.getInput();
                if (input) input.focus();
            }
        });
        if (!email) return;
        formData.set("email_test", email);
    }

    Swal.fire({ title: "Enviando...", didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(`${path}/settings/test-correo`, { method: "POST", body: formData });
        const data = await res.json();
        Swal.fire(data.ok ? "Éxito" : "Error", data.msg, data.ok ? "success" : "error");
    } catch (e) {
        Swal.fire("Error", "Fallo técnico.", "error");
    }
}

window.insertTag = function (btn, tag) {
    const textarea = btn.closest(".card-body").querySelector("textarea");
    textarea.value += tag;
    textarea.focus();
};

window.togglePassword = function (btn) {
    const input = btn.closest(".input-group").querySelector("input");
    const icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
};