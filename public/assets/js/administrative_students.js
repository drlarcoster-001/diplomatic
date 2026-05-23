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
    $('#filter-status, #filter-diplomado, #filter-docs').on('change', function() {
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
        search: $('#search-text').val(),
        diplomado: $('#filter-diplomado').val(),
        status: $('#filter-status').val(),
        docs: $('#filter-docs').val()
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

            tbody.append(`
                <tr class="student-row" data-id="${s.id}">
                    <td class="ps-4"><span class="font-monospace fw-bold text-dark">${s.expediente}</span></td>
                    <td class="fw-bold text-dark">${s.nombre_completo}</td>
                    <td class="text-muted small">${s.cedula}</td>
                    <td class="small text-truncate" style="max-width: 180px;" title="${s.diplomado_nombre}">
                        <i class="bi bi-mortarboard me-1"></i>${s.diplomado_nombre}
                    </td>
                    <td><span class="badge ${badgeClass} status-badge-premium">${s.estatus_academico}</span></td>
                    <td>
                        <span class="badge border ${docBadge}" style="font-size:0.65rem;">
                            <i class="bi ${isComplete ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-1"></i>${docText}
                        </span>
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
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector active py-3" data-doctype="cedula">
                        <i class="bi bi-person-vcard me-2 text-primary"></i>Cédula de Identidad
                    </button>
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector py-3" data-doctype="titulo">
                        <i class="bi bi-mortarboard me-2 text-success"></i>Título Universitario
                    </button>
                    <button type="button" class="list-group-item list-group-item-action btn-doc-selector py-3" data-doctype="cv">
                        <i class="bi bi-file-earmark-person me-2 text-secondary"></i>Resumen Curricular
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
            $('.btn-doc-selector').on('click', function() {
                $('.btn-doc-selector').removeClass('active');
                $(this).addClass('active');
                renderDocument($(this).data('doctype'), student);
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

    try {
        const wb = XLSX.utils.book_new();

        // 1. HOJA GENERAL: Todos los estudiantes filtrados
        let wsGeneralData = [
            ["DIRECTORIO INSTITUCIONAL DE ESTUDIANTES"],
            [`Fecha de Reporte: ${new Date().toLocaleDateString()}`],
            [],
            ["EXPEDIENTE", "NOMBRE COMPLETO", "CÉDULA", "CORREO", "TELÉFONO", "DIPLOMADO", "ESTATUS ACADÉMICO", "DOCS", "FECHA INGRESO"]
        ];

        currentStudentsData.forEach(s => {
            wsGeneralData.push([
                s.expediente, s.nombre_completo, s.cedula, s.email, s.phone || 'N/A',
                s.diplomado_nombre, s.estatus_academico, s.estatus_digital, s.fecha_ingreso
            ]);
        });

        const wsGeneral = XLSX.utils.aoa_to_sheet(wsGeneralData);
        XLSX.utils.book_append_sheet(wb, wsGeneral, "Directorio General");

        // 2. HOJAS POR DIPLOMADO (Segmentación)
        const grouped = {};
        currentStudentsData.forEach(s => {
            if (!grouped[s.diplomado_nombre]) grouped[s.diplomado_nombre] = [];
            grouped[s.diplomado_nombre].push(s);
        });

        Object.keys(grouped).forEach(diploma => {
            let wsDipData = [
                [`PROGRAMA: ${diploma.toUpperCase()}`],
                ["Lista de estudiantes inscritos en este programa."],
                [],
                ["EXPEDIENTE", "NOMBRE COMPLETO", "CÉDULA", "ESTATUS", "INGRESO"]
            ];

            grouped[diploma].forEach(s => {
                wsDipData.push([s.expediente, s.nombre_completo, s.cedula, s.estatus_academico, s.fecha_ingreso]);
            });

            const wsDip = XLSX.utils.aoa_to_sheet(wsDipData);
            // Nombre de pestaña limitado a 30 caracteres (regla de Excel)
            const sheetName = diploma.substring(0, 30).replace(/[\\*?:/[\]]/g, "");
            XLSX.utils.book_append_sheet(wb, wsDip, sheetName);
        });

        // Generar descarga
        XLSX.writeFile(wb, `Directorio_Estudiantes_${new Date().getTime()}.xlsx`);
        
    } catch (error) {
        console.error("Error al exportar:", error);
        Swal.fire('Error', 'No se pudo generar el archivo Excel.', 'error');
    }

    });  // <-- cierre del $('#btn-export-excel').on('click'...)

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