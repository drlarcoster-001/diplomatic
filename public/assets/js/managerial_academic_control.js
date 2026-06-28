/**
 * MÓDULO: GESTIÓN GERENCIAL / CONTROL ACADÉMICO
 * ARCHIVO: public/assets/js/managerial_academic_control.js
 * PROPÓSITO: Gestión dinámica de la matriz académica 360°, búsqueda reactiva y trazabilidad total.
 * VERSIÓN: 1.2.0 - FIX: Logos ExcelJS + for...of para await dentro de loops.
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. SELECTORES DE INTERFAZ
    const filterForm = document.getElementById('form-academic-filters');
    const searchInput = document.getElementById('dynamic-student-search');
    const offeringSelect = document.getElementById('filter_offering');
    const groupSelect = document.getElementById('filter_group');
    const statusSelect = document.getElementById('filter_status');
    
    const resultsContainer = document.getElementById('report-results-container');
    const tableBody = document.getElementById('matrix-tbody');
    const emptyState = document.getElementById('empty-state');
    
    const paginationControls = document.getElementById('pagination-controls');
    const paginationInfo = document.getElementById('pagination-info');

    const btnExportExcel = document.getElementById('btn-export-excel');
    const btnExportPdf = document.getElementById('btn-export-pdf');

    let debounceTimer;

    const debounceSearch = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { fetchAcademicData(1); }, 400); 
    };

    offeringSelect.addEventListener('change', async () => {
        const offeringId = offeringSelect.value;
        groupSelect.innerHTML = '<option value="ALL">Todos los Grupos</option>';
        
        if (offeringId === 'ALL') {
            groupSelect.disabled = true;
            fetchAcademicData(1);
            return;
        }

        try {
            const response = await fetch(`${BASE_URL}/managerial/academic-control/groups?offering_id=${offeringId}`);
            const groups = await response.json();

            if (groups && groups.length > 0) {
                groups.forEach(g => {
                    const option = document.createElement('option');
                    option.value = g.id;
                    option.textContent = g.name;
                    groupSelect.appendChild(option);
                });
                groupSelect.disabled = false;
            } else {
                groupSelect.disabled = true;
            }
            fetchAcademicData(1);
        } catch (error) {
            console.error('Error al cargar grupos:', error);
        }
    });

    const fetchAcademicData = async (page = 1) => {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        params.append('page', page.toString());
        resultsContainer.classList.add('opacity-50');

        try {
            const response = await fetch(`${BASE_URL}/managerial/academic-control/data?${params.toString()}`);
            const result = await response.json();

            if (result.ok) {
                renderTable(result.data, result.pagination); 
                renderPagination(result.pagination);
                
                if (result.data.length > 0) {
                    resultsContainer.classList.remove('d-none');
                    emptyState.classList.add('d-none');
                } else {
                    resultsContainer.classList.add('d-none');
                    emptyState.classList.remove('d-none');
                }
            }
        } catch (error) {
            console.error('Error Crítico AJAX:', error);
        } finally {
            resultsContainer.classList.remove('opacity-50');
        }
    };

    const renderTable = (data, p) => {
        tableBody.innerHTML = '';
        const currentPage = (p && p.page) ? parseInt(p.page) : 1;
        const startNumber = ((currentPage - 1) * 25) + 1;
        if (!data || data.length === 0) return;

        data.forEach((item, index) => {
            let badgeFicha = item.estatus_ficha === 'ACTIVO' ? 'bg-success' : (item.estatus_ficha === 'SUSPENDIDO' ? 'bg-danger' : 'bg-secondary');
            let badgeMatricula = item.estatus_matricula === 'CURSANDO' ? 'bg-primary' : (item.estatus_matricula === 'APROBADO' ? 'bg-success' : 'bg-dark');

            const tr = document.createElement('tr');
            tr.className = 'animate__animated animate__fadeIn';
            tr.innerHTML = `
                <td class="ps-4 fw-bold text-muted" style="font-size: 11px;">${startNumber + index}</td>
                <td class="ps-4 fw-bold text-dark" style="font-size: 13px;">${item.participante}</td>
                <td class="text-center small">${item.cedula}</td>
                <td class="text-center small">${item.diplomado}</td>
                <td class="text-center small fw-medium text-primary">${item.nombre_grupo}</td>
                <td class="text-center small" style="font-size: 10px;">${item.trazabilidad_adm_fin}</td>
                <td class="text-center"><code class="small" style="font-size: 11px;">${item.codigo_estudiante}</code></td>
                <td class="text-center"><span class="badge rounded-pill bg-light text-dark border">${item.nro_const_inscripcion}</span></td>
                <td class="text-center"><span class="badge rounded-pill bg-light text-dark border">${item.nro_const_estudios}</span></td>
                <td class="text-center">
                    <span class="badge ${badgeFicha} rounded-pill px-2 text-uppercase" style="font-size: 10px;">${item.estatus_ficha}</span>
                </td>
                <td class="text-center pe-4">
                    <span class="badge ${badgeMatricula} rounded-pill px-2 text-uppercase" style="font-size: 10px;">${item.estatus_matricula}</span>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    };

    const renderPagination = (p) => {
        paginationInfo.innerHTML = `Página <b>${p.page}</b> de <b>${p.pages}</b> <span class="text-muted">(${p.total_records} registros)</span>`;
        let html = '';
        for (let i = 1; i <= p.pages; i++) {
            html += `<button class="btn btn-sm ${i === p.page ? 'btn-primary active' : 'btn-outline-primary'} btn-page mx-1 shadow-sm" data-page="${i}">${i}</button>`;
        }
        paginationControls.innerHTML = html;
        document.querySelectorAll('.btn-page').forEach(btn => {
            btn.addEventListener('click', () => fetchAcademicData(parseInt(btn.dataset.page)));
        });
    };

    // --- LISTENERS ---
    searchInput.addEventListener('input', debounceSearch);
    groupSelect.addEventListener('change', () => fetchAcademicData(1));
    statusSelect.addEventListener('change', () => fetchAcademicData(1));
    filterForm.addEventListener('submit', (e) => { e.preventDefault(); fetchAcademicData(1); });

    // --- EXPORTACIÓN EXCEL CON LOGOS ---
    btnExportExcel.addEventListener('click', async () => {
        if (window.Swal) Swal.fire({ title: 'Generando Excel...', text: 'Preparando control académico...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fParams = new URLSearchParams(new FormData(filterForm));
            fParams.append('limit', -1);
            const resp   = await fetch(`${BASE_URL}/managerial/academic-control/data?${fParams.toString()}`);
            const result = await resp.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                if (window.Swal) Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
                return;
            }

            const now  = new Date();
            const yy   = now.getFullYear();
            const mm   = String(now.getMonth() + 1).padStart(2, '0');
            const dd   = String(now.getDate()).padStart(2, '0');
            const hh   = String(now.getHours()).padStart(2, '0');
            const min  = String(now.getMinutes()).padStart(2, '0');
            const ss   = String(now.getSeconds()).padStart(2, '0');
            const fileName = `Control_Academico_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

            const AZUL    = 'FF2E75B6';
            const BLANCO  = 'FFFFFFFF';
            const AMARILLO= 'FFFFF2CC';
            const VERDE   = 'FFE2EFDA';
            const NARANJA = 'FFFCE4D6';
            const GRIS_CL = 'FFF9F9F9';
            const GRIS_HD = 'FFF2F2F2';
            const AZUL_TT = 'FFDCE6F1';
            const ROJO_CL = 'FFFFD7D7';
            const AZUL_OS = 'FF1F4E79';

            const borderThin = {
                top:    { style: 'thin', color: { argb: 'FFD9D9D9' } },
                left:   { style: 'thin', color: { argb: 'FFD9D9D9' } },
                bottom: { style: 'thin', color: { argb: 'FFD9D9D9' } },
                right:  { style: 'thin', color: { argb: 'FFD9D9D9' } }
            };

            const grouped = {};
            result.data.forEach(r => {
                if (!grouped[r.diplomado]) grouped[r.diplomado] = [];
                grouped[r.diplomado].push(r);
            });

            // Cargar logos
            const logosResp = await fetch(`${BASE_URL}/assets/logos/base64`);
            const logos     = await logosResp.json();

            const wb = new ExcelJS.Workbook();
            wb.creator = 'Diplomatic';
            wb.created = now;

            const agregarLogo = async (ws, base64, extension, col, row, width, height) => {
                if (!base64) return;
                const imageId = wb.addImage({ base64, extension });
                ws.addImage(imageId, { tl: { col, row }, ext: { width, height } });
            };

            // ============================================================
            // HOJA 1: RESUMEN POR DIPLOMADO
            // ============================================================
            const wsResumen = wb.addWorksheet('Resumen Diplomados');

            wsResumen.addRow(['CONTROL ACADÉMICO — RESUMEN POR DIPLOMADO']);
            wsResumen.mergeCells('A1:G1');
            const rF1 = wsResumen.getRow(1);
            rF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            rF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            rF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rF1.height = 70;

            const periodoLabelAc = document.getElementById('filter_periodo')?.options[document.getElementById('filter_periodo')?.selectedIndex]?.text || 'Todos los períodos';
            wsResumen.addRow([`Período: ${periodoLabelAc} — Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsResumen.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsResumen.addRow([]);

            wsResumen.addRow(['#', 'DIPLOMADO', 'INSCRITOS', 'CURSANDO', 'APROBADO', 'OTROS', 'TOTAL']);
            const rF4 = wsResumen.getRow(4);
            rF4.height = 25;
            rF4.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });

            let totalInscritos = 0, totalCursando = 0, totalAprobado = 0, totalOtros = 0, resumenIdx = 1;

            Object.keys(grouped).forEach((diplomaName, idx) => {
                const items    = grouped[diplomaName];
                const cursando = items.filter(r => r.estatus_matricula === 'CURSANDO').length;
                const aprobado = items.filter(r => r.estatus_matricula === 'APROBADO').length;
                const otros    = items.length - cursando - aprobado;
                totalInscritos += items.length; totalCursando += cursando; totalAprobado += aprobado; totalOtros += otros;

                const row = wsResumen.addRow([resumenIdx++, diplomaName, items.length, cursando, aprobado, otros, items.length]);
                row.eachCell((cell, colNum) => {
                    cell.border = borderThin; cell.font = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                    if (colNum === 3 || colNum === 7) { cell.alignment = { horizontal: 'center' }; cell.font = { bold: true, size: 10 }; }
                    if (colNum === 4) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } }; cell.alignment = { horizontal: 'center' }; }
                    if (colNum === 5) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; cell.alignment = { horizontal: 'center' }; }
                    if (colNum === 6 && cell.value > 0) { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: NARANJA } }; cell.alignment = { horizontal: 'center' }; }
                });
            });

            const rowTotal = wsResumen.addRow(['', 'TOTAL GENERAL', totalInscritos, totalCursando, totalAprobado, totalOtros, totalInscritos]);
            rowTotal.eachCell((cell, colNum) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } };
                cell.font = { bold: true, size: 11 }; cell.border = borderThin;
                if (colNum >= 3) cell.alignment = { horizontal: 'center' };
            });
            rowTotal.height = 22;

            wsResumen.getColumn(1).width = 6;
            wsResumen.getColumn(2).width = 50;
            for (let c = 3; c <= 7; c++) wsResumen.getColumn(c).width = 14;
            wsResumen.views = [{ state: 'frozen', xSplit: 0, ySplit: 4 }];
            await agregarLogo(wsResumen, logos.ucla,     'png',  0, 0, 90, 70);
            await agregarLogo(wsResumen, logos.medicina, 'jpeg', 6, 0, 90, 70);

            // ============================================================
            // HOJA 2: MATRIZ GENERAL
            // ============================================================
            const wsMatrix = wb.addWorksheet('Matriz General');

            wsMatrix.addRow(['CONTROL ACADÉMICO — MATRIZ GENERAL DE ESTUDIANTES']);
            wsMatrix.mergeCells('A1:K1');
            const mF1 = wsMatrix.getRow(1);
            mF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
            mF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            mF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            mF1.height = 70;

            wsMatrix.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsMatrix.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsMatrix.addRow([]);
            wsMatrix.addRow([]);

            wsMatrix.addRow(['N°', 'PARTICIPANTE', 'CÉDULA', 'DIPLOMADO', 'GRUPO', 'TRAZABILIDAD ADM/FIN', 'CÓDIGO ESTUDIANTE', 'CONST. INSCRIPCIÓN', 'CONST. ESTUDIOS', 'ESTATUS FICHA', 'ESTATUS MATRÍCULA']);
            const mF5 = wsMatrix.getRow(5);
            mF5.height = 30;
            mF5.eachCell((cell) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                cell.border = borderThin;
            });

            result.data.forEach((r, idx) => {
                const row = wsMatrix.addRow([idx + 1, r.participante, r.cedula, r.diplomado, r.nombre_grupo, r.trazabilidad_adm_fin, r.codigo_estudiante, r.nro_const_inscripcion, r.nro_const_estudios, r.estatus_ficha, r.estatus_matricula]);
                row.eachCell((cell, colNum) => {
                    cell.border = borderThin; cell.font = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                    if (colNum === 1 || colNum >= 7) cell.alignment = { horizontal: 'center' };
                    if (colNum === 10) {
                        const val = (r.estatus_ficha || '').toUpperCase();
                        if (val === 'ACTIVO')     { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                        if (val === 'SUSPENDIDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; }
                        cell.alignment = { horizontal: 'center' };
                    }
                    if (colNum === 11) {
                        const val = (r.estatus_matricula || '').toUpperCase();
                        if (val === 'CURSANDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } }; cell.font = { bold: true, size: 10, color: { argb: 'FF' + AZUL_OS } }; }
                        if (val === 'APROBADO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                        cell.alignment = { horizontal: 'center' };
                    }
                });
            });

            wsMatrix.getColumn(1).width = 6; wsMatrix.getColumn(2).width = 35; wsMatrix.getColumn(3).width = 14;
            wsMatrix.getColumn(4).width = 35; wsMatrix.getColumn(5).width = 16; wsMatrix.getColumn(6).width = 25;
            wsMatrix.getColumn(7).width = 22; wsMatrix.getColumn(8).width = 18; wsMatrix.getColumn(9).width = 18;
            wsMatrix.getColumn(10).width = 16; wsMatrix.getColumn(11).width = 16;
            wsMatrix.views = [{ state: 'frozen', xSplit: 2, ySplit: 5, topLeftCell: 'C6' }];
            await agregarLogo(wsMatrix, logos.ucla,     'png',  0,  0, 90, 70);
            await agregarLogo(wsMatrix, logos.medicina, 'jpeg', 10, 0, 90, 70);

            // ============================================================
            // HOJAS POR DIPLOMADO
            // ============================================================
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

                ws.addRow([`CONTROL ACADÉMICO: ${diplomaName.toUpperCase()}`]);
                ws.mergeCells('A1:K1');
                const dF1 = ws.getRow(1);
                dF1.getCell(1).font      = { bold: true, size: 13, color: { argb: AZUL } };
                dF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
                dF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                dF1.height = 70;

                const cursando = items.filter(r => r.estatus_matricula === 'CURSANDO').length;
                const aprobado = items.filter(r => r.estatus_matricula === 'APROBADO').length;
                ws.addRow([`Inscritos: ${items.length}  |  Cursando: ${cursando}  |  Aprobado: ${aprobado}`]);
                const dF2 = ws.getRow(2);
                dF2.getCell(1).font = { bold: true, size: 10, color: { argb: 'FF' + AZUL_OS } };
                dF2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } };

                ws.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
                ws.getRow(3).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
                ws.addRow([]);

                ws.addRow(['N°', 'PARTICIPANTE', 'CÉDULA', 'GRUPO', 'TRAZABILIDAD ADM/FIN', 'CÓDIGO ESTUDIANTE', 'CONST. INSCRIPCIÓN', 'CONST. ESTUDIOS', 'ESTATUS FICHA', 'ESTATUS MATRÍCULA']);
                const dF5 = ws.getRow(5);
                dF5.height = 25;
                dF5.eachCell((cell) => {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                    cell.font = { bold: true, color: { argb: BLANCO }, size: 10 };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border = borderThin;
                });

                items.forEach((r, idx) => {
                    const row = ws.addRow([idx + 1, r.participante, r.cedula, r.nombre_grupo, r.trazabilidad_adm_fin, r.codigo_estudiante, r.nro_const_inscripcion, r.nro_const_estudios, r.estatus_ficha, r.estatus_matricula]);
                    row.eachCell((cell, colNum) => {
                        cell.border = borderThin; cell.font = { size: 10 };
                        if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };
                        if (colNum === 1 || colNum >= 6) cell.alignment = { horizontal: 'center' };
                        if (colNum === 9) {
                            const val = (r.estatus_ficha || '').toUpperCase();
                            if (val === 'ACTIVO')     { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                            if (val === 'SUSPENDIDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; }
                            cell.alignment = { horizontal: 'center' };
                        }
                        if (colNum === 10) {
                            const val = (r.estatus_matricula || '').toUpperCase();
                            if (val === 'CURSANDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL_TT } }; cell.font = { bold: true, size: 10, color: { argb: 'FF' + AZUL_OS } }; }
                            if (val === 'APROBADO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                            cell.alignment = { horizontal: 'center' };
                        }
                    });
                });

                ws.getColumn(1).width = 6; ws.getColumn(2).width = 35; ws.getColumn(3).width = 14;
                ws.getColumn(4).width = 16; ws.getColumn(5).width = 25; ws.getColumn(6).width = 22;
                ws.getColumn(7).width = 18; ws.getColumn(8).width = 18; ws.getColumn(9).width = 16;
                ws.getColumn(10).width = 16;
                ws.views = [{ state: 'frozen', xSplit: 2, ySplit: 5, topLeftCell: 'C6' }];
                await agregarLogo(ws, logos.ucla,     'png',  0, 0, 90, 70);
                await agregarLogo(ws, logos.medicina, 'jpeg', 9, 0, 90, 70);
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
    });

    // Exportación PDF
    btnExportPdf.addEventListener('click', () => {
        const params = new URLSearchParams(new FormData(filterForm));
        window.open(`${BASE_URL}/managerial/academic-control/exportPdf?${params.toString()}`, '_blank');
    });

    // Reiniciar filtros
    filterForm.addEventListener('reset', () => {
        setTimeout(() => {
            groupSelect.innerHTML = '<option value="ALL">Todos los Grupos</option>';
            groupSelect.disabled = true;
            fetchAcademicData(1);
        }, 10);
    });

    // Carga inicial
    fetchAcademicData(1);
});