<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VISTAS
 * ARCHIVO: app/views/financial/payment_registration/registry_s2.php
 * PROPÓSITO: Selección del diplomado/cohorte asociado al estudiante (Paso 2).
 * VERSIÓN: 1.2.0 - Validación estructural y confirmación de enlace de ID con el orquestador JS.
 */
?>
<div id="step2" class="wizard-step d-none animate__animated animate__fadeIn">
    <div class="text-center mb-5">
        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-2 text-uppercase fw-bold smallest">Programas Académicos</div>
        <h4 class="fw-bold text-dark">Selección de Diplomado</h4>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            Seleccione el programa al cual se le imputará el pago reportado.
        </p>
    </div>

    <div class="row g-4 justify-content-center" id="offeringsContainer">
    </div>

    <div class="mt-5 p-3 bg-light rounded-4 border border-dashed text-center">
        <p class="small text-muted mb-0">
            <i class="bi bi-info-circle-fill me-1 text-primary"></i>
            Asegúrese de seleccionar la cohorte correcta para evitar discrepancias contables en el Ledger.
        </p>
    </div>
</div>