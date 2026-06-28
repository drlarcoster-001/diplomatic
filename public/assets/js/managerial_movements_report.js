/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE MOVIMIENTOS
 * ARCHIVO: public/assets/js/managerial_movements_report.js
 * PROPÓSITO: Lógica de matriz dinámica 360°. Renderizado de N-Cuotas, 
 * trazabilidad de pagos, totales detallados por concepto y exportación Excel premium.
 * VERSIÓN: 4.2.0 - FIX: Totales generales en el encabezado del Excel por encima de la tabla.
 * Sincronizado con el Modelo 4.0.0 (Flat Data + Rescate de IDs).
 */

(function() {
    "use strict";

    // --- 1. CONFIGURACIÓN Y ESTADO ---
    const RECORDS_PER_PAGE = 25;
    let currentPage = 1;
    let currentHeaders = []; 

    // --- 2. SELECTORES DE ELEMENTOS ---
    const form = document.getElementById('form-movements-filters');
    const smartSearch = document.getElementById('search-input');
    const resultsArea = document.getElementById('results-container');
    const emptyState = document.getElementById('empty-state');
    const thead = document.getElementById('movements-thead');
    const tbody = document.getElementById('movements-tbody');
    
    const paginationControls = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');
    const btnExportExcel = document.getElementById('btn-export-excel');
    
    const offeringSelect = document.getElementById('filter_offering');
    const groupSelect = document.getElementById('filter_group');

    // Contenedor dinámico de totales
    const totalsContainer = document.getElementById('totals-dynamic-container');

    /**
     * Función Helper: Convierte el nombre del concepto ('CUOTA 1') 
     * en el alias exacto usado por el backend ('CUOTA_1')
     */
    function getAlias(concept) {
        return concept.toUpperCase().replace(/ /g, '_');
    }

    /**
     * Carga los datos evitando la doble lectura del stream y gestionando estados vacíos.
     */
    async function loadReport(page = 1) {
        currentPage = page;
        
        // UI Feedback
        if (tbody) tbody.style.opacity = '0.4';
        
        try {
            const formData = new URLSearchParams(new FormData(form));
            formData.append('page', page);
            formData.append('limit', RECORDS_PER_PAGE);

            const url = `${BASE_URL}/ManagerialMovementsReport/loadData?${formData.toString()}`;
            const response = await fetch(url);

            const result = await response.json();

            if (result.status === 'success') {
                currentHeaders = result.headers || []; 
                
                // --- CASO: NO HAY RESULTADOS ---
                if (!result.data || result.data.length === 0) {
                    if (resultsArea) resultsArea.classList.add('d-none');
                    if (totalsContainer) totalsContainer.classList.add('d-none');
                    if (emptyState) {
                        emptyState.classList.remove('d-none');
                        emptyState.innerHTML = `
                            <div class="py-5 animate__animated animate__fadeIn">
                                <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                                <h4 class="mt-3 fw-bold text-dark">No se encontraron registros</h4>
                                <p class="text-muted">No existen movimientos para el filtro seleccionado.</p>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="location.reload()">Limpiar Filtros</button>
                            </div>
                        `;
                    }
                    return;
                }

                // --- CASO: SÍ HAY RESULTADOS ---
                renderHeaders(result.headers);
                renderMatrix(result.data, result.headers, page);
                renderPagination(result.total || result.data.length, page);
                
                calculateDetailedTotals(result.data, result.headers);
                
                if (emptyState) emptyState.classList.add('d-none');
                if (resultsArea) resultsArea.classList.remove('d-none');
                if (totalsContainer) totalsContainer.classList.remove('d-none');

            } else {
                throw new Error(result.message || "Error desconocido en el servidor.");
            }

        } catch (error) {
            console.error("Fallo crítico en loadReport:", error);
            if (window.Swal) {
                Swal.fire({ icon: 'warning', title: 'Aviso', text: error.message });
            }
            if (emptyState) emptyState.classList.add('d-none');
        } finally {
            if (tbody) tbody.style.opacity = '1';
        }
    }

    /**
     * Motor de Cálculo de Totales Desglosados.
     */
    function calculateDetailedTotals(data, headers) {
        if (!totalsContainer) return;
        
        totalsContainer.innerHTML = "";
        let totalsMap = {};
        let totalGeneralAbonado = 0;
        let sumaSoloCuotas = 0;

        headers.forEach(h => totalsMap[h] = 0);

        data.forEach(student => {
            totalGeneralAbonado += parseFloat(student.total_abonado || 0);
            
            headers.forEach(h => {
                const alias = getAlias(h);
                const monto = parseFloat(student[`MONTO_${alias}`] || 0);
                totalsMap[h] += monto;
                if (h.toUpperCase().includes('CUOTA')) {
                    sumaSoloCuotas += monto;
                }
            });
        });

        let html = "";
        headers.forEach(h => {
            const isInstallment = h.toUpperCase().includes('CUOTA');
            const color = isInstallment ? 'info' : 'primary';
            html += `
                <div class="col-md">
                    <div class="p-2 border rounded bg-light shadow-sm border-start border-${color} border-3">
                        <div class="text-muted fw-bold" style="font-size: 9px; text-transform: uppercase;">TOTAL ${h}</div>
                        <div class="fw-bold text-${color}" style="font-size: 13px;">$ ${fMoney(totalsMap[h])}</div>
                    </div>
                </div>`;
        });

        html += `
            <div class="col-md">
                <div class="p-2 border rounded bg-white shadow-sm border-start border-warning border-3">
                    <div class="text-muted fw-bold" style="font-size: 9px;">SUMA TODAS CUOTAS</div>
                    <div class="fw-bold text-warning" style="font-size: 13px;">$ ${fMoney(sumaSoloCuotas)}</div>
                </div>
            </div>
            <div class="col-md">
                <div class="p-2 border rounded bg-primary shadow-sm">
                    <div class="text-white-50 fw-bold" style="font-size: 9px;">TOTAL RECAUDADO</div>
                    <div class="fw-bold text-white" style="font-size: 13px;">$ ${fMoney(totalGeneralAbonado)}</div>
                </div>
            </div>`;

        totalsContainer.innerHTML = html;
    }

    function renderHeaders(headers) {
        if (!thead) return;
        let html = `<tr class="bg-light"><th>NRO</th><th>PARTICIPANTE / DIPLOMADO</th><th>CEDULA</th><th>CORREO</th>`;
        headers.forEach(h => {
            const colorClass = h.toUpperCase().includes('INSCRIP') ? 'col-inscripcion' : 'col-cuota-impar';
            html += `
                <th class="${colorClass}">${h} (MONTO)</th>
                <th class="${colorClass}">FORMA PAGO</th>
                <th class="${colorClass}">REF / RECIBO</th>
                <th class="${colorClass}">BANCO</th>
                <th class="${colorClass} border-end">FECHA</th>
            `;
        });
        html += `
            <th class="bg-dark text-white fw-bold text-end pe-3">TOTAL ABONADO</th>
            <th class="bg-warning text-dark fw-bold border-start ps-3">OBSERVACIONES</th>
        </tr>`;
        thead.innerHTML = html;
    }

    function renderMatrix(data, headers, page) {
        if (!tbody) return;
        let finalHtml = "";
        data.forEach((r, idx) => {
            const globalNro = ((page - 1) * RECORDS_PER_PAGE) + (idx + 1);
            let rowHtml = `
                <tr>
                    <td class="sticky-nro">${globalNro}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-dark fw-bold">${r.participante}</span>
                            <span class="text-muted" style="font-size: 10px;">${r.diplomado}</span>
                        </div>
                    </td>
                    <td class="small fw-medium">${r.cedula}</td>
                    <td class="small text-muted">${r.email}</td>
            `;
            
            headers.forEach(h => {
                const alias = getAlias(h);
                const monto = parseFloat(r[`MONTO_${alias}`] || 0);
                const forma = r[`FORMA_${alias}`] || '-';
                const formaClass = forma === 'ABONO PARCIAL' ? 'text-warning fw-bold' : 'text-muted';

                if (monto > 0) {
                    rowHtml += `
                        <td class="fw-bold text-dark text-end">${fMoney(monto)}</td>
                        <td class="small ${formaClass}">${forma}</td>
                        <td class="small text-muted">${r[`REF_${alias}`] || 'N/A'}</td>
                        <td class="small">${r[`BANCO_${alias}`] || 'N/A'}</td>
                        <td class="small border-end">${r[`FECHA_${alias}`] || '-'}</td>
                    `;
                } else {
                    rowHtml += `<td colspan="5" class="text-center text-muted opacity-25 border-end">-</td>`;
                }
            });
            
            const obsText = r.observaciones ? `<span class="badge bg-danger rounded-pill" style="font-size: 10px;">${r.observaciones}</span>` : '-';
            rowHtml += `
                <td class="fw-bold bg-light text-end pe-3 text-success">${fMoney(r.total_abonado || 0)}</td>
                <td class="small border-start ps-3">${obsText}</td>
            </tr>`;
            finalHtml += rowHtml;
        });
        tbody.innerHTML = finalHtml;
    }

    function renderPagination(totalRecords, activePage) {
        if (!paginationControls) return;
        const totalPages = Math.ceil(totalRecords / RECORDS_PER_PAGE);
        if (paginationInfo) paginationInfo.innerText = `Registros encontrados: ${totalRecords}`;
        paginationControls.innerHTML = '';
        if (totalPages <= 1) return;

        const addBtn = (label, target, isActive = false) => {
            const btn = document.createElement('button');
            btn.className = `btn btn-sm ${isActive ? 'btn-primary' : 'btn-outline-primary'}`;
            btn.innerHTML = label;
            btn.onclick = () => loadReport(target);
            paginationControls.appendChild(btn);
        };
        addBtn('<i class="bi bi-chevron-left"></i>', activePage > 1 ? activePage - 1 : 1);
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= activePage - 2 && i <= activePage + 2)) {
                addBtn(i, i, i === activePage);
            }
        }
        addBtn('<i class="bi bi-chevron-right"></i>', activePage < totalPages ? activePage + 1 : totalPages);
    }


