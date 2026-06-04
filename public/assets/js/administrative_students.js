/**
 * MÓDULO: ADMINISTRATIVO / ESTUDIANTES
 * ARCHIVO: public/assets/js/administrative_students.js
 * PROPÓSITO: Lógica para Filtros y Ficha de Expediente (Limpiado de Cohorte).
 * VERSIÓN: 1.1.5
 */

let currentStudentsData = [];

$(document).ready(function() {
    // Carga inicial
    loadStudents();

    // 1. Buscador con retraso de 300ms para no saturar el servidor
    let timeout = null;
    $('#search-text').on('input', function() { 
        clearTimeout(timeout); 
        timeout = setTimeout(loadStudents, 300); 
    });

    // 2. CORRECCIÓN: Escuchar cambios en TODOS los selectores
    $('#filter-status, #filter-diplomado, #filter-docs, #filter-verified').on('change', function() {
    loadStudents();
});

    // 3. Botón Limpiar: Resetea el formulario completo y recarga
    $('#btn-clear-filters').on('click', function() { 
        $('#filter-form-students')[0].reset(); 
        loadStudents(); 
    });

    // 4. Click en fila para ver expediente
    $('#studentsTableBody').on('click', 'tr.student-row', function(e) {
        if (!$(e.target).closest('.action-eye-icon').length) {
            viewProfile($(this).data('id'));
        }
    });
});


function loadStudents() {
    // Capturamos los valores actuales de los 4 filtros
    const filters = {
    search:   $('#search-text').val(),
    diplomado: $('#filter-diplomado').val(),
    status:   $('#filter-status').val(),
    docs:     $('#filter-docs').val(),
    verified: $('#filter-verified').val()
    };
    
    const tbody = $('#studentsTableBody');
    // Loader elegante
    tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Cargando registros...</td></tr>');

    $.get(BASE_URL + '/administrative/students/list', filters, function(res) {
        if (!res.ok) {
            tbody.html('<tr><td colspan="7" class="text-center py-4 text-danger">Error al cargar datos.</td></tr>');
            return;
        }
        
        tbody.empty();
        currentStudentsData = res.data;

        if (currentStudentsData.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted">No se encontraron estudiantes con esos criterios.</td></tr>');
            $('#total-results').text('0 registros');
            return;
        }

        let rowNum = 1;
        res.data.forEach(s => {
            // Colores de Estatus Académico
            let badgeClass = 'bg-secondary';
            if(s.estatus_academico === 'ACTIVO') badgeClass = 'bg-success';
            if(s.estatus_academico === 'EGRESADO') badgeClass = 'bg-primary';
            if(s.estatus_academico === 'CONGELADO') badgeClass = 'bg-info text-dark';
            if(s.estatus_academico === 'RETIRADO' || s.estatus_academico === 'SUSPENDIDO') badgeClass = 'bg-danger';

            // Colores de Estatus Digital (Documentos)
            const isComplete = s.estatus_digital === 'COMPLETE';
            const docBadge = isComplete ? 'bg-success-subtle text-success border-success' : 'bg-warning-subtle text-warning-emphasis border-warning';
            const docText = isComplete ? 'COMPLETO' : 'PENDIENTE';

            const ledCedula = s.id_card_approved == 1
                ? '<span class="led led-on" title="Cédula verificada"></span>'
                : '<span class="led led-off" title="Cédula pendiente"></span>';

            const ledTitulo = s.degree_approved == 1
                ? '<span class="led led-on" title="Título verificado"></span>'
                : '<span class="led led-off" title="Título pendiente"></span>';

            tbody.append(`
                <tr class="student-row" data-id="${s.id}">
                    <td class="ps-4 text-muted small fw-bold">${rowNum++}</td>
                    <td><span class="font-monospace fw-bold text-dark small">${s.expediente}</span></td>
                    <td class="fw-bold text-dark">${s.nombre_completo}</td>
                    <td class="text-muted small">${s.cedula}</td>
                    <td class="small text-truncate" style="max-width: 180px;" title="${s.diplomado_nombre}">
                        <i class="bi bi-mortarboard me-1"></i>${s.diplomado_nombre}
                    </td>
                    <td><span class="badge ${badgeClass} status-badge-premium">${s.estatus_academico}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${ledCedula}
                            ${ledTitulo}
                            <span class="badge border ${docBadge}" style="font-size:0.65rem;">
                                <i class="bi ${isComplete ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-1"></i>${docText}
                            </span>
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <i class="bi bi-eye-fill action-eye-icon" onclick="viewProfile(${s.id})" title="Abrir Expediente"></i>
                    </td>
                </tr>
            `);


        });
        $('#total-results').text(`${res.data.length} estudiantes encontrados`);
    }, 'json');
}


