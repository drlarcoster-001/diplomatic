/**
 * MÓDULO: FINANCIERO / TESORERÍA
 * ARCHIVO: public/assets/js/financial_tesoreria.js
 * PROPÓSITO: Mostrar/ocultar campos según el medio de pago elegido, calcular
 *            el arqueo de billetes en vivo (USD), y enviar el formulario de
 *            pago (multipart, por el archivo de comprobante). Botón Reversar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE          = (window.APP_BASE_PATH || '') + '/financial/tesoreria';
    const TESORERIA_ID  = window.TESORERIA_ID;
    const ORDEN_PAGO_ID = window.ORDEN_PAGO_ID;
    const MONTO_USD     = window.MONTO_USD || 0;

    // =========================================================================
    // MOSTRAR/OCULTAR CAMPOS SEGÚN MEDIO DE PAGO
    // =========================================================================
    const medioPago       = document.getElementById('medioPago');
    const bloqueEfectivo  = document.getElementById('bloqueEfectivo');
    const bloqueBancario  = document.getElementById('bloqueBancario');
    const campoCuenta     = document.getElementById('campoCuenta');
    const campoTelefono   = document.getElementById('campoTelefono');

    medioPago?.addEventListener('change', () => {
        const val = medioPago.value;
        bloqueEfectivo.style.display = val === 'EFECTIVO' ? 'block' : 'none';
        bloqueBancario.style.display = (val === 'TRANSFERENCIA' || val === 'PAGO_MOVIL') ? 'block' : 'none';
        campoCuenta.style.display    = val === 'TRANSFERENCIA' ? 'block' : 'none';
        campoTelefono.style.display  = val === 'PAGO_MOVIL' ? 'block' : 'none';
    });

    // =========================================================================
    // MOSTRAR/OCULTAR ARQUEO SEGÚN MONEDA
    // =========================================================================
    const monedaEfectivo = document.getElementById('monedaEfectivo');
    const arqueoUsd       = document.getElementById('arqueoUsd');
    const arqueoBs         = document.getElementById('arqueoBs');

    monedaEfectivo?.addEventListener('change', () => {
        const val = monedaEfectivo.value;
        arqueoUsd.style.display = val === 'USD' ? 'block' : 'none';
        arqueoBs.style.display  = val === 'BS'  ? 'block' : 'none';
    });

    // =========================================================================
    // CÁLCULO EN VIVO DEL ARQUEO USD
    // =========================================================================
    function recalcularArqueoUsd() {
        let total = 0;
        document.querySelectorAll('.arqueo-input').forEach(input => {
            const den = parseFloat(input.dataset.den);
            const cant = parseInt(input.value) || 0;
            const sub = den * cant;
            total += sub;
            const subEl = document.getElementById(`subDen${input.dataset.den}`);
            if (subEl) subEl.textContent = '$' + sub.toFixed(2);
        });

        document.getElementById('totalArqueoUsd').textContent = '$' + total.toFixed(2);

        const diffEl = document.getElementById('diffArqueoUsd');
        const diff = total - MONTO_USD;
        if (Math.abs(diff) < 0.01) {
            diffEl.textContent = '✓ Coincide con el monto a pagar';
            diffEl.style.color = '#085041';
        } else {
            diffEl.textContent = diff > 0
                ? `Sobran $${diff.toFixed(2)}`
                : `Faltan $${Math.abs(diff).toFixed(2)}`;
            diffEl.style.color = '#A32D2D';
        }
    }
    document.querySelectorAll('.arqueo-input').forEach(input => {
        input.addEventListener('input', recalcularArqueoUsd);
    });

    // =========================================================================
    // CONFIRMAR PAGO
    // =========================================================================
    document.getElementById('btnPagar')?.addEventListener('click', async () => {
        const medio = medioPago.value;
        if (!medio) {
            Swal.fire({ icon: 'warning', title: 'Falta el medio de pago', text: 'Selecciona cómo se realizó el pago.' });
            return;
        }

        const fd = new FormData();
        fd.append('id', TESORERIA_ID);
        fd.append('orden_pago_id', ORDEN_PAGO_ID);
        fd.append('medio_pago', medio);

        if (medio === 'EFECTIVO') {
            const moneda = monedaEfectivo.value;
            if (!moneda) {
                Swal.fire({ icon: 'warning', title: 'Falta la moneda', text: 'Selecciona dólares o bolívares.' });
                return;
            }
            fd.append('moneda_efectivo', moneda);

            if (moneda === 'USD') {
                const detalle = [];
                document.querySelectorAll('.arqueo-input').forEach(input => {
                    const cant = parseInt(input.value) || 0;
                    if (cant > 0) detalle.push(`${cant} billete(s) de $${input.dataset.den}`);
                });
                if (detalle.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Arqueo vacío', text: 'Registra al menos un billete contado.' });
                    return;
                }
                fd.append('arqueo_detalle', detalle.join(', '));
            } else {
                const texto = document.getElementById('arqueoTextoBs').value.trim();
                if (!texto) {
                    Swal.fire({ icon: 'warning', title: 'Falta la descripción', text: 'Describe el arqueo en bolívares.' });
                    return;
                }
                fd.append('arqueo_detalle', texto);
            }
        } else {
            const banco = document.getElementById('bancoInput').value.trim();
            const destinatario = document.getElementById('destinatarioInput').value.trim();
            const referencia = document.getElementById('referenciaInput').value.trim();
            const comprobante = document.getElementById('comprobanteInput').files[0];

            if (!banco || !destinatario || !referencia) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Completa banco, destinatario y referencia.' });
                return;
            }
            if (!comprobante) {
                Swal.fire({ icon: 'warning', title: 'Falta el comprobante', text: 'Adjunta la captura de la transferencia.' });
                return;
            }

            fd.append('banco', banco);
            fd.append('nombre_destinatario', destinatario);
            fd.append('referencia', referencia);
            fd.append('comprobante', comprobante);

            if (medio === 'TRANSFERENCIA') {
                const cuenta = document.getElementById('cuentaInput').value.trim();
                if (!cuenta) { Swal.fire({ icon: 'warning', title: 'Falta la cuenta' }); return; }
                fd.append('cuenta', cuenta);
            } else {
                const telefono = document.getElementById('telefonoInput').value.trim();
                if (!telefono) { Swal.fire({ icon: 'warning', title: 'Falta el teléfono' }); return; }
                fd.append('telefono', telefono);
            }
        }

        const confirmed = await Swal.fire({
            title: '¿Confirmar este pago?',
            text: 'Esta acción marcará la orden como PAGADA.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        try {
            const resp = await fetch(`${BASE}/pagar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Pago registrado!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // REVERSAR
    // =========================================================================
    document.getElementById('btnReversar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Reversar a Órdenes de Pago?',
            text: 'Se devolverá la orden para corrección antes de pagar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('id', TESORERIA_ID);
        fd.append('orden_pago_id', ORDEN_PAGO_ID);

        try {
            const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false })
                .then(() => window.location.href = BASE);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });
});