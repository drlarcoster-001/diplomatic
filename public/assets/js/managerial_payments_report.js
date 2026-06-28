/**
 * MÓDULO: PANEL GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: public/assets/js/managerial_payments_report.js
 * PROPÓSITO: Lógica de matriz segmentada y generación de Libro Excel Multipestaña.
 * VERSIÓN: 9.5.5 
 * LOGIC: Exportación de Excel con encabezados desplazados (Fila 5), formato regional de coma y observaciones de validación.
 * FIX: Sincronización con Modelo 9.5.5 (Data agrupada y alertas por estudiante).
 */

(function() {
    "use strict";

    // --- 1. CONFIGURACIÓN Y ESTADO ---
    const RECORDS_PER_PAGE = 25;
    let currentPage = 1;
    let lastSummaryData = null; 

    // --- 2. SELECTORES DE ELEMENTOS UI ---
    const form = document.getElementById('form-financial-filters');
    const smartSearch = document.getElementById('dynamic-student-search');
    const resultsArea = document.getElementById('report-results-container');
    const emptyState = document.getElementById('empty-state');
    const tbody = document.getElementById('matrix-tbody');
    
    const lblTotalAprobado = document.getElementById('lbl-total-aprobado');
    const lblTotalCompromiso = document.getElementById('lbl-total-compromiso');
    const lblTotalGeneral = document.getElementById('lbl-total-general');
    const paginationControls = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');
    
    const btnExportExcel = document.getElementById('btn-export-excel');
    const btnExportPdf = document.getElementById('btn-export-pdf');

    // --- 3. BUSCADOR INTELIGENTE (Debounce 700ms) ---
    if (smartSearch) {
        let debounceTimer;
        smartSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const value = this.value.trim();
            debounceTimer = setTimeout(() => {
                if (value.length >= 2 || value.length === 0) {
                    loadReport(1);
                }
            }, 700);
        });
    }

    // --- 4. CONTROL DE REINICIO ---
    form.onreset = () => {
        resultsArea.classList.add('d-none');
        emptyState.classList.remove('d-none');
        tbody.innerHTML = '';
        lastSummaryData = null;
        paginationControls.innerHTML = '';
        paginationInfo.innerText = '';
        lblTotalAprobado.innerText = "$ 0,00";
        lblTotalCompromiso.innerText = "$ 0,00";
        lblTotalGeneral.innerText = "$ 0,00";
    };

    // --- 5. PROCESAMIENTO DEL REPORTE (AJAX) ---
    form.onsubmit = async (e) => {
        e.preventDefault();
        loadReport(1);
    };

    async function loadReport(page) {
        currentPage = page;
        try {
            const formData = new URLSearchParams(new FormData(form));
            formData.append('page', page);

            const response = await fetch(`${BASE_URL}/ManagerialPaymentsReport/getReportData?${formData.toString()}`);
            const result = await response.json();

            if (result.ok) {
                lastSummaryData = result.summary; 
                
                if (result.data.length > 0) {
                    renderMatrix(result.data);
                    updateFinancialIndicators(result.summary);
                    renderPaginationControls(result.summary.total_records, result.summary.pages, page);
                    
                    emptyState.classList.add('d-none');
                    resultsArea.classList.remove('d-none');
                } else {
                    resultsArea.classList.add('d-none');
                    emptyState.classList.remove('d-none');
                }
            }
        } catch (error) {
            console.error("Error en carga de datos:", error);
        }
    }

    function renderMatrix(data) {
        tbody.innerHTML = data.map(r => {
            const getStatusClass = (status) => {
                switch(status) {
                    case 'PAGADO': return 'text-success-helium fw-bold';
                    case 'ABONADO': return 'text-orange fw-bold';
                    case 'SIN MOVIMIENTO': return 'text-danger-helium';
                    default: return 'text-muted opacity-50';
                }
            };
            return `
                <tr>
                    <td class="ps-4 fw-bold text-dark">${r.participante}</td>
                    <td class="text-center text-muted small">${r.cedula}</td>
                    <td class="text-center text-muted small">${r.diplomado}</td>
                    
                    <td class="text-center text-primary fw-bold" style="font-size: 0.8rem;">${r.grupos_nombres || 'N/A'}</td>
                    
                    <td class="text-center ${getStatusClass(r.estatus_i)}">${fMoney(r.pago_inscripcion)}</td>
                    <td class="text-center ${getStatusClass(r.estatus_1)}">${fMoney(r.pago_cuota_1)}</td>
                    <td class="text-center ${getStatusClass(r.estatus_2)}">${fMoney(r.pago_cuota_2)}</td>
                    <td class="text-center ${getStatusClass(r.estatus_3)}">${fMoney(r.pago_cuota_3)}</td>
                    <td class="text-center ${getStatusClass(r.estatus_4)}">${fMoney(r.pago_cuota_4)}</td>
                    <td class="text-center ${getStatusClass(r.estatus_5)}">${fMoney(r.pago_cuota_5)}</td>
                    <td class="text-end pe-4 bg-light fw-bold">${fMoney(r.total_abonado)}</td>
                    <td class="text-center small text-orange" style="line-height:1.2;">${r.observacion || '-'}</td>
                    
                    </tr>
            `;
        }).join('');
    }

    function renderPaginationControls(totalRecords, totalPages, activePage) {
        paginationControls.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.type = "button";
            btn.className = `pagination-btn ${i === activePage ? 'active' : ''}`;
            btn.innerText = i;
            btn.onclick = () => loadReport(i);
            paginationControls.appendChild(btn);
        }
        paginationInfo.innerText = `Página ${activePage} de ${totalPages} (${totalRecords} estudiantes)`;
    }


    function updateFinancialIndicators(summary) {
        // CIRUGÍA: Cambiamos .innerText por .innerHTML
        lblTotalAprobado.innerHTML = fMoney(summary.total_aprobado);
        lblTotalCompromiso.innerHTML = fMoney(summary.total_compromiso);
        lblTotalGeneral.innerHTML = fMoney(summary.total_general);
    }

    // Helper: Formateo con COMA decimal para la UI Web
    function fMoney(v) {
        const val = parseFloat(v);
        return val > 0 
            ? `$ ${val.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` 
            // CIRUGÍA: Agregamos el símbolo $ para que los ceros también se vean con moneda
            : `<span class="text-muted opacity-25">$ 0,00</span>`; 
    }

    // Helper: Formateo de número con coma para EXCEL
    function fExcel(v) {
        const val = parseFloat(v) || 0;
        return val.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    window.verPagos = (userId) => {
        window.open(`${BASE_URL}/collection/student_ledger?u=${userId}`, '_blank');
    };


// ============================================================
// REEMPLAZA COMPLETO el bloque: // --- 6. EXPORTACIÓN EXCEL MULTIPESTAÑA PROFESIONAL ---
// if (btnExportExcel) { ... }
// EN: public/assets/js/managerial_payments_report.js
// ============================================================

// --- 6. EXPORTACIÓN EXCEL MULTIPESTAÑA PROFESIONAL CON COLORES ---
    if (btnExportExcel) {
        btnExportExcel.onclick = async (e) => {
            e.preventDefault();
            if (!lastSummaryData) return;

            if (window.Swal) Swal.fire({ title: 'Generando Excel...', text: 'Preparando reporte de pagos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const fParams = new URLSearchParams(new FormData(form));
                fParams.append('limit', -1);
                const resp = await fetch(`${BASE_URL}/ManagerialPaymentsReport/getReportData?${fParams.toString()}`);
                const fullResult = await resp.json();
                if (!fullResult.ok) return;

                // --- Fecha para nombre de archivo ---
                const now  = new Date();
                const yy   = now.getFullYear();
                const mm   = String(now.getMonth() + 1).padStart(2, '0');
                const dd   = String(now.getDate()).padStart(2, '0');
                const hh   = String(now.getHours()).padStart(2, '0');
                const min  = String(now.getMinutes()).padStart(2, '0');
                const ss   = String(now.getSeconds()).padStart(2, '0');
                const fileName = `Reporte_Ejecutivo_Recaudacion_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

                // --- Colores institucionales ---
                const AZUL    = 'FF2E75B6';
                const BLANCO  = 'FFFFFFFF';
                const AMARILLO= 'FFFFF2CC';
                const VERDE   = 'FFE2EFDA';
                const NARANJA = 'FFFCE4D6';
                const GRIS_CL = 'FFF9F9F9';
                const GRIS_HD = 'FFF2F2F2';
                const AZUL_TT = 'FFDCE6F1';
                const ROJO_CL = 'FFFFD7D7';

                const borderThin = {
                    top:    { style: 'thin', color: { argb: 'FFD9D9D9' } },
                    left:   { style: 'thin', color: { argb: 'FFD9D9D9' } },
                    bottom: { style: 'thin', color: { argb: 'FFD9D9D9' } },
                    right:  { style: 'thin', color: { argb: 'FFD9D9D9' } }
                };

                // Cargar logos
                const logosResp = await fetch(`${BASE_URL}/assets/logos/base64`);
                const logos     = await logosResp.json();

                const agregarLogo = async (ws, base64, extension, col, row, width, height) => {
                    if (!base64) return;
                    const imageId = wb.addImage({ base64, extension });
                    ws.addImage(imageId, { tl: { col, row }, ext: { width, height } });
                };

                const wb = new ExcelJS.Workbook();
                wb.creator = 'Diplomatic';
                wb.created = now;

                // ============================================================
                // HOJA 1: RESUMEN DE DIPLOMADOS
                // ============================================================
                const wsRes = wb.addWorksheet('Resumen Diplomados');

                // Título
                wsRes.addRow(['REPORTE DE RESUMEN DE PAGOS DE DIPLOMADOS']);
                wsRes.mergeCells('A1:L1');
                const rF1 = wsRes.getRow(1);
                rF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
                rF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                rF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                rF1.height = 28;

                // Subtítulo
                const periodoLabel = document.getElementById('filter_periodo')?.options[document.getElementById('filter_periodo')?.selectedIndex]?.text || 'Todos los períodos';
                wsRes.addRow([`Período: ${periodoLabel} — Este resumen consolidado muestra el estado financiero por oferta académica abierta.`]);
                wsRes.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

                // Generado
                wsRes.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
                wsRes.getRow(3).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

                wsRes.addRow([]);

                // Encabezados
                wsRes.addRow(['DIPLOMADO', 'EST.', 'INSCRIPCIÓN', 'CUOTA 1', 'CUOTA 2', 'CUOTA 3', 'CUOTA 4', 'CUOTA 5', 'REC. VALIDADA', 'REC. COMPROMISO', 'TOTAL PROYECTADO', 'OBSERVACIÓN']);
                const rF5 = wsRes.getRow(5);
                rF5.height = 25;
                rF5.eachCell((cell) => {
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                    cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border    = borderThin;
                });

                let tEst = 0, tInsc = 0, tC1 = 0, tC2 = 0, tC3 = 0, tC4 = 0, tC5 = 0, tVal = 0, tCom = 0, tPro = 0;

                fullResult.summary.diploma_summary.forEach((s, idx) => {
                    const proyectado = parseFloat(s.total_proyectado);
                    const row = wsRes.addRow([
                        s.diplomado, parseInt(s.total_estudiantes),
                        parseFloat(s.sum_inscrip), parseFloat(s.sum_c1), parseFloat(s.sum_c2),
                        parseFloat(s.sum_c3), parseFloat(s.sum_c4), parseFloat(s.sum_c5),
                        parseFloat(s.total_validado), parseFloat(s.total_compromiso),
                        proyectado, s.observacion_resumen || ''
                    ]);
                    row.eachCell((cell, colNum) => {
                        cell.border = borderThin;
                        cell.font   = { size: 10 };
                        if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                        if (colNum >= 3 && colNum <= 11 && typeof cell.value === 'number') {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                        }
                        if (colNum === 9) { // Rec. Validada — verde
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                            cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                        }
                        if (colNum === 10) { // Compromiso — naranja
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                        }
                        if (colNum === 11) { // Total proyectado — azul claro
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                            cell.font = { bold: true, size: 10, color: { argb: 'FF' + '1F4E79' } };
                        }
                    });
                    tEst += parseInt(s.total_estudiantes); tInsc += parseFloat(s.sum_inscrip);
                    tC1  += parseFloat(s.sum_c1); tC2 += parseFloat(s.sum_c2); tC3 += parseFloat(s.sum_c3);
                    tC4  += parseFloat(s.sum_c4); tC5 += parseFloat(s.sum_c5); tVal += parseFloat(s.total_validado);
                    tCom += parseFloat(s.total_compromiso); tPro += proyectado;
                });

                // Totales generales
                const rowTotalRes = wsRes.addRow(['TOTALES GENERALES', tEst, tInsc, tC1, tC2, tC3, tC4, tC5, tVal, tCom, tPro, '']);
                rowTotalRes.eachCell((cell, colNum) => {
                    cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                    cell.font   = { bold: true, size: 11 };
                    cell.border = borderThin;
                    if (colNum >= 3 && typeof cell.value === 'number') {
                        cell.numFmt    = '"$"#,##0.00';
                        cell.alignment = { horizontal: 'right' };
                    }
                });
                rowTotalRes.height = 22;

                // Anchos resumen
                wsRes.getColumn(1).width = 45;
                wsRes.getColumn(2).width = 8;
                for (let c = 3; c <= 11; c++) wsRes.getColumn(c).width = 16;
                wsRes.getColumn(12).width = 30;
                wsRes.getRow(1).height = 70;
                wsRes.views = [{ state: 'frozen', xSplit: 0, ySplit: 5 }];
                await agregarLogo(wsRes, logos.ucla,     'png',  0,  0, 90, 70);
                await agregarLogo(wsRes, logos.medicina, 'jpeg', 11, 0, 90, 70);

                // ============================================================
                // HOJA 2: MATRIZ GENERAL DE ESTUDIANTES
                // ============================================================
                const wsMatrix = wb.addWorksheet('Matriz Estudiantes');

                wsMatrix.addRow(['REPORTE DETALLADO DE ESTUDIANTES POR DIPLOMADO']);
                wsMatrix.mergeCells('A1:L1');
                const mF1 = wsMatrix.getRow(1);
                mF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
                mF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                mF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                mF1.height = 28;

                wsMatrix.addRow(['Lista completa de recaudación validada por cada participante registrado.']);
                wsMatrix.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
                wsMatrix.addRow([]);
                wsMatrix.addRow([]);

                wsMatrix.addRow(['ESTUDIANTE', 'ID/CÉDULA', 'DIPLOMADO', 'GRUPOS', 'INSCRIPCIÓN', 'CUOTA 1', 'CUOTA 2', 'CUOTA 3', 'CUOTA 4', 'CUOTA 5', 'TOTAL VALIDADO', 'OBSERVACIÓN']);
                const mF5 = wsMatrix.getRow(5);
                mF5.height = 25;
                mF5.eachCell((cell) => {
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                    cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border    = borderThin;
                });

                fullResult.data.forEach((r, idx) => {
                    const row = wsMatrix.addRow([
                        r.participante, r.cedula, r.diplomado, r.grupos_nombres || 'N/A',
                        parseFloat(r.pago_inscripcion) || 0, parseFloat(r.pago_cuota_1) || 0,
                        parseFloat(r.pago_cuota_2) || 0, parseFloat(r.pago_cuota_3) || 0,
                        parseFloat(r.pago_cuota_4) || 0, parseFloat(r.pago_cuota_5) || 0,
                        parseFloat(r.total_abonado) || 0, r.observacion || '-'
                    ]);
                    row.eachCell((cell, colNum) => {
                        cell.border = borderThin;
                        cell.font   = { size: 10 };
                        if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                        if (colNum >= 5 && colNum <= 10 && typeof cell.value === 'number') {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                            if (cell.value > 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                        }
                        if (colNum === 11) {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                            cell.font      = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                            cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                        }
                        if (colNum === 12 && cell.value && cell.value !== '-') {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                            cell.font = { italic: true, size: 9 };
                        }
                    });
                });

                wsMatrix.getColumn(1).width = 35;
                wsMatrix.getColumn(2).width = 14;
                wsMatrix.getColumn(3).width = 35;
                wsMatrix.getColumn(4).width = 16;
                for (let c = 5; c <= 11; c++) wsMatrix.getColumn(c).width = 14;
                wsMatrix.getColumn(12).width = 35;
                wsMatrix.getRow(1).height = 70;
                wsMatrix.views = [{ state: 'frozen', xSplit: 0, ySplit: 5 }];
                await agregarLogo(wsMatrix, logos.ucla,     'png',  0,  0, 90, 70);
                await agregarLogo(wsMatrix, logos.medicina, 'jpeg', 11, 0, 90, 70);

                // ============================================================
                // HOJAS 3+: DETALLE POR DIPLOMADO
                // ============================================================
                const grouped = {};
                fullResult.data.forEach(r => {
                    if (!grouped[r.diplomado]) grouped[r.diplomado] = [];
                    grouped[r.diplomado].push(r);
                });

                for (const diplomaName of Object.keys(grouped)) {
                    const items    = grouped[diplomaName];
                    let sheetName  = diplomaName.substring(0, 28).replace(/[\\/?*\[\]:]/g, '_');
                    let uniqueName = sheetName;
                    let sheetIdx   = 1;
                    while (wb.getWorksheet(uniqueName)) {
                        uniqueName = sheetName.substring(0, 26) + `_${sheetIdx}`;
                        sheetIdx++;
                    }

                    const ws = wb.addWorksheet(uniqueName);
                    const subT = items.reduce((s, e) => s + (parseFloat(e.total_abonado) || 0), 0);

                    // Título
                    ws.addRow([`DETALLE FINANCIERO: ${diplomaName.toUpperCase()}`]);
                    ws.mergeCells('A1:K1');
                    const dF1 = ws.getRow(1);
                    dF1.getCell(1).font      = { bold: true, size: 13, color: { argb: AZUL } };
                    dF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                    dF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                    dF1.height = 26;

                    // Total diplomado
                    ws.addRow(['Total Recaudado', subT]);
                    const dF2 = ws.getRow(2);
                    dF2.getCell(1).font = { bold: true, size: 11 };
                    dF2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                    dF2.getCell(2).font = { bold: true, size: 11, color: { argb: 'FF1E6B24' } };
                    dF2.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                    dF2.getCell(2).numFmt = '"$"#,##0.00';

                    ws.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
                    ws.getRow(3).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
                    ws.addRow([]);

                    // Encabezados
                    ws.addRow(['ESTUDIANTE', 'ID/CÉDULA', 'GRUPOS', 'INSCRIPCIÓN', 'CUOTA 1', 'CUOTA 2', 'CUOTA 3', 'CUOTA 4', 'CUOTA 5', 'TOTAL VALIDADO', 'OBSERVACIÓN']);
                    const dF5 = ws.getRow(5);
                    dF5.height = 25;
                    dF5.eachCell((cell) => {
                        cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                        cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                        cell.border    = borderThin;
                    });

                    // Datos
                    items.forEach((e, idx) => {
                        const row = ws.addRow([
                            e.participante, e.cedula, e.grupos_nombres || 'N/A',
                            parseFloat(e.pago_inscripcion) || 0, parseFloat(e.pago_cuota_1) || 0,
                            parseFloat(e.pago_cuota_2) || 0, parseFloat(e.pago_cuota_3) || 0,
                            parseFloat(e.pago_cuota_4) || 0, parseFloat(e.pago_cuota_5) || 0,
                            parseFloat(e.total_abonado) || 0, e.observacion || ''
                        ]);
                        row.eachCell((cell, colNum) => {
                            cell.border = borderThin;
                            cell.font   = { size: 10 };
                            if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                            if (colNum >= 4 && colNum <= 9 && typeof cell.value === 'number') {
                                cell.numFmt    = '"$"#,##0.00';
                                cell.alignment = { horizontal: 'right' };
                                if (cell.value > 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                            }
                            if (colNum === 10) {
                                cell.numFmt    = '"$"#,##0.00';
                                cell.alignment = { horizontal: 'right' };
                                cell.font      = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                            }
                            if (colNum === 11 && cell.value && cell.value !== '') {
                                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                                cell.font = { italic: true, size: 9 };
                            }
                        });
                    });

                    // Total del diplomado
                    const rowSub = ws.addRow(['TOTAL RECAUDADO EN PROGRAMA', '', '', '', '', '', '', '', '', subT, '']);
                    rowSub.eachCell((cell, colNum) => {
                        cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                        cell.font   = { bold: true, size: 10 };
                        cell.border = borderThin;
                        if (colNum === 10) {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                            cell.font      = { bold: true, size: 11, color: { argb: 'FF1E6B24' } };
                        }
                    });

                    // Anchos
                    ws.getColumn(1).width = 35;
                    ws.getColumn(2).width = 14;
                    ws.getColumn(3).width = 16;
                    for (let c = 4; c <= 10; c++) ws.getColumn(c).width = 14;
                    ws.getColumn(11).width = 35;
                    ws.getRow(1).height = 70;
                    ws.views = [{ state: 'frozen', xSplit: 0, ySplit: 5, topLeftCell: 'A6' }];
                    await agregarLogo(ws, logos.ucla,     'png',  0,  0, 90, 70);
                    await agregarLogo(ws, logos.medicina, 'jpeg', 10, 0, 90, 70);
                
                 }

                // Descargar
                const buffer = await wb.xlsx.writeBuffer();   
                const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                saveAs(blob, fileName);

                if (window.Swal) Swal.close();

            } catch (err) {
                console.error('Error ExcelJS:', err);
                if (window.Swal) Swal.fire('Error', err.message, 'error');
            }
        };
    }
    // --- 7. EXPORTACIÓN PDF ---
    if (btnExportPdf) {
        btnExportPdf.onclick = (e) => {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(form)).toString();
            window.open(`${BASE_URL}/ManagerialPaymentsReport/exportPdf?${params}`, '_blank');
        };
    }

    // --- 8. LÓGICA DINÁMICA PARA EL FILTRO DE GRUPOS ---
    const offeringSelect = document.getElementById('filter_offering');
    const groupSelect = document.getElementById('filter_group');

    if (offeringSelect && groupSelect) {
        const updateGroups = async () => {
            const offeringId = offeringSelect.value;
            
            if (offeringId === 'ALL') {
                groupSelect.innerHTML = '<option value="ALL">Todos los Grupos</option>';
                groupSelect.disabled = true;
            } else {
                groupSelect.disabled = false;
                groupSelect.innerHTML = '<option value="">Cargando grupos...</option>';
                
                try {
                    const response = await fetch(`${BASE_URL}/ManagerialPaymentsReport/getGroupsByOffering?offering_id=${offeringId}`);
                    
                    if (!response.ok) throw new Error('Error en la red');
                    
                    const groups = await response.json();
                    
                    let optionsHtml = '<option value="ALL">Todos los Grupos</option>';
                    if (groups && groups.length > 0) {
                        groups.forEach(g => {
                            optionsHtml += `<option value="${g.id}">${g.name}</option>`;
                        });
                    } else {
                        optionsHtml = '<option value="ALL">Sin grupos asignados</option>';
                    }
                    
                    groupSelect.innerHTML = optionsHtml;
                } catch (error) {
                    console.error('Error al cargar los grupos:', error);
                    groupSelect.innerHTML = '<option value="ALL">Error al cargar</option>';
                }
            }
        };

        // Escuchar cuando el usuario cambia el select de Ofertas
        offeringSelect.addEventListener('change', updateGroups);
        
        // Ejecutar una vez al inicializar
        updateGroups();
    }

    // --- 9. FILTRO DE PERÍODO — recarga la página al cambiar ---
const periodoSelect = document.getElementById('filter_periodo');
if (periodoSelect) {
    periodoSelect.addEventListener('change', () => {
        const periodoId = periodoSelect.value;
        const url = new URL(window.location.href);
        url.searchParams.set('periodo_id', periodoId);
        window.location.href = url.toString();
    });
}

})();