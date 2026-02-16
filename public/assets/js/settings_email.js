/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: public/assets/js/settings_email.js
 * Propósito: Gestión de SMTP y plantillas con lógica de guardado único (No incremental).
 */

"use strict";

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

window.previewEmailPopup = function (prefix) {
    const form = document.getElementById(`form-${prefix}`);
    const asunto = form.querySelector('input[name="asunto"]').value;
    const contenido = form.querySelector('textarea[name="contenido"]').value;

    if (!asunto || !contenido) {
        Swal.fire("Atención", "Complete el asunto y el contenido.", "warning");
        return;
    }

    // El POPUP de Vista Previa solicitado
    Swal.fire({
        title: 'Vista Previa del Mensaje',
        html: `
            <div style="text-align:left; border:1px solid #ddd; padding:15px; background:#fff; max-height:350px; overflow-y:auto;">
                <p><strong>Asunto:</strong> ${asunto}</p>
                <hr>
                <div>${contenido}</div>
            </div>
            <p class="mt-3 small">¿Deseas enviar una prueba real a tu correo?</p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar prueba',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        width: '650px'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const { value: email } = await Swal.fire({
                title: 'Email de destino',
                input: 'email',
                inputPlaceholder: 'tu@correo.com'
            });
            if (email) executeTest(prefix, "template", email);
        }
    });
};

async function executeTest(prefix, mode, emailTarget = "") {
    const form = document.getElementById(`form-${prefix}`);
    const path = form.getAttribute("data-basepath");
    const formData = new FormData(form);
    formData.append("mode", mode);
    formData.append("email_test", emailTarget);

    if (mode === 'connection' && !emailTarget) {
        const { value: email } = await Swal.fire({ title: "Email de destino", input: "email" });
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
};

window.togglePassword = function (btn) {
    const input = btn.closest(".input-group").querySelector("input");
    input.type = input.type === "password" ? "text" : "password";
};