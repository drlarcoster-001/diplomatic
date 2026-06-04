<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / VISTAS
 * ARCHIVO: app/views/students/payment_registration/registry_s2.php
 * PROPÓSITO: Selección del diplomado/cohorte asociado al estudiante (Paso 2).
 * VERSIÓN: 1.0.1 - FIX: Mejora visual de selección (contorno en vez de relleno) y fuentes para móvil.
 */
?>
<style>
    /* 1. Letras más grandes para el nombre del programa en móviles */
    #offeringsContainer h5,
    #offeringsContainer .card-title,
    #offeringsContainer .program-title {
        font-size: 1.3rem !important; 
        font-weight: 800 !important;
        line-height: 1.2;
        color: #212529 !important;
    }

    /* 2. Contorno elegante para la selección, forzando la anulación del fondo azul */
    #offeringsContainer .card {
        border: 2px solid #e9ecef !important;
        transition: all 0.2s ease-in-out;
    }

    /* Cuando la tarjeta está seleccionada (sobrescribe bg-primary si el JS lo inyecta) */
    #offeringsContainer .card.bg-primary,
    #offeringsContainer .card.border-primary,
    #offeringsContainer .card.active-selection {
        background-color: #f8faff !important; /* Un fondo súper clarito casi blanco */
        border: 3px solid #0d6efd !important; /* El contorno azul grueso */
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15) !important;
        transform: translateY(-3px);
    }

    /* Forzamos que el texto vuelva a ser oscuro aunque el JS le haya puesto .text-white */
    #offeringsContainer .card.bg-primary *,
    #offeringsContainer .card.active-selection * {
        color: #212529 !important;
    }
    
    #offeringsContainer .card.bg-primary .text-muted,
    #offeringsContainer .card.bg-primary .text-white-50 {
        color: #6c757d !important;
    }
</style>

<div id="step2" class="wizard-step d-none animate__animated animate__fadeIn">
    <div class="text-center mb-5">
        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-2 text-uppercase fw-bold smallest">Mis Programas Inscritos</div>
        <h4 class="fw-bold text-dark">¿A qué programa deseas abonar?</h4>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            Selecciona el diplomado o curso al cual deseas reportar tu pago para actualizar tu estado de cuenta.
        </p>
    </div>

    <div class="row g-4 justify-content-center" id="offeringsContainer">
        </div>

    <div class="mt-5 p-3 bg-light rounded-4 border border-dashed text-center">
        <p class="small text-muted mb-0">
            <i class="bi bi-info-circle-fill me-1 text-primary"></i>
            Si no visualizas un programa en el que estás inscrito, por favor contacta a soporte técnico o control de estudios.
        </p>
    </div>
</div>