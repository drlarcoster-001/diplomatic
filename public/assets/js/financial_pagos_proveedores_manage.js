/**
 * MÓDULO: FINANCIERO / PAGOS A PROVEEDORES
 * ARCHIVO: public/assets/js/financial_pagos_proveedores_manage.js
 * PROPÓSITO: Captura rápida de ítems tipo hoja de cálculo (Tab/Enter, vista
 *            previa en vivo, foco automático), presets de ajustes con un
 *            clic, y acciones de Procesar / Descartar / Reversar.
 * VERSIÓN: 2.0.0 - Rediseño completo tipo factura.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE    = (window.APP_BASE_PATH || '') + '/financial/pagos-proveedores';
    const PAGO_ID = window.PAGO_ID;

    // =========================================================================
    // CAMBIAR PROVEEDOR
    // =========================================================================
    document.getElementById('btnCambiarProveedor')?.addEventListener('click', async () => {
        const proveedores = window.PROVEEDORES || [];
        const opciones = proveedores.reduce((acc, p) => {
            acc[p.id] = p.nombre;
            return acc;
        }, {});

        const { value: nuevoId, isConfirmed } = await Swal.fire({
            title: 'Cambiar proveedor',
            input: 'select',
            inputOptions: opciones,
            inputPlaceholder: 'Selecciona un proveedor...',
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#533AB7'
        });

        if (!isConfirmed || !nuevoId) return;

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        fd.append('proveedor_id', nuevoId);

        try {
            const resp = await fetch(`${BASE}/cambiarProveedor`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1200, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // TOTALES
    // =========================================================================
    function actualizarTotales(pago, ajustes) {
        document.getElementById('totSubtotal').textContent = '$' + parseFloat(pago.subtotal).toFixed(2);
        document.getElementById('totUsd').textContent      = '$' + parseFloat(pago.total_usd).toFixed(2);
        document.getElementById('totTasa').textContent     = parseFloat(pago.tasa_bcv).toFixed(4);
        document.getElementById('totBs').textContent       = 'Bs. ' + parseFloat(pago.total_bs).toFixed(2);

        const wrap = document.getElementById('totalesAjustes');
        if (wrap && ajustes) {
            wrap.innerHTML = ajustes.map(a => {
                const color = a.direccion === 'SUMA' ? '#085041' : '#A32D2D';
                const signo = a.direccion === 'SUMA' ? '+' : '-';
                return `<div class="pp-total-linea text-muted small">
                    <span>${a.nombre}</span>
                    <span style="color:${color}">${signo}$${parseFloat(a.monto_calculado).toFixed(2)}</span>
                </div>`;
            }).join('');
        }
    }

    // =========================================================================
    // VISTA PREVIA EN VIVO DEL SUBTOTAL DE LA FILA NUEVA
    // =========================================================================
    const inpCant   = document.getElementById('inpCant');
    const inpPrecio = document.getElementById('inpPrecio');
    const previewEl = document.getElementById('previewSubtotal');

    function actualizarPreview() {
        if (!previewEl) return;
        const cant   = parseFloat(inpCant?.value) || 0;
        const precio = parseFloat(inpPrecio?.value) || 0;
        previewEl.textContent = '$' + (cant * precio).toFixed(2);
    }
    inpCant?.addEventListener('input', actualizarPreview);
    inpPrecio?.addEventListener('input', actualizarPreview);

    // =========================================================================
    // AGREGAR ÍTEM (Enter o botón +)
    // =========================================================================
    async function agregarItem() {
        const inpDesc = document.getElementById('inpDesc');
        const desc   = inpDesc.value.trim();
        const cant   = parseFloat(inpCant.value);
        const precio = parseFloat(inpPrecio.value);

        if (!desc || !cant || cant <= 0 || isNaN(precio) || precio < 0) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Completa descripción, cantidad y precio.' });
            return;
        }

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        fd.append('descripcion', desc);
        fd.append('cantidad', cant);
        fd.append('precio_unitario', precio);

        try {
            const resp = await fetch(`${BASE}/addItem`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            insertarFilaGuardada(resp.items[resp.items.length - 1], resp.items.length);
            renderAjustes(resp.ajustes);
            actualizarTotales(resp.pago, resp.ajustes);

            inpDesc.value = '';
            inpCant.value = '1';
            inpPrecio.value = '';
            actualizarPreview();
            inpDesc.focus();
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    }

    document.getElementById('btnAddItem')?.addEventListener('click', agregarItem);
    document.getElementById('inpDesc')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); inpCant.focus(); inpCant.select(); }
    });
    document.getElementById('inpCant')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); inpPrecio.focus(); }
    });
    document.getElementById('inpPrecio')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); agregarItem(); }
    });

    function insertarFilaGuardada(item, numero) {
        const fila = document.createElement('tr');
        fila.className = 'pp-fila-item';
        fila.dataset.id = item.id;
        fila.innerHTML = `
            <td class="text-muted small">${numero}</td>
            <td>${item.descripcion}</td>
            <td class="text-end">${parseFloat(item.cantidad)}</td>
            <td class="text-end">$${parseFloat(item.precio_unitario).toFixed(2)}</td>
            <td class="text-end fw-bold">$${parseFloat(item.subtotal).toFixed(2)}</td>
            <td class="text-center">
                <button class="pp-fila-del btn-del-item" data-id="${item.id}" title="Eliminar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        const filaNueva = document.getElementById('filaNuevaItem');
        filaNueva.parentNode.insertBefore(fila, filaNueva);
        fila.querySelector('.btn-del-item').addEventListener('click', () => eliminarItem(item.id));
        document.getElementById('numFilaNueva').textContent = numero + 1;
    }

    async function eliminarItem(itemId) {
        const fd = new FormData();
        fd.append('item_id', itemId);
        fd.append('pago_id', PAGO_ID);
        try {
            const resp = await fetch(`${BASE}/removeItem`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            document.querySelector(`.pp-fila-item[data-id="${itemId}"]`)?.remove();
            renumerarFilas();
            renderAjustes(resp.ajustes);
            actualizarTotales(resp.pago, resp.ajustes);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    }

    function renumerarFilas() {
        const filas = document.querySelectorAll('#cuerpoItems .pp-fila-item');
        filas.forEach((f, idx) => { f.querySelector('td').textContent = idx + 1; });
        const numFilaNueva = document.getElementById('numFilaNueva');
        if (numFilaNueva) numFilaNueva.textContent = filas.length + 1;
    }

    document.querySelectorAll('.btn-del-item').forEach(btn => {
        btn.addEventListener('click', () => eliminarItem(btn.dataset.id));
    });

    // =========================================================================
    // AJUSTES: PRESETS DE UN CLIC
    // =========================================================================
    async function enviarAjuste(nombre, tipo, direccion, valor) {
        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        fd.append('nombre', nombre);
        fd.append('tipo', tipo);
        fd.append('direccion', direccion);
        fd.append('valor', valor);

        try {
            const resp = await fetch(`${BASE}/addAjuste`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            renderAjustes(resp.ajustes);
            actualizarTotales(resp.pago, resp.ajustes);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    }

    document.querySelectorAll('.pp-preset-btn:not(.pp-preset-custom)').forEach(btn => {
        btn.addEventListener('click', () => {
            enviarAjuste(btn.dataset.nombre, btn.dataset.tipo, btn.dataset.dir, parseFloat(btn.dataset.valor));
        });
    });

    document.getElementById('btnAjusteCustom')?.addEventListener('click', () => {
        const form = document.getElementById('formAjusteCustom');
        form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });

    document.getElementById('btnAddAjusteCustom')?.addEventListener('click', () => {
        const nombre    = document.getElementById('ajusteNombre').value.trim();
        const tipo      = document.getElementById('ajusteTipo').value;
        const direccion = document.getElementById('ajusteDireccion').value;
        const valor     = parseFloat(document.getElementById('ajusteValor').value);

        if (!nombre || isNaN(valor) || valor <= 0) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Completa nombre y valor.' });
            return;
        }
        enviarAjuste(nombre, tipo, direccion, valor);
        document.getElementById('ajusteNombre').value = '';
        document.getElementById('ajusteValor').value = '';
    });

    function renderAjustes(ajustes) {
        const wrap = document.getElementById('listaAjustes');
        wrap.innerHTML = ajustes.map(a => {
            const valorTxt = a.tipo === 'PORCENTAJE' ? parseFloat(a.valor).toFixed(2) + '%' : '$' + parseFloat(a.valor).toFixed(2);
            const color = a.direccion === 'SUMA' ? '#085041' : '#A32D2D';
            const signo = a.direccion === 'SUMA' ? '+' : '-';
            return `<div class="pp-ajuste-chip" data-id="${a.id}">
                <span>${a.nombre} <span class="text-muted">(${valorTxt})</span></span>
                <span style="color:${color}" class="fw-bold">${signo}$${parseFloat(a.monto_calculado).toFixed(2)}</span>
                <button class="pp-chip-del btn-del-ajuste" data-id="${a.id}"><i class="bi bi-x"></i></button>
            </div>`;
        }).join('');
        bindDelAjustes();
    }

    function bindDelAjustes() {
        document.querySelectorAll('.btn-del-ajuste').forEach(btn => {
            btn.addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('ajuste_id', btn.dataset.id);
                fd.append('pago_id', PAGO_ID);
                try {
                    const resp = await fetch(`${BASE}/removeAjuste`, { method: 'POST', body: fd }).then(r => r.json());
                    if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
                    renderAjustes(resp.ajustes);
                    actualizarTotales(resp.pago, resp.ajustes);
                } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
            });
        });
    }
    bindDelAjustes();

    // =========================================================================
    // PROCESAR / DESCARTAR / REVERSAR
    // =========================================================================
    document.getElementById('btnProcesar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Procesar este pago?',
            text: 'Se congelará y no podrás editar ítems ni ajustes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#533AB7',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        try {
            const resp = await fetch(`${BASE}/procesar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    document.getElementById('btnDescartar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Descartar este pago?',
            text: 'Se eliminará por completo. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, descartar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        try {
            const resp = await fetch(`${BASE}/descartar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.href = BASE);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    document.getElementById('btnReversar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Reversar este pago?',
            text: 'Retrocederá un paso en el flujo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);
        try {
            const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });
});