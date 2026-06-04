/**
 * MÓDULO: GESTIÓN GERENCIAL / AUDITORÍA BANCARIA
 * ARCHIVO: public/assets/js/managerial_bank_reconciliation.js
 * PROPÓSITO: Monitor de Auditoría Maestra para contrastar movimientos del CSV vs Sistema (Inscripciones y Cuotas).
 * VERSIÓN: 1.5.5 - Sincronización de KPIs: Se muestra conteo de registros conciliados exitosos (KPI Info).
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. REFERENCIAS AL DOM ---
    const formFilters = document.getElementById('form-reconciliation-filters');
    const resultsContainer = document.getElementById('reconciliation-results-container');
    const emptyState = document.getElementById('empty-state');
    const tableBody = document.getElementById('matrix-tbody');
    const btnExportExcel = document.getElementById('btn-export-audit-excel');

    // KPIs (Sincronizados con los IDs de la Vista Helium 1.5.5)
    const kpiBanco = document.getElementById('kpi-total-banco');
    const kpiConciliado = document.getElementById('kpi-total-conciliado');
    const kpiMontoHuerfano = document.getElementById('kpi-total-huerfano'); 
    const kpiConteoConciliado = document.getElementById('kpi-total-conteo-conciliado');

    // --- 2. EVENTOS PRINCIPALES ---
    
    if (formFilters) {
        // Al enviar el formulario, filtramos los datos
        formFilters.addEventListener('submit', async (e) => {
            e.preventDefault();
            await fetchReconciliationData();
        });

        // Al limpiar el formulario, reseteamos la vista y KPIs
        formFilters.addEventListener('reset', () => {
            setTimeout(() => { 
                resultsContainer.classList.add('d-none');
                if (emptyState) emptyState.classList.remove('d-none');
                resetKPIs();
            }, 100);
        });
    }

    // Evento para exportar a Excel (SheetJS)
    if (btnExportExcel) {
        btnExportExcel.addEventListener('click', () => handleExportExcel());
    }

    // --- 3. FUNCIONES DE CARGA Y RENDERIZADO ---

    /**
     * Obtiene los datos del Controlador aplicando los filtros maestros.
     */
    async function fetchReconciliationData() {
        const formData = new FormData(formFilters);
        const queryParams = new URLSearchParams(formData).toString();

        Swal.fire({
            title: 'Ejecutando Auditoría...',
            text: 'Contrastando TPago vs. Inscripciones y Cuotas.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const response = await fetch(`${BASE_URL}/managerial/bank-reconciliation/data?${queryParams}`);
            const result = await response.json();

            if (!result.ok) throw new Error(result.message || 'Error al obtener la matriz de auditoría.');

            // Actualizar KPIs y Tabla
            updateKPIs(result.kpis || {});
            renderTable(result.data || []);

            Swal.close();

            // Alternar visibilidad
            if (emptyState) emptyState.classList.add('d-none');
            resultsContainer.classList.remove('d-none');

        } catch (error) {
            console.error('Audit Error:', error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    /**
     * Renderiza la tabla basada en las 8 columnas de la Matriz Maestra.
     */
    function renderTable(data) {
        tableBody.innerHTML = '';

        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">No se encontraron registros para los criterios seleccionados.</td></tr>`;
            return;
        }

        data.forEach(row => {
            const fechaBanco = row.fecha_banco ? formatDate(row.fecha_banco) : '---';
            const montoBanco = formatMoney(row.monto_banco ?? 0);
            
            // Blindaje contra nulos antes de procesar badges
            const statusConciliacion = row.estatus_conciliacion || 'NO ENCONTRADO';
            const statusSistema = row.estatus_sistema || 'N/A';

            const tr = document.createElement('tr');
            tr.style.cursor = 'pointer'; 
            tr.className = 'animate__animated animate__fadeIn';
            
            tr.innerHTML = `
                <td class="ps-4 fw-medium text-dark">${fechaBanco}</td>
                <td class="fw-bold text-primary">${row.referencia_banco || '---'}</td>
                <td class="text-center text-muted small">${row.telefono_emisor || '---'}</td>
                <td class="text-end fw-bold text-dark">${montoBanco}</td>
                <td class="ps-4 fw-bold" style="font-size: 0.85rem;">${row.nombre_estudiante || '---'}</td>
                <td class="fw-medium small">${row.etapa_financiera || '---'}</td>
                <td class="text-center">${getSystemStatusBadge(statusSistema)}</td>
                <td class="text-center pe-4">${getReconciliationBadge(statusConciliacion)}</td>
            `;

            // EVENTO: Detalle vertical al hacer clic
            tr.addEventListener('click', () => showVerticalDetail(row));
            tableBody.appendChild(tr);
        });
    }

    /**
     * Muestra el detalle vertical con lógica de auditoría unificada.
     */
    function showVerticalDetail(row) {
        const statusConc = row.estatus_conciliacion || 'NO ENCONTRADO';
        const badge = getReconciliationBadge(statusConc);
        
        Swal.fire({
            title: '<h5 class="fw-bold text-primary mb-0">Detalle de Auditoría</h5>',
            html: `
                <div class="text-start mt-3 px-2" style="font-family: 'Inter', sans-serif;">
                    <div class="list-group list-group-flush border-top border-bottom">
                        <div class="list-group-item d-flex justify-content-between py-3">
                            <span class="text-muted small fw-bold text-uppercase">Referencia</span>
                            <span class="fw-bold text-primary">${row.referencia_banco || '---'}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between py-3">
                            <span class="text-muted small fw-bold text-uppercase">Monto Banco</span>
                            <span class="fw-bold text-dark">${formatMoney(row.monto_banco ?? 0)}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between py-3">
                            <span class="text-muted small fw-bold text-uppercase">Estudiante</span>
                            <span class="fw-bold text-dark text-end">${row.nombre_estudiante || '---'}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between py-3">
                            <span class="text-muted small fw-bold text-uppercase">Concepto</span>
                            <span class="badge bg-light text-dark border">${row.etapa_financiera || 'HUÉRFANO'}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between py-3">
                            <span class="text-muted small fw-bold text-uppercase">Estatus Sistema</span>
                            <span class="fw-medium">${row.estatus_sistema || '---'}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between py-3 bg-light">
                            <span class="text-muted small fw-bold text-uppercase">Cruce Bancario</span>
                            <span>${badge}</span>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Cerrar Detalle',
            confirmButtonColor: '#0d6efd',
            customClass: { popup: 'rounded-4 shadow-lg border-0' }
        });
    }

    /**
     * EXPORTACIÓN A EXCEL (SHEETJS)
     */
    // ============================================================
// REEMPLAZA COMPLETO la función:
// function handleExportExcel() { ... }
// EN: public/assets/js/managerial_bank_reconciliation.js
// ============================================================

    async function handleExportExcel() {
        if (tableBody.children.length === 0 || tableBody.innerHTML.includes('No se encontraron')) {
            Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
            return;
        }

        Swal.fire({ title: 'Generando Excel...', text: 'Preparando auditoría bancaria...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            // Fetch data completa
            const formData   = new FormData(formFilters);
            const queryParams = new URLSearchParams(formData).toString();
            const resp       = await fetch(`${BASE_URL}/managerial/bank-reconciliation/data?${queryParams}`);
            const result     = await resp.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
                return;
            }

            // --- Nombre del archivo con fecha invertida y hora+segundos ---
            const now  = new Date();
            const yy   = now.getFullYear();
            const mm   = String(now.getMonth() + 1).padStart(2, '0');
            const dd   = String(now.getDate()).padStart(2, '0');
            const hh   = String(now.getHours()).padStart(2, '0');
            const min  = String(now.getMinutes()).padStart(2, '0');
            const ss   = String(now.getSeconds()).padStart(2, '0');
            const fileName = `Auditoria_Bancaria_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

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

            const data = result.data;
            const kpis = result.kpis || {};

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
            // HOJA 1: RESUMEN KPIs
            // ============================================================
            const wsKpi = wb.addWorksheet('Resumen KPIs');

            wsKpi.addRow(['AUDITORÍA BANCARIA — RESUMEN DE KPIs']);
            wsKpi.mergeCells('A1:D1');
            const kF1 = wsKpi.getRow(1);
            kF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            kF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            kF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            kF1.height = 28;

            wsKpi.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsKpi.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsKpi.addRow([]);

            // KPIs
            const kpiRows = [
                ['Total Banco (Bs.)',        parseFloat(kpis.total_banco      ?? 0), AZUL_TT, 'FF1F4E79'],
                ['Total Conciliado (Bs.)',   parseFloat(kpis.total_conciliado ?? 0), VERDE,   'FF1E6B24'],
                ['Total Huérfano (Bs.)',     parseFloat(kpis.total_huerfano   ?? 0), NARANJA, 'FFCC0000'],
                ['Registros Conciliados',    parseInt(kpis.conteo_conciliados ?? 0), AZUL_TT, 'FF1F4E79'],
            ];

            kpiRows.forEach(([label, val, bgColor, fontColor]) => {
                const row = wsKpi.addRow([label, val]);
                row.getCell(1).font   = { bold: true, size: 11 };
                row.getCell(1).fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                row.getCell(1).border = borderThin;
                row.getCell(2).font   = { bold: true, size: 12, color: { argb: fontColor } };
                row.getCell(2).fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: bgColor } };
                row.getCell(2).border = borderThin;
                if (typeof val === 'number' && label.includes('Bs')) {
                    row.getCell(2).numFmt = '"Bs."#,##0.00';
                }
                row.height = 22;
            });

            wsKpi.getColumn(1).width = 30;
            wsKpi.getColumn(2).width = 22;

            // ============================================================
            // HOJA 2: MATRIZ MAESTRA COMPLETA
            // ============================================================
            const wsMat = wb.addWorksheet('Auditoría Maestra');

            wsMat.addRow(['AUDITORÍA BANCARIA — MATRIZ MAESTRA']);
            wsMat.mergeCells('A1:H1');
            const mF1 = wsMat.getRow(1);
            mF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            mF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            mF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            mF1.height = 28;

            wsMat.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsMat.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsMat.addRow([]);
            wsMat.addRow([]);

            wsMat.addRow(['FECHA BANCO', 'REFERENCIA', 'TELÉFONO EMISOR', 'MONTO BANCO (Bs.)', 'ESTUDIANTE', 'ETAPA FINANCIERA', 'ESTATUS SISTEMA', 'CRUCE BANCARIO']);
            const mF5 = wsMat.getRow(5);
            mF5.height = 25;
            mF5.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                cell.border    = borderThin;
            });

            // Separar conciliados y huérfanos para resumen
            let totalConciliado = 0;
            let totalHuerfano   = 0;

            data.forEach((row, idx) => {
                const esConciliado = (row.estatus_conciliacion || '').toUpperCase().includes('CONCILIADO');
                const monto = parseFloat(row.monto_banco ?? 0);
                if (esConciliado) totalConciliado += monto;
                else              totalHuerfano   += monto;

                const dataRow = wsMat.addRow([
                    row.fecha_banco     ? formatDate(row.fecha_banco) : '---',
                    row.referencia_banco || '---',
                    row.telefono_emisor  || '---',
                    monto,
                    row.nombre_estudiante || '---',
                    row.etapa_financiera  || '---',
                    row.estatus_sistema   || 'N/A',
                    esConciliado ? 'CONCILIADO' : 'NO ENCONTRADO'
                ]);

                dataRow.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };

                    // Monto banco
                    if (colNum === 4) {
                        cell.numFmt    = '"Bs."#,##0.00';
                        cell.alignment = { horizontal: 'right' };
                        cell.font      = { bold: true, size: 10 };
                    }

                    // Estatus sistema
                    if (colNum === 7) {
                        const val = (row.estatus_sistema || '').toUpperCase();
                        if (val.includes('APROBADO'))  { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                        if (val.includes('PENDIENTE')) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } }; cell.font = { bold: true, size: 10 }; }
                        if (val.includes('RECHAZADO')) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; }
                        cell.alignment = { horizontal: 'center' };
                    }

                    // Cruce bancario
                    if (colNum === 8) {
                        if (esConciliado) {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                            cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                        } else {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } };
                            cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } };
                        }
                        cell.alignment = { horizontal: 'center' };
                    }
                });
            });

            // Fila totales
            const rowTot = wsMat.addRow(['TOTALES', '', '', parseFloat(kpis.total_banco ?? 0), '', '', '', '']);
            rowTot.eachCell((cell, colNum) => {
                cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font   = { bold: true, size: 11 };
                cell.border = borderThin;
                if (colNum === 4) { cell.numFmt = '"Bs."#,##0.00'; cell.alignment = { horizontal: 'right' }; }
            });

            // Anchos
            wsMat.getColumn(1).width = 14;
            wsMat.getColumn(2).width = 20;
            wsMat.getColumn(3).width = 16;
            wsMat.getColumn(4).width = 18;
            wsMat.getColumn(5).width = 35;
            wsMat.getColumn(6).width = 22;
            wsMat.getColumn(7).width = 16;
            wsMat.getColumn(8).width = 16;
            wsMat.views = [{ state: 'frozen', xSplit: 0, ySplit: 5, topLeftCell: 'A6' }];

            // ============================================================
            // HOJA 3: HUÉRFANOS (No Conciliados)
            // ============================================================
            const wsHuerfanos = wb.addWorksheet('Huérfanos');

            wsHuerfanos.addRow(['REGISTROS NO CONCILIADOS (HUÉRFANOS)']);
            wsHuerfanos.mergeCells('A1:H1');
            const hF1 = wsHuerfanos.getRow(1);
            hF1.getCell(1).font      = { bold: true, size: 13, color: { argb: 'FFCC0000' } };
            hF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } };
            hF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            hF1.height = 26;

            wsHuerfanos.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsHuerfanos.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsHuerfanos.addRow([]);

            wsHuerfanos.addRow(['FECHA BANCO', 'REFERENCIA', 'TELÉFONO EMISOR', 'MONTO BANCO (Bs.)', 'ESTUDIANTE', 'ETAPA FINANCIERA', 'ESTATUS SISTEMA', 'CRUCE BANCARIO']);
            const hF4 = wsHuerfanos.getRow(4);
            hF4.height = 25;
            hF4.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFCC0000' } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });

            const huerfanos = data.filter(r => !(r.estatus_conciliacion || '').toUpperCase().includes('CONCILIADO'));
            huerfanos.forEach((row, idx) => {
                const monto   = parseFloat(row.monto_banco ?? 0);
                const dataRow = wsHuerfanos.addRow([
                    row.fecha_banco ? formatDate(row.fecha_banco) : '---',
                    row.referencia_banco || '---',
                    row.telefono_emisor  || '---',
                    monto,
                    row.nombre_estudiante || '---',
                    row.etapa_financiera  || '---',
                    row.estatus_sistema   || 'N/A',
                    'NO ENCONTRADO'
                ]);
                dataRow.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF5F5' } };
                    if (colNum === 4) { cell.numFmt = '"Bs."#,##0.00'; cell.alignment = { horizontal: 'right' }; cell.font = { bold: true, size: 10 }; }
                    if (colNum === 8) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; cell.alignment = { horizontal: 'center' }; }
                });
            });

            // Total huérfanos
            const rowHuerfTot = wsHuerfanos.addRow(['TOTAL HUÉRFANO', '', '', totalHuerfano, '', '', '', '']);
            rowHuerfTot.eachCell((cell, colNum) => {
                cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font   = { bold: true, size: 11 };
                cell.border = borderThin;
                if (colNum === 4) { cell.numFmt = '"Bs."#,##0.00'; cell.alignment = { horizontal: 'right' }; }
            });

            for (let c = 1; c <= 8; c++) wsHuerfanos.getColumn(c).width = wsMat.getColumn(c).width;
            wsHuerfanos.views = [{ state: 'frozen', xSplit: 0, ySplit: 4, topLeftCell: 'A5' }];


            // ============================================================
            // HOJA 4: CONCILIADOS
            // ============================================================
            const wsConciliados = wb.addWorksheet('Conciliados');

            wsConciliados.addRow(['REGISTROS CONCILIADOS EXITOSAMENTE']);
            wsConciliados.mergeCells('A1:H1');
            const cF1 = wsConciliados.getRow(1);
            cF1.getCell(1).font      = { bold: true, size: 13, color: { argb: 'FF1E6B24' } };
            cF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
            cF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            cF1.height = 26;

            wsConciliados.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsConciliados.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsConciliados.addRow([]);

            wsConciliados.addRow(['FECHA BANCO', 'REFERENCIA', 'TELÉFONO EMISOR', 'MONTO BANCO (Bs.)', 'ESTUDIANTE', 'ETAPA FINANCIERA', 'ESTATUS SISTEMA', 'CRUCE BANCARIO']);
            const cF4 = wsConciliados.getRow(4);
            cF4.height = 25;
            cF4.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E6B24' } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });

            const conciliados = data.filter(r => (r.estatus_conciliacion || '').toUpperCase().includes('CONCILIADO'));
            conciliados.forEach((row, idx) => {
                const monto   = parseFloat(row.monto_banco ?? 0);
                const dataRow = wsConciliados.addRow([
                    row.fecha_banco ? formatDate(row.fecha_banco) : '---',
                    row.referencia_banco  || '---',
                    row.telefono_emisor   || '---',
                    monto,
                    row.nombre_estudiante || '---',
                    row.etapa_financiera  || '---',
                    row.estatus_sistema   || 'N/A',
                    'CONCILIADO'
                ]);
                dataRow.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FFF0' } };
                    if (colNum === 4) { cell.numFmt = '"Bs."#,##0.00'; cell.alignment = { horizontal: 'right' }; cell.font = { bold: true, size: 10 }; }
                    if (colNum === 7) {
                        const val = (row.estatus_sistema || '').toUpperCase();
                        if (val.includes('APROBADO'))  { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                        if (val.includes('PENDIENTE')) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } }; cell.font = { bold: true, size: 10 }; }
                        cell.alignment = { horizontal: 'center' };
                    }
                    if (colNum === 8) {
                        cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };
                        cell.font      = { bold: true, size: 10, color: { argb: 'FF1E6B24' } };
                        cell.alignment = { horizontal: 'center' };
                    }
                });
            });

            // Total conciliados
            const totalConc = conciliados.reduce((sum, r) => sum + parseFloat(r.monto_banco ?? 0), 0);
            const rowConcTot = wsConciliados.addRow(['TOTAL CONCILIADO', '', '', totalConc, '', '', '', '']);
            rowConcTot.eachCell((cell, colNum) => {
                cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font   = { bold: true, size: 11 };
                cell.border = borderThin;
                if (colNum === 4) { cell.numFmt = '"Bs."#,##0.00'; cell.alignment = { horizontal: 'right' }; cell.font = { bold: true, size: 12, color: { argb: 'FF1E6B24' } }; }
            });
            rowConcTot.height = 22;

            for (let c = 1; c <= 8; c++) wsConciliados.getColumn(c).width = wsMat.getColumn(c).width;
            wsConciliados.views = [{ state: 'frozen', xSplit: 0, ySplit: 4, topLeftCell: 'A5' }];