// ============================================================
// REEMPLAZA COMPLETO el bloque: if (btnExportExcel) { ... }
// EN: public/assets/js/managerial_movements_report.js
// ============================================================

if (btnExportExcel) {
    btnExportExcel.onclick = async (e) => {
        e.preventDefault();
        if (window.Swal) Swal.fire({ title: 'Generando Excel...', text: 'Preparando matriz de trazabilidad...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fParams = new URLSearchParams(new FormData(form));
            fParams.append('limit', -1);
            const resp   = await fetch(`${BASE_URL}/ManagerialMovementsReport/loadData?${fParams.toString()}`);
            const result = await resp.json();

            if (!result.data || result.data.length === 0) {
                Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
                return;
            }

            // --- Nombre del archivo con fecha invertida y hora+segundos ---
            const now     = new Date();
            const yy      = now.getFullYear();
            const mm      = String(now.getMonth() + 1).padStart(2, '0');
            const dd      = String(now.getDate()).padStart(2, '0');
            const hh      = String(now.getHours()).padStart(2, '0');
            const min     = String(now.getMinutes()).padStart(2, '0');
            const ss      = String(now.getSeconds()).padStart(2, '0');
            const fileName = `Reporte_Maestro_Movimientos_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

            // --- Colores institucionales ---
            const AZUL    = 'FF2E75B6';
            const BLANCO  = 'FFFFFFFF';
            const AMARILLO= 'FFFFF2CC';
            const VERDE   = 'FFE2EFDA';
            const NARANJA = 'FFFCE4D6';
            const GRIS_CL = 'FFF9F9F9';
            const GRIS_HD = 'FFF2F2F2';
            const AZUL_TT = 'FFDCE6F1';

            const borderThin = {
                top:    { style: 'thin', color: { argb: 'FFD9D9D9' } },
                left:   { style: 'thin', color: { argb: 'FFD9D9D9' } },
                bottom: { style: 'thin', color: { argb: 'FFD9D9D9' } },
                right:  { style: 'thin', color: { argb: 'FFD9D9D9' } }
            };

            // --- Agrupar por diplomado ---
            const groupedData = result.data.reduce((acc, s) => {
                const d = s.diplomado || 'General';
                if (!acc[d]) acc[d] = [];
                acc[d].push(s);
                return acc;
            }, {});

            // Cargar logos
            const logos = LOGOS_BASE64;

            const agregarLogo = async (ws, base64, extension, col, row, width, height) => {
                if (!base64) return;
                const imageId = wb.addImage({ base64, extension });
                ws.addImage(imageId, { tl: { col, row }, ext: { width, height } });
            };

            const wb = new ExcelJS.Workbook();
            wb.creator  = 'Diplomatic';
            wb.created  = now;

            // Acumulador para hoja resumen
            const resumenDiplomados = [];
            let   totalGeneralAcum  = 0;
            let   resumenId         = 1;

           // ============================================================
            // HOJAS POR DIPLOMADO
            // ============================================================
            for (const diploma of Object.keys(groupedData)) { 
                const students  = groupedData[diploma];
                const sheetName = (diploma.substring(0, 28) + '_' + (Object.keys(groupedData).indexOf(diploma) + 1)).replace(/[\\/?*\[\]:]/g, '_');
                const ws        = wb.addWorksheet(sheetName);

                const totalDiploma = students.reduce((sum, s) => sum + parseFloat(s.total_abonado || 0), 0);
                totalGeneralAcum  += totalDiploma;

                resumenDiplomados.push({
                    id:        resumenId++,
                    diplomado: diploma,
                    inscritos: students.length,
                    total:     totalDiploma
                });

                // --- FILA 1: Título ---
                ws.addRow([`MATRIZ DE TRAZABILIDAD: ${diploma.toUpperCase()}`]);
                const f1 = ws.getRow(1);
                const lastColNum = 4 + (currentHeaders.length * 5) + 2;
                ws.mergeCells(1, 1, 1, lastColNum);
                f1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
                f1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                f1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                f1.height = 28;

                // --- FILA 2: Total Recaudado ---
                ws.addRow(['Total Recaudado', totalDiploma]);
                const f2 = ws.getRow(2);
                f2.getCell(1).font = { bold: true, size: 11 };
                f2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                f2.getCell(2).font = { bold: true, size: 11, color: { argb: 'FF1E6B24' } };
                f2.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                f2.getCell(2).numFmt = '"$"#,##0.00';

                // --- FILA 3: Generado ---
                const periodoLabelMov = document.getElementById('filter_periodo')?.options[document.getElementById('filter_periodo')?.selectedIndex]?.text || 'Todos los períodos';
                ws.addRow([`Período: ${periodoLabelMov} — Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
                ws.getRow(3).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

                // --- FILA 4: Totales por concepto ---
                const totalesRow = ['', '', '', ''];
                currentHeaders.forEach(h => {
                    const alias = h.toUpperCase().replace(/ /g, '_');
                    const total = students.reduce((sum, s) => sum + parseFloat(s[`MONTO_${alias}`] || 0), 0);
                    totalesRow.push(`TOTAL ${h}:`, total, '', '', '');
                });
                totalesRow.push('TOTAL RECAUDADO:', totalDiploma, '');
                ws.addRow(totalesRow);
                const f4 = ws.getRow(4);
                f4.eachCell((cell) => {
                    if (cell.value && cell.value !== '') {
                        cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                        cell.font   = { bold: true, size: 10 };
                        cell.border = borderThin;
                        if (typeof cell.value === 'number') cell.numFmt = '"$"#,##0.00';
                    }
                });

                // --- FILA 5: Encabezados tabla ---
                const headers = ['NRO', 'ESTUDIANTE', 'CÉDULA', 'CORREO'];
                currentHeaders.forEach(h => {
                    headers.push(`${h} ($)`, 'FORMA PAGO', 'REF / RECIBO', 'BANCO', 'FECHA');
                });
                headers.push('TOTAL ALUMNO', 'OBSERVACIONES');
                ws.addRow(headers);
                const f5 = ws.getRow(5);
                f5.height = 30;
                f5.eachCell((cell) => {
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                    cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border    = borderThin;
                });

                // --- FILAS DE DATOS ---
                const totalCol = 5 + (currentHeaders.length * 5);
                students.forEach((r, idx) => {
                    const rowData = [idx + 1, r.participante, r.cedula, r.email];
                    currentHeaders.forEach(h => {
                        const alias = h.toUpperCase().replace(/ /g, '_');
                        const monto = parseFloat(r[`MONTO_${alias}`] || 0);
                        if (monto > 0) {
                            rowData.push(monto, r[`FORMA_${alias}`] || '-', r[`REF_${alias}`] || '-', r[`BANCO_${alias}`] || '-', r[`FECHA_${alias}`] || '-');
                        } else {
                            rowData.push('', '', '', '', '');
                        }
                    });
                    rowData.push(parseFloat(r.total_abonado || 0), r.observaciones || '');

                    const dataRow = ws.addRow(rowData);
                    dataRow.eachCell((cell, colNum) => {
                        cell.border = borderThin;
                        cell.font   = { size: 10 };
                        if (idx % 2 === 0) {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                        }
                        // Total alumno — verde
                        if (colNum === totalCol) {
                            cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                            cell.font   = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                            cell.numFmt = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                        }
                        // Observaciones — naranja si tiene contenido
                        if (colNum === totalCol + 1 && cell.value && cell.value !== '') {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                            cell.font = { italic: true, size: 9 };
                        }
                        // Montos numéricos
                        if (typeof cell.value === 'number' && colNum !== 1 && colNum !== 3) {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                        }
                    });
                });

                // --- Anchos de columnas ---
                ws.getColumn(1).width = 6;
                ws.getColumn(2).width = 35;
                ws.getColumn(3).width = 14;
                ws.getColumn(4).width = 28;
                currentHeaders.forEach((h, i) => {
                    const base = 5 + (i * 5);
                    ws.getColumn(base).width     = 12;
                    ws.getColumn(base + 1).width = 14;
                    ws.getColumn(base + 2).width = 16;
                    ws.getColumn(base + 3).width = 22;
                    ws.getColumn(base + 4).width = 12;
                });
                ws.getColumn(totalCol).width     = 14;
                ws.getColumn(totalCol + 1).width = 40;

// --- Congelar fila 5 y columna D ---
                ws.getRow(1).height = 70;
                ws.views = [{ state: 'frozen', xSplit: 4, ySplit: 5, topLeftCell: 'E6' }];
                await agregarLogo(ws, logos.ucla,     'png',  0,  0, 90, 70);
                await agregarLogo(ws, logos.medicina, 'jpeg', (4 + (currentHeaders.length * 5) + 1), 0, 90, 70);
            }

            // ============================================================
            // HOJA RESUMEN TOTAL DE DIPLOMADOS
            // ============================================================
            const wsResumen = wb.addWorksheet('Resumen Total de Diplomados');

            // Título
            wsResumen.addRow(['RESUMEN TOTAL DE DIPLOMADOS']);
            wsResumen.mergeCells('A1:E1');
            const rF1 = wsResumen.getRow(1);
            rF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            rF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            rF1.getCell(1).alignment = { horizontal: 'center' };
            rF1.height = 28;

            // Generado
            wsResumen.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsResumen.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

            wsResumen.addRow([]);

            // Encabezados
            wsResumen.addRow(['#ID', 'DIPLOMADO', 'CANTIDAD DE INSCRITOS', 'TOTAL ACUMULADO POR DIPLOMADO']);
            const rF4 = wsResumen.getRow(4);
            rF4.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });
            rF4.height = 25;

            // Datos
            resumenDiplomados.forEach((d, idx) => {
                const row = wsResumen.addRow([d.id, d.diplomado, d.inscritos, d.total]);
                row.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) {
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                    }
                    if (colNum === 4) {
                        cell.numFmt    = '"$"#,##0.00';
                        cell.alignment = { horizontal: 'right' };
                        cell.font      = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                        cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                    }
                    if (colNum === 3) {
                        cell.alignment = { horizontal: 'center' };
                    }
                });
            });

            // Total General
            const totalInscritos = resumenDiplomados.reduce((sum, d) => sum + d.inscritos, 0);
            const rowTotal = wsResumen.addRow(['', 'TOTAL GENERAL', totalInscritos, totalGeneralAcum]);
            rowTotal.eachCell((cell, colNum) => {
                cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font   = { bold: true, size: 11 };
                cell.border = borderThin;
                if (colNum === 4) {
                    cell.numFmt    = '"$"#,##0.00';
                    cell.alignment = { horizontal: 'right' };
                    cell.font      = { bold: true, size: 12, color: { argb: 'FF1E6B24' } };
                }
                if (colNum === 3) cell.alignment = { horizontal: 'center' };
            });
            rowTotal.height = 22;

