/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s4_utils.js
 * PROPÓSITO: Centralización de utilidades de parseo numérico y contexto del estudiante (S4).
 * VERSIÓN: 1.0.1 - FIX: Blindaje de extracción de datos del DOM para evitar JSON corrupto.
 */

window.StudentsUtils = {
    
    // --- LLAVES DE CONTEXTO DEL DOM ---
    getStudentId: () => {
        const val = document.getElementById('user_id_val')?.value;
        return (val && val !== "") ? val.trim() : '0';
    },

    getStudentCode: () => {
        const val = document.getElementById('student_code_hidden')?.value;
        // Si no hay código, devolvemos un string limpio para no romper el JSON
        return (val && val !== "") ? val.trim() : 'S-EXP';
    },

    getStudentName: () => {
        const val = document.getElementById('full_name_hidden')?.value;
        // Si el nombre no carga, devolvemos el valor genérico
        return (val && val !== "") ? val.trim() : 'ESTUDIANTE';
    },

    getOfferingId: () => {
        const val = document.getElementById('offering_id_val')?.value;
        return (val && val !== "") ? val.trim() : '0';
    },
    
    getAgentId: () => {
        const val = document.getElementById('user_id_hidden')?.value;
        return (val && val !== "") ? val.trim() : '0';
    },

    // --- AYUDANTES DE FECHA Y TIEMPO ---
    
    getSystemDate: () => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },
    
    getSystemDateTime: () => {
        const d = new Date();
        const time = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        return `${window.StudentsUtils.getSystemDate()} ${time}`;
    },

    /** Formatea fecha ISO a legible: DD/MM/YYYY */
    formatDate: (dateStr) => {
        if (!dateStr || dateStr.includes('0000')) return 'No indicada';
        const parts = dateStr.split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : dateStr;
    },
    
    getFormattedDateTime: () => {
        const d = new Date();
        let h = d.getHours();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12; 
        const dateStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        const timeStr = `${String(h).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')} ${ampm}`;
        return `${dateStr} ${timeStr}`;
    },

    // --- FORMATEO Y CONVERSIÓN ---

    round: (number) => {
        return Math.round((parseFloat(number) + Number.EPSILON) * 100) / 100;
    },

    formatCurrency: (e) => {
        if (!e || !e.target) return;
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") { e.target.value = ""; return; }
        const numericValue = parseFloat(value) / 100;
        e.target.value = numericValue.toLocaleString('de-DE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    },

    formatNumberToCurrency: (number) => {
        const parsed = parseFloat(number);
        if (isNaN(parsed)) return "0,00";
        return parsed.toLocaleString('de-DE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    },

    parseCurrencyToFloat: (value) => {
        if (!value) return 0.00;
        let cleanValue = value.toString().trim();
        if (!isNaN(cleanValue) && !cleanValue.includes(',') && !cleanValue.includes('.')) {
            return window.StudentsUtils.round(parseFloat(cleanValue));
        }
        cleanValue = cleanValue.replace(/\./g, "").replace(",", ".");
        const result = parseFloat(cleanValue);
        return isNaN(result) ? 0.00 : window.StudentsUtils.round(result);
    }
};