// Agregar logos a todas las hojas — fila 1 con altura aumentada
            [wsKpi, wsMat, wsHuerfanos, wsConciliados].forEach(ws => {
                ws.getRow(1).height = 70;
            });

            await agregarLogo(wsKpi,         logos.ucla,     'png',  0,   0, 90, 70);
            await agregarLogo(wsKpi,         logos.medicina, 'jpeg', 3,   0, 90, 70);
            await agregarLogo(wsMat,         logos.ucla,     'png',  0,   0, 90, 70);
            await agregarLogo(wsMat,         logos.medicina, 'jpeg', 7,   0, 90, 70);
            await agregarLogo(wsHuerfanos,   logos.ucla,     'png',  0,   0, 90, 70);
            await agregarLogo(wsHuerfanos,   logos.medicina, 'jpeg', 7,   0, 90, 70);
            await agregarLogo(wsConciliados, logos.ucla,     'png',  0,   0, 90, 70);
            await agregarLogo(wsConciliados, logos.medicina, 'jpeg', 7,   0, 90, 70);



            // Descargar
            const buffer = await wb.xlsx.writeBuffer();
            const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            saveAs(blob, fileName);

            Swal.close();

        } catch (err) {
            console.error('Error ExcelJS:', err);
            Swal.fire('Error', err.message, 'error');
        }
    }
    /**
     * ACTUALIZACIÓN DE KPIs
     * Sincronizado con ManagerialBankReconciliationController 1.5.1
     */
    function updateKPIs(kpis) {
        kpiBanco.innerText = formatMoney(kpis.total_banco ?? 0);
        kpiConciliado.innerText = formatMoney(kpis.total_conciliado ?? 0);
        kpiMontoHuerfano.innerText = formatMoney(kpis.total_huerfano ?? 0);
        
        // Se actualiza el conteo de registros exitosos (KPI Info Azul)
        kpiConteoConciliado.innerText = kpis.conteo_conciliados ?? 0; 
    }

    function resetKPIs() {
        [kpiBanco, kpiConciliado, kpiMontoHuerfano].forEach(el => el.innerText = 'Bs. 0,00');
        kpiConteoConciliado.innerText = '0';
    }

    // --- 4. HELPERS ---

    function formatMoney(amount) {
        return 'Bs. ' + parseFloat(amount).toLocaleString('es-VE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }

    function formatDate(dateString) {
        if(!dateString) return '---';
        const p = dateString.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : dateString;
    }

    /**
     * Blindaje contra null/undefined mediante (status || '')
     */
    function getReconciliationBadge(status) {
        const val = (status || '').toString().toUpperCase();
        if (val.includes('CONCILIADO')) {
            return `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                        <i class="bi bi-check-all"></i> CONCILIADO
                    </span>`;
        }
        return `<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">
                    <i class="bi bi-question-circle"></i> NO ENCONTRADO
                </span>`;
    }

    /**
     * Blindaje contra null/undefined mediante (status || '')
     */
    function getSystemStatusBadge(status) {
        const val = (status || '').toString().toUpperCase();
        if (val === 'N/A' || val === '') return `<span class="text-muted small">---</span>`;
        
        if (val.includes('APROBADO')) return `<span class="text-success fw-bold small">✅ APROBADO</span>`;
        if (val.includes('PENDIENTE')) return `<span class="text-warning fw-bold small">⏳ PENDIENTE</span>`;
        if (val.includes('RECHAZADO')) return `<span class="text-danger fw-bold small">❌ RECHAZADO</span>`;
        
        return `<span class="text-muted small">${val}</span>`;
    }

    // Carga inicial automática
    fetchReconciliationData();

});