/**
 * Muestra la ficha completa del estudiante con todos sus datos y visor de PDF.
 */
function viewProfile(studentId) {
    const student = currentStudentsData.find(s => s.id == studentId);
    if (!student) return;

    const htmlContent = `
        <div class="row g-0 text-start m-0">
            <div class="col-lg-5 p-4 bg-light border-end" style="max-height: 85vh; overflow-y: auto;">
                <h6 class="text-uppercase fw-bold text-muted mb-3 border-bottom pb-2" style="font-size: 0.75rem;">Expediente Digital</h6>
                
                <div class="mb-3">
                    <span class="d-block fw-bold text-dark fs-4 lh-sm">${student.nombre_completo}</span>
                    <span class="badge bg-dark font-monospace mt-1 px-3 py-2 shadow-sm">${student.expediente}</span>
                </div>
                
                <div class="row g-2 mb-3 small">
                    <div class="col-6"><span class="text-muted d-block text-uppercase" style="font-size:0.6rem;">Cédula</span><strong>${student.cedula}</strong></div>
                    <div class="col-6"><span class="text-muted d-block text-uppercase" style="font-size:0.6rem;">Teléfono</span>
                        <div class="d-flex align-items-center gap-2">
                            <strong id="swal-phone">${student.phone || 'N/A'}</strong>
                            ${student.phone ? `<button type="button" onclick="openWhatsappStudent('${student.nombre_completo}', '${student.phone}')" class="btn btn-sm p-0 border-0" style="color: #25D366; font-size: 1.1rem;" title="Enviar WhatsApp"><i class="bi bi-whatsapp"></i></button>` : ''}
                        </div>
                    </div>
                    <div class="col-12"><span class="text-muted d-block text-uppercase" style="font-size:0.6rem;">Correo</span><strong>${student.email}</strong></div>
                    <div class="col-12"><span class="text-muted d-block text-uppercase" style="font-size:0.6rem;">Título</span><strong>${student.titulo || 'N/A'}</strong></div>
                    <div class="col-12"><span class="text-muted d-block text-uppercase" style="font-size:0.6rem;">Procedencia</span><strong>${student.procedencia || 'N/A'}</strong></div>
                </div>

                <div class="p-2 border-0 rounded bg-white small mb-4 shadow-sm">
                    <span class="fw-bold text-muted d-block mb-1" style="font-size:0.65rem;">DIRECCIÓN</span>
                    <span class="text-dark">${student.direccion || 'No registrada'}</span>
                </div>

                <div class="list-group list-group-flush border rounded shadow-sm">
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector active py-3 d-flex justify-content-between align-items-center" data-doctype="cedula" data-field="id_card_approved">
                        <span><i class="bi bi-person-vcard me-2 text-primary"></i>Cédula de Identidad</span>
                        <i class="bi ${student.id_card_approved == 1 ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'} doc-check-icon" data-field="id_card_approved"></i>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector py-3 d-flex justify-content-between align-items-center" data-doctype="titulo" data-field="degree_approved">
                        <span><i class="bi bi-mortarboard me-2 text-success"></i>Título Universitario</span>
                        <i class="bi ${student.degree_approved == 1 ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'} doc-check-icon" data-field="degree_approved"></i>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector py-3 d-flex justify-content-between align-items-center" data-doctype="cv" data-field="cv_approved">
                        <span><i class="bi bi-file-earmark-person me-2 text-secondary"></i>Resumen Curricular</span>
                        <i class="bi ${student.cv_approved == 1 ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'} doc-check-icon" data-field="cv_approved"></i>
                    </button>
                </div>
            </div>

            <div class="col-lg-7 p-4 bg-secondary bg-opacity-10 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted text-uppercase" id="visor-title">Vista Previa</span>
                    <a href="#" id="btn-download-doc" target="_blank" class="btn btn-sm btn-dark shadow-sm" style="display: none;">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                
                <div class="pdf-viewer-container flex-grow-1 bg-white border rounded-4 shadow-sm d-flex align-items-center justify-content-center overflow-hidden" id="pdf-container" style="min-height: 500px;">
                    <p class="text-muted">Cargando visor...</p>
                </div>

                <div class="card border-0 bg-warning bg-opacity-10 mt-4 shadow-sm">
                    <div class="card-body p-2 row align-items-center g-2">
                        <div class="col-7">
                            <span class="text-muted d-block fw-bold text-uppercase" style="font-size:0.65rem;">Estatus Académico</span>
                            <span class="small">Ingreso: <strong>${student.fecha_ingreso || 'N/A'}</strong></span>
                        </div>
                        <div class="col-5">
                            <select id="swal-status-select" class="form-select form-select-sm fw-bold border-0 shadow-sm">
                                <option value="ACTIVO" ${student.estatus_academico === 'ACTIVO' ? 'selected' : ''}>🟢 ACTIVO</option>
                                <option value="CONGELADO" ${student.estatus_academico === 'CONGELADO' ? 'selected' : ''}>🔵 CONGELADO</option>
                                <option value="EGRESADO" ${student.estatus_academico === 'EGRESADO' ? 'selected' : ''}>🎓 EGRESADO</option>
                                <option value="SUSPENDIDO" ${student.estatus_academico === 'SUSPENDIDO' ? 'selected' : ''}>🟡 SUSPENDIDO</option>
                                <option value="RETIRADO" ${student.estatus_academico === 'RETIRADO' ? 'selected' : ''}>🔴 RETIRADO</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    Swal.fire({
        html: htmlContent,
        width: '1200px',
        padding: 0,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-save me-1"></i> Guardar Cambios',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#000',
        cancelButtonColor: '#6c757d',
        customClass: { popup: 'rounded-4 shadow-lg overflow-hidden' },

        didOpen: () => {
    // Cambiar documento al hacer clic en el botón
    $('.btn-doc-selector').on('click', function() {
        $('.btn-doc-selector').removeClass('active');
        $(this).addClass('active');
        renderDocument($(this).data('doctype'), student);
    });

    // Verificar documento al hacer clic en el ícono check
    $('.doc-check-icon').on('click', function(e) {
        e.stopPropagation(); // No activar el selector de documento
        const field = $(this).data('field');
        const icon  = $(this);

        // Solo aprobar si el documento está cargado
        const docMap = {
            'id_card_approved': student.doc_id_card,
            'degree_approved':  student.doc_degree,
            'cv_approved':      student.doc_cv,
        };

        if (!docMap[field] || docMap[field].trim() === '') {
            Swal.fire('Atención', 'No se puede aprobar: el documento no está cargado.', 'warning');
            return;
        }

       $.ajax({
    url: BASE_URL + '/administrative/students/saveDocumentVerification',
    method: 'POST',
    data: {
        enrollment_id: student.enrollment_id,
        student_id:    student.id,
        field:         field
    },
    dataType: 'json',
    success: function(res) {
        if (res.ok) {
            const isNowApproved = student[field] == 1 ? 0 : 1;
            student[field] = isNowApproved;
            if (isNowApproved) {
                icon.removeClass('bi-circle text-muted')
                    .addClass('bi-check-circle-fill text-success');
            } else {
                icon.removeClass('bi-check-circle-fill text-success')
                    .addClass('bi-circle text-muted');
            }

            loadStudents();
        } else {
            Swal.getPopup()?.querySelector('.swal2-html-container')
                ?.insertAdjacentHTML('beforeend', 
                `<div class="alert alert-danger mt-2 small">${res.message}</div>`);
        }
    },
    error: function(xhr) {
        console.error('Error saveDocumentVerification:', xhr.status, xhr.responseText);
    }
}).fail(function() {
    return false;
}); 
    });

    renderDocument('cedula', student);
}

    }).then((result) => {
        if (result.isConfirmed) executeStatusUpdate(student.id, $('#swal-status-select').val());
    });
}

function renderDocument(docType, studentData) {
    const container = document.getElementById('pdf-container');
    const btnDownload = document.getElementById('btn-download-doc');
    const titleMap = { 'cedula': 'Cédula / Identidad', 'titulo': 'Título Universitario', 'cv': 'Resumen Curricular' };
    
    document.getElementById('visor-title').innerText = `Vista Previa: ${titleMap[docType]}`;

    let filePath = '';
    if (docType === 'cedula') filePath = studentData.doc_id_card;
    if (docType === 'titulo') filePath = studentData.doc_degree;
    if (docType === 'cv') filePath = studentData.doc_cv;

    if (!filePath || filePath === 'N/A' || filePath.trim() === '') {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-file-earmark-x display-1 text-secondary opacity-25"></i>
                <h5 class="text-secondary fw-bold mt-2">Documento no cargado</h5>
            </div>`;
        btnDownload.style.display = 'none';
    } else {
        const fullUrl = `${BASE_URL}/${filePath}?t=${new Date().getTime()}`;
        container.innerHTML = `<iframe src="${fullUrl}" style="width:100%; height:100%; border:none;"></iframe>`;
        btnDownload.href = fullUrl;
        btnDownload.style.display = 'inline-block';
    }
}

