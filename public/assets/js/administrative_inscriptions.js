/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions.js
 * PROPÓSITO: Manejo de eventos para el catálogo de ofertas y filtrado dinámico.
 * VERSIÓN: 1.2.0 - Sincronizado con Grid de 3 columnas y clases v1.3.0.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. REFERENCIAS DE UI ---
    const searchInput = document.getElementById('searchOffering'); 
    const offeringsGrid = document.getElementById('offeringsGrid');
    // Buscamos las tarjetas solo dentro del grid de ofertas
    const offeringCards = offeringsGrid ? offeringsGrid.querySelectorAll('.offering-card') : [];

    console.log("Catálogo Administrativo: Iniciando filtrado para " + offeringCards.length + " ofertas.");

    /**
     * FILTRADO DINÁMICO DE TARJETAS
     * Filtra por nombre de diplomado o nombre de cohorte.
     */
    if (searchInput && offeringCards.length > 0) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            offeringCards.forEach(card => {
                // SINCRO: Clases actualizadas en index.php v1.3.0
                const title = card.querySelector('.card-title-main')?.textContent.toLowerCase() || '';
                const cohort = card.querySelector('.text-primary-emphasis')?.textContent.toLowerCase() || '';
                
                // Obtenemos la columna (col-lg-4) para no romper el layout de Bootstrap
                const colContainer = card.closest('.col-12, .col-md-6, .col-lg-4');

                if (title.includes(searchTerm) || cohort.includes(searchTerm)) {
                    if (colContainer) colContainer.classList.remove('d-none');
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                    visibleCount++;
                } else {
                    if (colContainer) colContainer.classList.add('d-none');
                    card.style.opacity = '0';
                }
            });

            handleNoResults(visibleCount);
        });
    }

    /**
     * GESTIÓN DE ESTADO VACÍO
     * Muestra un mensaje si el filtro no coincide con nada.
     */
    function handleNoResults(count) {
        let noResultsMsg = document.getElementById('noFilterResults');
        
        if (count === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'noFilterResults';
                noResultsMsg.className = 'col-12 text-center py-5';
                noResultsMsg.innerHTML = `
                    <div class="p-5 bg-white rounded-4 border-2 border-dashed shadow-sm">
                        <i class="bi bi-search display-3 text-muted opacity-25"></i>
                        <p class="mt-3 text-secondary fw-bold">No hay diplomados que coincidan con "${searchInput.value}"</p>
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="location.reload()">Ver todo el catálogo</button>
                    </div>`;
                offeringsGrid.appendChild(noResultsMsg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }

    /**
     * FEEDBACK VISUAL DE SELECCIÓN
     * Añade un pequeño efecto de profundidad al hacer clic antes de la redirección.
     */
    offeringCards.forEach(card => {
        card.addEventListener('mousedown', function() {
            this.style.transform = "scale(0.97)";
            this.style.transition = "transform 0.1s ease";
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = "scale(1)";
        });
    });
});