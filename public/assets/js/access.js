/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: public/assets/js/access.js
 * Propósito: UX del login (validación y mensajes UI) con SweetAlert2 y control del sidebar (hamburguesa).
 * Nota: No ejecuta seguridad avanzada; solo mejora interacción del formulario y la UI del menú.
 */

(function () {
  "use strict";

  // Verificar si el archivo JS está cargado correctamente
  console.log("Archivo JS cargado correctamente.");

  // Funcionalidad para el formulario de login
  const form = document.querySelector('form[action$="/login"]');
  if (form) {
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
  }

  // FUNCIONALIDAD DEL BURGER (sidebar)
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar-wrapper');

  // Verificar si el menú toggle existe
  if (menuToggle) {
    console.log('Botón hamburguesa encontrado'); // Debug: Verificar si el botón existe

    menuToggle.addEventListener('click', function () {
      console.log('Hamburguesa clickeada'); // Debug: Verificar si el clic es capturado
      sidebar.classList.toggle('open'); // Cambia visibilidad del sidebar
    });
  } else {
    console.log('Botón hamburguesa NO encontrado'); // Debug: Verificar si el botón existe
  }
})();
