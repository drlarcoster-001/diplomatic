/**
 * MÓDULO: PANEL
 * Archivo: public/assets/js/panel.js
 * Propósito: Toggle sidebar + burger + overlay (móvil/tablet).
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    const burger  = document.getElementById("dpBurger");
    const sidebar = document.getElementById("dpSidebar");
    const overlay = document.getElementById("dpOverlay");

    if (!burger || !sidebar || !overlay) return;

    function openMenu(){
      sidebar.classList.add("is-open");
      overlay.classList.add("is-open");
      burger.classList.add("is-open");
    }

    function closeMenu(){
      sidebar.classList.remove("is-open");
      overlay.classList.remove("is-open");
      burger.classList.remove("is-open");
    }

    burger.addEventListener("click", function(){
      sidebar.classList.contains("is-open") ? closeMenu() : openMenu();
    });

    overlay.addEventListener("click", closeMenu);

    window.addEventListener("resize", function(){
      if (window.innerWidth >= 992) closeMenu();
    });
  });
})();
