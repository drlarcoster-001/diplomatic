/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA
 * ARCHIVO: public/assets/js/financial_student_statement_voucher.js
 * PROPÓSITO: Visor flotante draggable de comprobantes de pago (digital y efectivo).
 * VERSIÓN: 1.0.0
 * DEPENDENCIAS: financial_student_statement.js (usa state.baseUrl, state.selectedEnrollmentId, state.selectedStudentId)
 */

(function () {
    "use strict";

    // ----------------------------------------------------------------
    //  REFERENCIAS DOM
    // ----------------------------------------------------------------
    const panel        = document.getElementById('voucher-panel');
    const dragHandle   = document.getElementById('voucher-drag-handle');
    const closeBtn     = document.getElementById('voucher-close-btn');
    const resizeHandle = document.getElementById('voucher-resize-handle');

    if (!panel) return; // Seguro: si no existe el panel no inicializa nada

    // ----------------------------------------------------------------
    //  ESTADOS INTERNOS DEL PANEL
    // ----------------------------------------------------------------
    const voucherStates = {
        loading : document.getElementById('voucher-loading'),
        error   : document.getElementById('voucher-error'),
        digital : document.getElementById('voucher-digital'),
        cash    : document.getElementById('voucher-cash'),
    };

    let currentZoom  = 1;
    const ZOOM_STEP  = 0.25;
    const ZOOM_MAX   = 3;
    const ZOOM_MIN   = 0.5;

    // ----------------------------------------------------------------
    //  MOSTRAR ESTADO DEL PANEL
    // ----------------------------------------------------------------
    function showState(name) {
        Object.values(voucherStates).forEach(el => el?.classList.add('d-none'));
        voucherStates[name]?.classList.remove('d-none');
    }

    // ----------------------------------------------------------------
    //  ABRIR PANEL
    // ----------------------------------------------------------------
    function openPanel(tipo, referencia) {
        // Reset zoom
        currentZoom = 1;
        applyZoom();

        // Actualizar referencia en el header
        setText('voucher-ref-label', `Ref: ${referencia}`);

        // Reset badge
        const badge = document.getElementById('voucher-method-badge');
        if (badge) {
            badge.textContent = '---';
            badge.className   = 'voucher-method-badge';
        }

        // Mostrar panel
        panel.classList.remove('d-none');
        panel.classList.add('visible');
        showState('loading');

        fetchVoucher(tipo, referencia);
    }

    // ----------------------------------------------------------------
    //  CERRAR PANEL
    // ----------------------------------------------------------------
    function closePanel() {
        panel.classList.add('d-none');
        panel.classList.remove('visible');
        const img = document.getElementById('voucher-img');
        if (img) img.src = '';
    }

    // ----------------------------------------------------------------
    //  FETCH AL ENDPOINT
    // ----------------------------------------------------------------
    async function fetchVoucher(tipo, referencia) {
        try {
            // Usa el state del módulo principal (financial_student_statement.js)
            const baseUrl    = window.BASE_URL || '/diplomatic/public';
            const enrollId   = window._voucherEnrollId   || 0;
            const userId     = window._voucherUserId     || 0;

            const url = `${baseUrl}/financial/student_statement/getPaymentVoucher`
                      + `?tipo=${encodeURIComponent(tipo)}`
                      + `&referencia=${encodeURIComponent(referencia)}`
                      + `&enrollment_id=${enrollId}`
                      + `&user_id=${userId}`;

            const response = await fetch(url);
            const result   = await response.json();

            if (result.ok && result.data) {
                renderVoucher(result.data);
            } else {
                showError(result.message || 'Comprobante no disponible.');
            }
        } catch (e) {
            showError('Error de conexión con el servidor.');
        }
    }

    // ----------------------------------------------------------------
    //  RENDERIZAR SEGÚN MÉTODO
    // ----------------------------------------------------------------
    function renderVoucher(data) {
        const badge = document.getElementById('voucher-method-badge');
        if (badge) {
            badge.textContent = data.method;
            badge.className   = `voucher-method-badge method-${data.method.toLowerCase()}`;
        }

        if (data.method === 'CASH') {
            renderCash(data);
        } else {
            renderDigital(data);
        }
    }

    // ----------------------------------------------------------------
    //  RENDER — PAGO DIGITAL
    // ----------------------------------------------------------------
    function renderDigital(data) {
        const t = data.transaccion || {};
        const o = data.origen      || {};

        setText('v-banco',      o.banco_emisor        || 'N/A');
        setText('v-telefono',   o.cuenta_correo_telf  || 'N/A');
        setText('v-cedula',     o.identificador       || 'N/A');
        setText('v-fecha-comp', t.fecha_comprobante   || 'N/A');

        const moneda      = t.moneda_nativa || data.currency || 'Bs';
        const montoNativo = parseFloat(t.monto_nativo || data.amount || 0);
        setText('v-monto-bs',  `${moneda} ${montoNativo.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
        setText('v-monto-usd', `$ ${parseFloat(data.monto_usd || 0).toFixed(2)}`);

        // Imagen
        const img       = document.getElementById('voucher-img');
        const noImage   = document.getElementById('voucher-no-image');
        const zoomCtrls = document.getElementById('voucher-zoom-controls');
        const openFull  = document.getElementById('btn-open-full');

        if (data.screenshot_path) {
            const baseUrl = window.BASE_URL || '/diplomatic/public';
            const imgUrl  = `${baseUrl}/${data.screenshot_path}`;
            img.src = imgUrl;
            img.classList.remove('d-none');
            noImage?.classList.add('d-none');
            zoomCtrls?.classList.remove('d-none');
            if (openFull) openFull.href = imgUrl;
        } else {
            img.classList.add('d-none');
            noImage?.classList.remove('d-none');
            zoomCtrls?.classList.add('d-none');
        }

        showState('digital');
    }

    // ----------------------------------------------------------------
    //  RENDER — PAGO EN EFECTIVO (ARQUEO)
    // ----------------------------------------------------------------
    function renderCash(data) {
        const arqueo   = data.arqueo || {};
        const desglose = arqueo.desglose_billetes || {};

        const tbody   = document.getElementById('voucher-cash-tbody');
        const totalEl = document.getElementById('voucher-cash-total');

        let totalCalculado = 0;
        let rowsHtml       = '';

        // Ordenar denominaciones de mayor a menor
        const denominaciones = Object.keys(desglose)
            .map(Number)
            .sort((a, b) => b - a);

        denominaciones.forEach(denom => {
            const cantidad = parseInt(desglose[denom]) || 0;
            if (cantidad === 0) return;

            const subtotal = denom * cantidad;
            totalCalculado += subtotal;

            rowsHtml += `
                <tr>
                    <td class="text-center fw-bold">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                            $ ${denom}
                        </span>
                    </td>
                    <td class="text-center">${cantidad}</td>
                    <td class="text-end fw-bold">$ ${subtotal.toFixed(2)}</td>
                </tr>
            `;
        });

        if (!rowsHtml) {
            rowsHtml = `<tr><td colspan="3" class="text-center text-muted small py-3">Sin detalle de billetes.</td></tr>`;
        }

        if (tbody)   tbody.innerHTML     = rowsHtml;
        if (totalEl) totalEl.textContent = `$ ${totalCalculado.toFixed(2)}`;

        setText('v-cash-agente', String(arqueo.agente_receptor || 'N/A'));
        setText('v-cash-fecha',  arqueo.fecha_recepcion        || 'N/A');

        showState('cash');
    }

    // ----------------------------------------------------------------
    //  ERROR
    // ----------------------------------------------------------------
    function showError(msg) {
        const msgEl = document.getElementById('voucher-error-msg');
        if (msgEl) msgEl.textContent = msg;
        showState('error');
    }

    // ----------------------------------------------------------------
    //  HELPER setText
    // ----------------------------------------------------------------
    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    // ----------------------------------------------------------------
    //  ZOOM
    // ----------------------------------------------------------------
    function applyZoom() {
        const img = document.getElementById('voucher-img');
        if (img) img.style.transform = `scale(${currentZoom}) translate(${imgTranslateX / currentZoom}px, ${imgTranslateY / currentZoom}px)`;
    }

    document.getElementById('btn-zoom-in')?.addEventListener('click', () => {
        currentZoom = Math.min(currentZoom + ZOOM_STEP, ZOOM_MAX);
        applyZoom();
    });

    document.getElementById('btn-zoom-out')?.addEventListener('click', () => {
        currentZoom = Math.max(currentZoom - ZOOM_STEP, ZOOM_MIN);
        applyZoom();
    });

    document.getElementById('btn-zoom-reset')?.addEventListener('click', () => {
        currentZoom   = 1;
        imgTranslateX = 0;
        imgTranslateY = 0;
        applyZoom();
    });


    // Arrastre de imagen dentro del contenedor
    let imgDragging  = false;
    let imgStartX    = 0;
    let imgStartY    = 0;
    let imgTranslateX = 0;
    let imgTranslateY = 0;

    const voucherImg = document.getElementById('voucher-img');

    voucherImg?.addEventListener('mousedown', (e) => {
        imgDragging = true;
        imgStartX   = e.clientX - imgTranslateX;
        imgStartY   = e.clientY - imgTranslateY;
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!imgDragging) return;
        imgTranslateX = e.clientX - imgStartX;
        imgTranslateY = e.clientY - imgStartY;
        if (voucherImg) {
            voucherImg.style.transform = `scale(${currentZoom}) translate(${imgTranslateX / currentZoom}px, ${imgTranslateY / currentZoom}px)`;
        }
    });

    document.addEventListener('mouseup', () => { imgDragging = false; });

    document.getElementById('voucher-img')?.addEventListener('wheel', (e) => {
        e.preventDefault();
        currentZoom = e.deltaY < 0
            ? Math.min(currentZoom + ZOOM_STEP, ZOOM_MAX)
            : Math.max(currentZoom - ZOOM_STEP, ZOOM_MIN);
        applyZoom();
    }, { passive: false });

    // ----------------------------------------------------------------
    //  DRAG
    // ----------------------------------------------------------------
    let isDragging  = false;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    dragHandle?.addEventListener('mousedown', (e) => {
        if (e.target.closest('.voucher-panel__close')) return;
        isDragging  = true;
        const rect  = panel.getBoundingClientRect();
        dragOffsetX = e.clientX - rect.left;
        dragOffsetY = e.clientY - rect.top;
        panel.classList.add('is-dragging');
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        let newX = e.clientX - dragOffsetX;
        let newY = e.clientY - dragOffsetY;
        newX = Math.max(0, Math.min(newX, window.innerWidth  - panel.offsetWidth));
        newY = Math.max(0, Math.min(newY, window.innerHeight - panel.offsetHeight));
        panel.style.left   = `${newX}px`;
        panel.style.top    = `${newY}px`;
        panel.style.right  = 'auto';
        panel.style.bottom = 'auto';
    });

    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            panel.classList.remove('is-dragging');
        }
    });

    // ----------------------------------------------------------------
    //  RESIZE
    // ----------------------------------------------------------------
    let isResizing   = false;
    let resizeStartY = 0;
    let resizeStartH = 0;

    resizeHandle?.addEventListener('mousedown', (e) => {
        isResizing   = true;
        resizeStartY = e.clientY;
        resizeStartH = panel.offsetHeight;
        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;
        const newH = Math.max(200, resizeStartH + (e.clientY - resizeStartY));
        panel.style.height    = `${newH}px`;
        panel.style.maxHeight = 'none';
    });

    document.addEventListener('mouseup', () => { isResizing = false; });

    // ----------------------------------------------------------------
    //  CERRAR CON BOTÓN X
    // ----------------------------------------------------------------
    closeBtn?.addEventListener('click', closePanel);

    // ----------------------------------------------------------------
    //  EVENTO: clic en botón 👁️ de la tabla del modal
    // ----------------------------------------------------------------
    document.getElementById('table-history-payments')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-view-voucher');
        if (!btn) return;
        openPanel(btn.dataset.tipo, btn.dataset.ref);
    });

    // ----------------------------------------------------------------
    //  API PÚBLICA (por si se necesita desde otro módulo)
    // ----------------------------------------------------------------
    window.VoucherPanel = { open: openPanel, close: closePanel };

})();