/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: public/assets/js/access.js
 * Propósito: UX del login (validación y mensajes UI) con SweetAlert2.
 * Nota: No ejecuta seguridad avanzada; solo mejora interacción del formulario.
 */

(function () {
  "use strict";

  const form = document.querySelector('form[action$="/login"]');
  if (!form) return;

  form.addEventListener("submit", function (e) {
    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');

    const emailVal = (email?.value || "").trim();
    const passVal = (password?.value || "").trim();

    // Validación mínima front
    if (!emailVal || !passVal) {
      e.preventDefault();

      Swal.fire({
        icon: "warning",
        title: "Datos incompletos",
        text: "Ingrese correo y contraseña para continuar.",
        confirmButtonText: "Entendido",
      });

      return;
    }

    // Indicador visual (sin bloquear el back)
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = 'Validando...';
    }
  });
})();
