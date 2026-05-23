/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s4_utils.js
 * PROPÓSITO: Centralización de utilidades de parseo numérico, contexto del estudiante y manejo de cronometría.
 * VERSIÓN: 4.7.0 - REGLA: Precisión obligatoria de 2 decimales y blindaje anti-NaN.
 */

window.FinancialUtils = {
    
    // --- LLAVES DE CONTEXTO DEL DOM ---
    getStudentId: () => document.getElementById('user_id_val')?.value?.trim() || '0',
    getStudentCode: () => document.getElementById('student_code_hidden')?.value?.trim() || 'SIN-EXPEDIENTE',
    getStudentName: () => document.getElementById('full_name_hidden')?.value?.trim() || 'Estudiante',
    getOfferingId: () => document.getElementById('offering_id_val')?.value?.trim() || '0',
    getAgentId: () => document.getElementById('user_id_hidden')?.value?.trim() || '1',

    // --- AYUDANTES DE FECHA Y TIEMPO ---
    
    /** Retorna fecha en formato ISO: YYYY-MM-DD */
    getSystemDate: () => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },
    
    /** Retorna fecha y hora para auditoría: YYYY-MM-DD HH:MM:SS */
    getSystemDateTime: () => {
        const d = new Date();
        const time = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        return `${window.FinancialUtils.getSystemDate()} ${time}`;
    },
    
    /** Retorna fecha legible: DD/MM/YYYY HH:MM AM/PM */
    getFormattedDateTime: () => {
        const d = new Date();
        let h = d.getHours();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12; 
        h = h ? h : 12; // Si es 0, convertir a 12
        const dateStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        const timeStr = `${String(h).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')} ${ampm}`;
        return `${dateStr} ${timeStr}`;
    },

    // --- FORMATEO, CONVERSIÓN Y PRECISIÓN ---

    /**
     * Redondea matemáticamente a 2 decimales para evitar errores de precisión de punto flotante.
     */
    round: (number) => {
        return Math.round((parseFloat(number) + Number.EPSILON) * 100) / 100;
    },

    /**
     * Formatea el input en tiempo real (Mascara de moneda).
     * Formato: 1.250,50
     */
    formatCurrency: (e) => {
        if (!e || !e.target) return;
        
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") {
            e.target.value = "";
            return;
        }
        
        // Convertir a número con 2 decimales dividiendo entre 100
        const numericValue = parseFloat(value) / 100;
        
        e.target.value = numericValue.toLocaleString('de-DE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    },

    /**
     * Convierte float/number a String formateado ("1.250,50").
     * Protege contra valores nulos o NaN.
     */
    formatNumberToCurrency: (number) => {
        const parsed = parseFloat(number);
        if (isNaN(parsed)) return "0,00";
        
        return parsed.toLocaleString('de-DE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    },

    /**
     * Convierte String ("1.250,50") a Float puro (1250.50).
     * Esencial para cálculos antes de enviar a la base de datos.
     */
    parseCurrencyToFloat: (value) => {
        if (value === null || value === undefined || value === "") return 0.00;
        
        let cleanValue = value.toString().trim();
        
        // Si ya es un número, solo asegurar los 2 decimales
        if (!isNaN(cleanValue) && !cleanValue.includes(',') && !cleanValue.includes('.')) {
            return window.FinancialUtils.round(parseFloat(cleanValue));
        }

        // Eliminar puntos de miles y cambiar coma decimal por punto
        cleanValue = cleanValue.replace(/\./g, "").replace(",", ".");
        
        const result = parseFloat(cleanValue);
        return isNaN(result) ? 0.00 : window.FinancialUtils.round(result);
    }
};