function executeStatusUpdate(studentId, newStatus) {
    Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
    $.ajax({
        url: BASE_URL + '/administrative/students/updateStatus',
        method: 'POST',
        data: { student_id: studentId, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if(res.ok) {
                Swal.fire({ icon: 'success', title: '¡Estatus Actualizado!', text: res.message, timer: 2000, showConfirmButton: false });
                loadStudents();
            } else { Swal.fire('Error', res.message, 'error'); }
        },
        error: function() { Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error'); }
    });
}

// --- Lógica de Exportación a Excel ---
$('#btn-export-excel').on('click', async function(e) {
    e.preventDefault();

    if (currentStudentsData.length === 0) {
        Swal.fire('Atención', 'No hay datos en la tabla para exportar.', 'warning');
        return;
    }

    Swal.fire({ title: 'Generando Excel...', text: 'Preparando directorio...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const now    = new Date();
        const yy     = now.getFullYear();
        const mm     = String(now.getMonth() + 1).padStart(2, '0');
        const dd     = String(now.getDate()).padStart(2, '0');
        const hh     = String(now.getHours()).padStart(2, '0');
        const min    = String(now.getMinutes()).padStart(2, '0');
        const ss     = String(now.getSeconds()).padStart(2, '0');
        const fileName = `Directorio_Estudiantes_${yy}${mm}${dd}_${hh}${min}${ss}.xlsx`;

        // Colores
        const AZUL    = 'FF2E75B6';
        const BLANCO  = 'FFFFFFFF';
        const VERDE   = 'FFE2EFDA';
        const AMARILLO= 'FFFFF2CC';
        const GRIS_CL = 'FFF9F9F9';
        const GRIS_HD = 'FFF2F2F2';
        const ROJO_CL = 'FFFFD7D7';

        const borderThin = {
            top:    { style: 'thin', color: { argb: 'FFD9D9D9' } },
            left:   { style: 'thin', color: { argb: 'FFD9D9D9' } },
            bottom: { style: 'thin', color: { argb: 'FFD9D9D9' } },
            right:  { style: 'thin', color: { argb: 'FFD9D9D9' } }
        };

        // Logos
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
        // HOJA 1: DIRECTORIO GENERAL
        // ============================================================
        const wsGeneral = wb.addWorksheet('Directorio General');

        wsGeneral.addRow(['DIRECTORIO INSTITUCIONAL DE ESTUDIANTES']);
        wsGeneral.mergeCells('A1:I1');
        const gF1 = wsGeneral.getRow(1);
        gF1.getCell(1).font      = { bold: true, size: 14, color: { argb: AZUL } };
        gF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
        gF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        gF1.height = 70;

        wsGeneral.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
        wsGeneral.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
        wsGeneral.addRow([]);

        // Encabezados
        wsGeneral.addRow(['#', 'EXPEDIENTE', 'NOMBRE COMPLETO', 'CÉDULA', 'CORREO', 'TELÉFONO', 'DIPLOMADO', 'ESTATUS', 'CÉDULA ✓', 'TÍTULO ✓', 'INGRESO']);
        const gF4 = wsGeneral.getRow(4);
        gF4.height = 25;
        gF4.eachCell((cell) => {
            cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
            cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
            cell.border    = borderThin;
        });

        currentStudentsData.forEach((s, idx) => {
            const row = wsGeneral.addRow([
                idx + 1,
                s.expediente,
                s.nombre_completo,
                s.cedula,
                s.email,
                s.phone || 'N/A',
                s.diplomado_nombre,
                s.estatus_academico,
                s.id_card_approved == 1 ? '✅' : '⭕',
                s.degree_approved  == 1 ? '✅' : '⭕',
                s.fecha_ingreso
            ]);

            row.eachCell((cell, colNum) => {
                cell.border = borderThin;
                cell.font   = { size: 10 };
                if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };

                // Estatus académico con color
                if (colNum === 8) {
                    const val = (s.estatus_academico || '').toUpperCase();
                    if (val === 'ACTIVO')    { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };   cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                    if (val === 'EGRESADO')  { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD6E4F7' } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1F4E79' } }; }
                    if (val === 'RETIRADO' || val === 'SUSPENDIDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; }
                    if (val === 'CONGELADO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } }; cell.font = { bold: true, size: 10 }; }
                    cell.alignment = { horizontal: 'center' };
                }

                // LEDs verificación
                if (colNum === 9 || colNum === 10) {
                    cell.alignment = { horizontal: 'center' };
                }
            });
        });

        // Anchos
        wsGeneral.getColumn(1).width  = 5;
        wsGeneral.getColumn(2).width  = 28;
        wsGeneral.getColumn(3).width  = 35;
        wsGeneral.getColumn(4).width  = 14;
        wsGeneral.getColumn(5).width  = 30;
        wsGeneral.getColumn(6).width  = 16;
        wsGeneral.getColumn(7).width  = 35;
        wsGeneral.getColumn(8).width  = 14;
        wsGeneral.getColumn(9).width  = 10;
        wsGeneral.getColumn(10).width = 10;
        wsGeneral.getColumn(11).width = 14;
        wsGeneral.views = [{ state: 'frozen', xSplit: 0, ySplit: 4, topLeftCell: 'A5' }];

        // ============================================================
        // HOJAS POR DIPLOMADO
        // ============================================================
        const grouped  = {};
        const usedNames = {};
        currentStudentsData.forEach(s => {
            if (!grouped[s.diplomado_nombre]) grouped[s.diplomado_nombre] = [];
            grouped[s.diplomado_nombre].push(s);
        });

        Object.keys(grouped).forEach(diploma => {
            let sheetName = diploma.substring(0, 28).replace(/[\\*?:/[\]]/g, "").trim();
            if (usedNames[sheetName]) {
                usedNames[sheetName]++;
                sheetName = sheetName.substring(0, 26) + '_' + usedNames[sheetName];
            } else {
                usedNames[sheetName] = 1;
            }

            const wsDip = wb.addWorksheet(sheetName);

            wsDip.addRow([`PROGRAMA: ${diploma.toUpperCase()}`]);
            wsDip.mergeCells('A1:H1');
            const dF1 = wsDip.getRow(1);
            dF1.getCell(1).font      = { bold: true, size: 13, color: { argb: AZUL } };
            dF1.getCell(1).fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_HD } };
            dF1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            dF1.height = 70;

            wsDip.addRow([`Generado: ${dd}/${mm}/${yy} ${hh}:${min}:${ss}`]);
            wsDip.getRow(2).getCell(1).font = { italic: true, size: 9, color: { argb: 'FF888888' } };
            wsDip.addRow([]);

            wsDip.addRow(['#', 'EXPEDIENTE', 'NOMBRE COMPLETO', 'CÉDULA', 'ESTATUS', 'CÉDULA ✓', 'TÍTULO ✓', 'INGRESO']);
            const dF4 = wsDip.getRow(4);
            dF4.height = 25;
            dF4.eachCell((cell) => {
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: AZUL } };
                cell.font      = { bold: true, color: { argb: BLANCO }, size: 10 };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border    = borderThin;
            });

            grouped[diploma].forEach((s, idx) => {
                const row = wsDip.addRow([
                    idx + 1,
                    s.expediente,
                    s.nombre_completo,
                    s.cedula,
                    s.estatus_academico,
                    s.id_card_approved == 1 ? '✅' : '⭕',
                    s.degree_approved  == 1 ? '✅' : '⭕',
                    s.fecha_ingreso
                ]);

                row.eachCell((cell, colNum) => {
                    cell.border = borderThin;
                    cell.font   = { size: 10 };
                    if (idx % 2 === 0) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GRIS_CL } };

                    if (colNum === 5) {
                        const val = (s.estatus_academico || '').toUpperCase();
                        if (val === 'ACTIVO')    { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: VERDE } };   cell.font = { bold: true, size: 10, color: { argb: 'FF1E6B24' } }; }
                        if (val === 'EGRESADO')  { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD6E4F7' } }; cell.font = { bold: true, size: 10, color: { argb: 'FF1F4E79' } }; }
                        if (val === 'RETIRADO' || val === 'SUSPENDIDO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROJO_CL } }; cell.font = { bold: true, size: 10, color: { argb: 'FFCC0000' } }; }
                        if (val === 'CONGELADO') { cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: AMARILLO } }; cell.font = { bold: true, size: 10 }; }
                        cell.alignment = { horizontal: 'center' };
                    }
                    if (colNum === 6 || colNum === 7) cell.alignment = { horizontal: 'center' };
                });
            });

            wsDip.getColumn(1).width = 5;
            wsDip.getColumn(2).width = 28;
            wsDip.getColumn(3).width = 35;
            wsDip.getColumn(4).width = 14;
            wsDip.getColumn(5).width = 14;
            wsDip.getColumn(6).width = 10;
            wsDip.getColumn(7).width = 10;
            wsDip.getColumn(8).width = 14;
            wsDip.views = [{ state: 'frozen', xSplit: 0, ySplit: 4, topLeftCell: 'A5' }];

            agregarLogo(wsDip, logos.ucla, 'png', 0, 0, 90, 70);
            agregarLogo(wsDip, logos.medicina, 'jpeg', 7, 0, 90, 70);
        });

        // Logos hoja general
        await agregarLogo(wsGeneral, logos.ucla,     'png',  0, 0, 90, 70);
        await agregarLogo(wsGeneral, logos.medicina, 'jpeg', 10, 0, 90, 70);

        // Descargar
        const buffer = await wb.xlsx.writeBuffer();
        const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        saveAs(blob, fileName);

        Swal.close();

    } catch (error) {
        console.error("Error al exportar:", error);
        Swal.fire('Error', error.message, 'error');
    }
});