// Anchos resumen
            wsResumen.getColumn(1).width = 8;
            wsResumen.getColumn(2).width = 50;
            wsResumen.getColumn(3).width = 22;
            wsResumen.getColumn(4).width = 28;
            wsResumen.getRow(1).height = 70;
            await agregarLogo(wsResumen, logos.ucla,     'png',  0, 0, 90, 70);
            await agregarLogo(wsResumen, logos.medicina, 'jpeg', 4, 0, 90, 70);

            // ============================================================
            // DESCARGAR
            // ============================================================
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





    if (offeringSelect && groupSelect) {
        offeringSelect.onchange = async () => {
            const id = offeringSelect.value;
            groupSelect.disabled = (id === 'ALL');
            if (id === 'ALL') {
                groupSelect.innerHTML = '<option value="ALL">Todos los Grupos</option>';
            } else {
                groupSelect.innerHTML = '<option value="">Cargando...</option>';
                try {
                    const res = await fetch(`${BASE_URL}/ManagerialMovementsReport/getGroupsByOffering?id=${id}`);
                    const groups = await res.json();
                    let html = '<option value="ALL">Todos los Grupos</option>';
                    groups.forEach(g => { html += `<option value="${g.id}">${g.name}</option>`; });
                    groupSelect.innerHTML = html;
                } catch (e) { groupSelect.innerHTML = '<option value="ALL">Error</option>'; }
            }
            loadReport(1);
        };
        groupSelect.onchange = () => loadReport(1);
    }

    if (form) form.onsubmit = (e) => { e.preventDefault(); loadReport(1); };
    if (smartSearch) {
        let timer;
        smartSearch.oninput = () => {
            clearTimeout(timer);
            timer = setTimeout(() => loadReport(1), 700);
        };
    }

    function fMoney(v) {
        return (parseFloat(v) || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', () => { loadReport(1); });

    // --- FILTRO DE PERÍODO ---
    const periodoSelect = document.getElementById('filter_periodo');
    if (periodoSelect) {
        periodoSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('periodo_id', periodoSelect.value);
            window.location.href = url.toString();
        });
    }
})();

