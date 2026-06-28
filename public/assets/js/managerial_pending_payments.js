/**
 * MÓDULO: PANEL GERENCIAL / PAGOS PENDIENTES
 * ARCHIVO: public/assets/js/managerial_pending_payments.js
 * PROPÓSITO: Lógica AJAX para la bandeja de auditoría, paginación, cálculo en tránsito y exportación.
 * VERSIÓN: 1.1.0 - Fix del botón reiniciar y carga inicial automática al entrar a la vista (DOMContentLoaded).
 */

(function() {
    "use strict";

    // --- 1. CONFIGURACIÓN Y ESTADO ---
    let currentPage = 1;

    // --- 2. SELECTORES DE ELEMENTOS UI ---
    const form = document.getElementById('form-pending-filters');
    const smartSearch = document.getElementById('dynamic-student-search');
    const resultsArea = document.getElementById('report-results-container');
    const emptyState = document.getElementById('empty-state');
    const tbody = document.getElementById('pending-tbody');
    
    const lblTotalPending = document.getElementById('lbl-total-pending');
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
                    loadData(1);
                }
            }, 700);
        });
    }

    // --- 4. CONTROL DE REINICIO Y CARGA INICIAL ---
    const btnReset = document.getElementById('btn-reset-filters');
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            form.reset(); // Limpia los inputs del formulario
            loadData(1);  // Llama a la base de datos de nuevo, pero todo vacío (carga limpia)
        });
    }

    // Carga inicial automática al entrar a la pantalla
    document.addEventListener('DOMContentLoaded', () => {
        loadData(1);
    });

    // --- 5. PROCESAMIENTO AJAX ---
    form.onsubmit = async (e) => {
        e.preventDefault();
        loadData(1);
    };

    async function loadData(page) {
        currentPage = page;
        try {
            const formData = new URLSearchParams(new FormData(form));
            formData.append('page', page);

            const response = await fetch(`${BASE_URL}/ManagerialPendingPayments/getPendingData?${formData.toString()}`);
            const result = await response.json();

            if (result.ok) {
                if (result.data.length > 0) {
                    renderTable(result.data);
                    renderPaginationControls(result.pagination.total_records, result.pagination.pages, page);
                    
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

    // --- 6. RENDERIZADO DE LA TABLA Y ALERTAS VISUALES ---
    function renderTable(data) {
        let currentPageTotalUsd = 0;

        tbody.innerHTML = data.map(r => {
            const isCash = r.tipo_pago.toUpperCase() === 'EFECTIVO';
            const usdValue = parseFloat(r.monto_usd) || 0;
            currentPageTotalUsd += usdValue;

            const originClass = r.origin === 'INSCRIPCION' ? 'origin-inscription' : 'origin-installment';
            const alertClass = isCash ? 'alert-cash' : 'alert-bank';
            const alertIcon = isCash ? 'bi-exclamation-triangle-fill' : 'bi-clock-history';
            
            let payTypeClass = 'text-dark';
            if(r.tipo_pago === 'ZELLE') payTypeClass = 'pay-type-zelle';
            if(r.tipo_pago === 'BINANCE') payTypeClass = 'pay-type-binance';
            if(r.tipo_pago === 'PAGO MOVIL') payTypeClass = 'pay-type-pm';
            if(isCash) payTypeClass = 'pay-type-cash';

            return `
                <tr>
                    <td class="text-center text-muted small">${r.cedula}</td>
                    <td class="ps-3 fw-bold text-dark" style="font-size: 0.85rem;">${r.estudiante}</td>
                    <td class="text-center text-muted small" style="font-size: 0.75rem;">${r.diplomado}</td>
                    <td class="text-center">
                        <span class="badge-origin ${originClass}">${r.origin}</span>
                    </td>
                    <td class="text-center ${payTypeClass}">${r.tipo_pago}</td>
                    <td class="text-end fw-medium">${fNumber(r.monto)} ${r.moneda}</td>
                    <td class="text-center text-muted small">${fNumber(r.tasa)}</td>
                    <td class="text-end pe-3 bg-light amount-highlight">$ ${fNumber(usdValue)}</td>
                    <td class="text-center">
                        <span class="badge-alert ${alertClass}">
                            <i class="bi ${alertIcon}"></i> ${r.observacion}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        // Actualiza el total flotante de la vista actual
        lblTotalPending.innerText = `$ ${fNumber(currentPageTotalUsd)}`;
    }

    function renderPaginationControls(totalRecords, totalPages, activePage) {
        paginationControls.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.type = "button";
            btn.className = `pagination-btn ${i === activePage ? 'active' : ''}`;
            btn.innerText = i;
            btn.onclick = () => loadData(i);
            paginationControls.appendChild(btn);
        }
        paginationInfo.innerText = `Pág. ${activePage} de ${totalPages} (${totalRecords} trámites pendientes)`;
    }

    // --- 7. HELPERS DE FORMATO ---
    function fNumber(v) {
        const val = parseFloat(v) || 0;
        return val.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    
    // --- 8. EXPORTACIÓN EXCEL MULTIPESTAÑA ---
    
// ============================================================
// REEMPLAZA COMPLETO el bloque: if (btnExportExcel) { ... }
// EN: public/assets/js/managerial_pending_payments.js
// ============================================================

if (btnExportExcel) {
    btnExportExcel.onclick = async (e) => {
        e.preventDefault();
        if (window.Swal) Swal.fire({ title: 'Generando Excel...', text: 'Preparando auditoría de pagos pendientes...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fParams = new URLSearchParams(new FormData(form));
            fParams.append('limit', -1);
            const resp   = await fetch(`${BASE_URL}/ManagerialPendingPayments/getPendingData?${fParams.toString()}`);
            const result = await resp.json();

            if (!result.ok || result.data.length === 0) {
                if (window.Swal) Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
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
            const fileName = `Auditoria_Pagos_Pendientes_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

            // --- Colores institucionales ---
            const AZUL    = 'FF2E75B6';
            const BLANCO  = 'FFFFFFFF';
            const AMARILLO= 'FFFFF2CC';
            const VERDE   = 'FFE2EFDA';
            const NARANJA = 'FFFCE4D6';
            const GRIS_CL = 'FFF9F9F9';
            const GRIS_HD = 'FFF2F2F2';
            const ROJO_CL = 'FFFFD7D7';
            const AZUL_TT = 'FFDCE6F1';

            const borderThin = {
                top:    { style: 'thin', color: { argb: 'FFD9D9D9' } },
                left:   { style: 'thin', color: { argb: 'FFD9D9D9' } },
                bottom: { style: 'thin', color: { argb: 'FFD9D9D9' } },
                right:  { style: 'thin', color: { argb: 'FFD9D9D9' } }
            };

            // --- Agrupar por diplomado ---
            const grouped = {};
            let totalUsdGlobal = 0;
            result.data.forEach(r => {
                if (!grouped[r.diplomado]) grouped[r.diplomado] = [];
                grouped[r.diplomado].push(r);
                totalUsdGlobal += parseFloat(r.monto_usd) || 0;
            });

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
            // HOJA 1: RESUMEN POR DIPLOMADO
            // ============================================================
            const wsResumen = wb.addWorksheet('Resumen Diplomados');

            // Título
            wsResumen.addRow(['AUDITORÍA DE PAGOS PENDIENTES — RESUMEN POR DIPLOMADO']);
            wsResumen.mergeCells('A1:I1');
            const rF1 = wsResumen.getRow(1);
            rF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            rF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            rF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rF1.height = 28;

            // Generado
            const periodoLabelPend = document.getElementById('filter_periodo')?.options[document.getElementById('filter_periodo')?.selectedIndex]?.text || 'Todos los períodos';
            wsResumen.addRow([`Período: ${periodoLabelPend} — Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsResumen.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

            // Total General
            wsResumen.addRow(['Total Flotante Global (USD)', totalUsdGlobal]);
            const rF3 = wsResumen.getRow(3);
            rF3.getCell(1).font = { bold: true, size: 11 };
            rF3.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
            rF3.getCell(2).font = { bold: true, size: 11, color: { argb: 'FFCC0000' } };
            rF3.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } };
            rF3.getCell(2).numFmt = '"$"#,##0.00';

            wsResumen.addRow([]);

            // Encabezados resumen
            wsResumen.addRow(['#', 'DIPLOMADO', 'CANT. TRÁMITES', 'TOTAL FLOTANTE (USD)']);
            const rF5 = wsResumen.getRow(5);
            rF5.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });
            rF5.height = 25;

            // Datos resumen
            let resumenIdx = 1;
            Object.keys(grouped).forEach((diplomaName, idx) => {
                const items   = grouped[diplomaName];
                const subT    = items.reduce((sum, r) => sum + (parseFloat(r.monto_usd) || 0), 0);
                const row     = wsResumen.addRow([resumenIdx++, diplomaName, items.length, subT]);
                row.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                    if (colNum === 3) cell.alignment = { horizontal: 'center' };
                    if (colNum === 4) {
                        cell.numFmt    = '"$"#,##0.00';
                        cell.alignment = { horizontal: 'right' };
                        cell.font      = { bold: true, size: 10, color: { argb: 'FFCC0000' } };
                        cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                    }
                });
            });

            // Total general resumen
            const rowTotal = wsResumen.addRow(['', 'TOTAL GENERAL', result.data.length, totalUsdGlobal]);
            rowTotal.eachCell((cell, colNum) => {
                cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font   = { bold: true, size: 11 };
                cell.border = borderThin;
                if (colNum === 3) cell.alignment = { horizontal: 'center' };
                if (colNum === 4) {
                    cell.numFmt    = '"$"#,##0.00';
                    cell.alignment = { horizontal: 'right' };
                    cell.font      = { bold: true, size: 12, color: { argb: 'FFCC0000' } };
                }
            });
            rowTotal.height = 22;

            // Anchos resumen
            wsResumen.getColumn(1).width = 6;
            wsResumen.getColumn(2).width = 50;
            wsResumen.getColumn(3).width = 18;
            wsResumen.getColumn(4).width = 22;
            wsResumen.getRow(1).height = 70;
            await agregarLogo(wsResumen, logos.ucla,     'png',  0, 0, 90, 70);
            await agregarLogo(wsResumen, logos.medicina, 'jpeg', 3, 0, 90, 70);

            // ============================================================
            // HOJAS DETALLE POR DIPLOMADO
            // ============================================================
            for (const diplomaName of Object.keys(grouped)) {
                const items     = grouped[diplomaName];
                const subTotal  = items.reduce((sum, r) => sum + (parseFloat(r.monto_usd) || 0), 0);
                let sheetName   = diplomaName.substring(0, 28).replace(/[\\/?*\[\]:]/g, '_');
                let uniqueName  = sheetName;
                let sheetIdx    = 1;
                while (wb.getWorksheet(uniqueName)) {
                    uniqueName = sheetName.substring(0, 26) + `_${sheetIdx}`;
                    sheetIdx++;
                }

                const ws = wb.addWorksheet(uniqueName);

                // Título
                ws.addRow([`TRÁMITES PENDIENTES: ${diplomaName.toUpperCase()}`]);
                ws.mergeCells('A1:I1');
                const wF1 = ws.getRow(1);
                wF1.getCell(1).font      = { bold: true, size: 13, color: { argb: AZUL } };
                wF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                wF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                wF1.height = 26;

                // Total del diplomado
                ws.addRow(['Total Flotante', subTotal]);
                const wF2 = ws.getRow(2);
                wF2.getCell(1).font = { bold: true, size: 11 };
                wF2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };
                wF2.getCell(2).font = { bold: true, size: 11, color: { argb: 'FFCC0000' } };
                wF2.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                wF2.getCell(2).numFmt = '"$"#,##0.00';

                // Generado
                ws.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
                ws.getRow(3).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };

                ws.addRow([]);

                // Encabezados
                ws.addRow(['CÉDULA', 'ESTUDIANTE', 'ORIGEN', 'MÉTODO', 'MONEDA', 'MONTO ORIG.', 'TASA', 'EQUIV. USD', 'OBSERVACIÓN']);
                const wF5 = ws.getRow(5);
                wF5.height = 25;
                wF5.eachCell((cell) => {
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                    cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border    = borderThin;
                });

                // Datos
                items.forEach((r, idx) => {
                    const usd = parseFloat(r.monto_usd) || 0;
                    const row = ws.addRow([
                        r.cedula, r.estudiante, r.origin, r.tipo_pago,
                        r.moneda, parseFloat(r.monto) || 0,
                        parseFloat(r.tasa) || 0, usd, r.observacion || ''
                    ]);

                    row.eachCell((cell, colNum) => {
                        cell.border = borderThin;
                        cell.font   = { size: 10 };
                        if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };

                        // Método de pago — color según tipo
                        if (colNum === 4) {
                            const metodo = (r.tipo_pago || '').toUpperCase();
                            if (metodo === 'ZELLE')      cell.font = { bold: true, size: 10, color: { argb: 'FF2E75B6' } };
                            if (metodo === 'BINANCE')    cell.font = { bold: true, size: 10, color: { argb: 'FFF0B90B' } };
                            if (metodo === 'PAGO MOVIL') cell.font = { bold: true, size: 10, color: { argb: 'FF198754' } };
                            if (metodo === 'EFECTIVO')   cell.font = { bold: true, size: 10, color: { argb: 'FFDC3545' } };
                        }

                        // Monto original y tasa
                        if (colNum === 6 || colNum === 7) {
                            cell.numFmt    = '#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                        }

                        // Equiv USD — verde
                        if (colNum === 8) {
                            cell.numFmt    = '"$"#,##0.00';
                            cell.alignment = { horizontal: 'right' };
                            cell.font      = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                            cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                        }

                        // Observación — naranja si tiene contenido
                        if (colNum === 9 && cell.value && cell.value !== '') {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                            cell.font = { italic: true, size: 9 };
                        }
                    });
                });

                // Fila total
                const rowSub = ws.addRow(['', 'SUBTOTAL FLOTANTE', '', '', '', '', '', subTotal, '']);
                rowSub.eachCell((cell, colNum) => {
                    cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                    cell.font   = { bold: true, size: 10 };
                    cell.border = borderThin;
                    if (colNum === 8) {
                        cell.numFmt    = '"$"#,##0.00';
                        cell.alignment = { horizontal: 'right' };
                        cell.font      = { bold: true, size: 11, color: { argb: 'FFCC0000' } };
                    }
                });

                // Anchos
                ws.getColumn(1).width = 14;
                ws.getColumn(2).width = 35;
                ws.getColumn(3).width = 14;
                ws.getColumn(4).width = 14;
                ws.getColumn(5).width = 10;
                ws.getColumn(6).width = 14;
                ws.getColumn(7).width = 12;
                ws.getColumn(8).width = 16;
                ws.getColumn(9).width = 40;

            // Congelar fila 5
                ws.getRow(1).height = 70;
                ws.views = [{ state: 'frozen', xSplit: 0, ySplit: 5, topLeftCell: 'A6' }];
                await agregarLogo(ws, logos.ucla,     'png',  0, 0, 90, 70);
                await agregarLogo(ws, logos.medicina, 'jpeg', 8, 0, 90, 70);
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
    
// --- FILTRO DE PERÍODO ---
    const periodoSelect = document.getElementById('filter_periodo');
    if (periodoSelect) {
        periodoSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('periodo_id', periodoSelect.value);
            window.location.href = url.toString();
        });
    }
    
// --- 9. EXPORTACIÓN PDF ---
    if (btnExportPdf) {
        btnExportPdf.onclick = (e) => {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(form)).toString();
            window.open(`${BASE_URL}/ManagerialPendingPayments/exportPdf?${params}`, '_blank');
        };
    }

})();