function openWhatsappStudent(nombre, telefono) {
    Swal.fire({
        title: '<i class="bi bi-whatsapp text-success me-2"></i>Enviar WhatsApp',
        html: `
            <p class="text-muted small mb-2">Para: <strong>${nombre}</strong> — <span class="font-monospace">${telefono}</span></p>
            <textarea id="wa-msg-dir" class="form-control mb-3" rows="3" placeholder="Escriba el mensaje aquí..."></textarea>
            <div class="p-3 bg-light rounded text-start small text-muted">
                <strong>Vista previa:</strong><br>
                Buenas <strong>${nombre}</strong><br>
                Le escribimos de parte de la <strong>Plataforma de Diplomados</strong> para informarte que:<br>
                <em id="wa-preview-dir" class="text-dark"></em><br><br>
                Atentamente,<br><strong>Coordinación de Diplomados</strong>
            </div>`,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-whatsapp me-1"></i> Abrir WhatsApp',
        confirmButtonColor: '#25D366',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            document.getElementById('wa-msg-dir').addEventListener('input', function() {
                document.getElementById('wa-preview-dir').textContent = this.value;
            });
        },
        preConfirm: () => {
            const msg = document.getElementById('wa-msg-dir').value.trim();
            if (!msg) { Swal.showValidationMessage('Escribe el mensaje personalizado'); return false; }
            return msg;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const tel   = telefono.replace(/\D/g, '').slice(-10);
            const texto = `Buenas ${nombre}\nLe escribimos de parte de la *Plataforma de Diplomados* para informarte que:\n${result.value}\n\nAtentamente,\n*Coordinación de Diplomados*`;
            window.open(`https://web.whatsapp.com/send?phone=58${tel}&text=${encodeURIComponent(texto)}`, '_blank');
        }
    });
}