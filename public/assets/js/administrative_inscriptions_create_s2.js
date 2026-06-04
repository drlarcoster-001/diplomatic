/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s2.js
 * PROPÓSITO: Validación del Paso 2 (Datos Académicos).
 * VERSIÓN: 2.2.0 - Eliminación definitiva de lógica de borradores (Drafts).
 */

document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Inyectamos la validación del Paso 2 en el objeto global Wizard.
     * Se ejecuta automáticamente al presionar "Siguiente" desde el Paso 2.
     */
    Wizard.validators[2] = function() {
        const degreeInput = document.getElementById('undergraduate_degree');
        if (!degreeInput) return true; // Si por alguna razón no existe el input, dejamos pasar

        const degree = degreeInput.value.trim();
        
        if (!degree || degree === 'N/A' || degree === '') {
            Swal.fire({
                title: 'Información Requerida',
                text: 'Debe ingresar o verificar la carrera de pregrado del estudiante.',
                icon: 'warning',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        return true;
    };

    // La lógica de 'btnSaveDraft' ha sido eliminada por completo ya que el 
    // proceso ahora es de sesión única y no requiere persistencia intermedia.
    console.log("✅ Validador del Paso 2 cargado (Modo Sesión Única).");
});