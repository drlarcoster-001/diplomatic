/**
 * MÓDULO: CONFIGURACIÓN / RESPALDO DEL SISTEMA
 * ARCHIVO: public/assets/js/settings_backup.js
 * PROPÓSITO: Botón único que dispara la secuencia automática y real de
 *            descargas (Sistema, Público, Uploads, Raíz, Enrollments y
 *            SQL troceados, Instrucciones). El popup avanza solo cuando
 *            cada descarga realmente termina (fetch + blob + a.click()),
 *            no con un temporizador fijo. Lee el header X-Total-Partes
 *            para ajustar el conteo real de partes del SQL, ya que el
 *            plan inicial solo es un estimado.
 * VERSIÓN: 11.0.0 - Reemplaza los enlaces individuales por un botón único
 *          con secuencia automática y troceo dinámico.
 */

(function () {
    'use strict';

    const BASE = window.DIPLOMATIC_BASE_PATH || '';

    async function descargarBlob(url, nombreSugerido) {
        const resp = await fetch(url, { credentials: 'same-origin' });
        if (!resp.ok) {
            throw new Error(`Error al descargar ${nombreSugerido} (HTTP ${resp.status})`);
        }

        const totalPartesHeader = resp.headers.get('X-Total-Partes');
        const disposition = resp.headers.get('Content-Disposition') || '';
        let filename = nombreSugerido;
        const match = disposition.match(/filename="([^"]+)"/);
        if (match) filename = match[1];

        const blob = await resp.blob();
        const blobUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(blobUrl);

        return {
            totalPartes: totalPartesHeader ? parseInt(totalPartesHeader, 10) : null,
        };
    }

    function actualizarProgreso(mensaje, pasoActual, pasoTotal) {
        Swal.update({
            html: `<div style="font-size:14px;color:#555;margin:8px 0">${mensaje}</div>
                   <div style="font-size:12px;color:#999">Paso ${pasoActual} de ${pasoTotal} — No cierres esta pestaña</div>`
        });
    }

    async function generarRespaldoCompleto() {
        const btn = document.getElementById('btnRespaldoCompleto');
        if (!btn || btn.dataset.procesando === '1') return;
        btn.dataset.procesando = '1';
        btn.disabled = true;

        Swal.fire({
            title: 'Generando respaldo...',
            html: '<div style="font-size:14px;color:#555;margin:8px 0">Calculando plan de respaldo...</div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            // 1. Plan inicial: cuántas partes tiene cada componente
            const planResp = await fetch(`${BASE}/settings/database/get-plan`, { credentials: 'same-origin' });
            const plan = await planResp.json();
            if (!plan.ok) throw new Error(plan.error || 'No se pudo calcular el plan de respaldo.');

            const enrollPartes = plan.enrollments;
            let sqlPartesEstimadas = plan.sql;
            let pasoTotal = plan.sistema + plan.publico + plan.uploads + plan.raiz
                          + enrollPartes + sqlPartesEstimadas + plan.instrucciones;
            let paso = 0;

            // 2. Sistema
            paso++;
            actualizarProgreso('Comprimiendo código fuente (app + tools)...', paso, pasoTotal);
            await descargarBlob(`${BASE}/settings/database/download-sistema`, 'diplomatic_sistema.zip');

            // 3. Público
            paso++;
            actualizarProgreso('Comprimiendo carpeta pública...', paso, pasoTotal);
            await descargarBlob(`${BASE}/settings/database/download-publico`, 'diplomatic_publico.zip');

            // 4. Uploads generales
            paso++;
            actualizarProgreso('Comprimiendo documentos generales...', paso, pasoTotal);
            await descargarBlob(`${BASE}/settings/database/download-uploads`, 'diplomatic_uploads.zip');

            // 5. Raíz
            paso++;
            actualizarProgreso('Comprimiendo archivos de la raíz...', paso, pasoTotal);
            await descargarBlob(`${BASE}/settings/database/download-raiz`, 'diplomatic_raiz.zip');

            // 6. Enrollments (troceado, cantidad exacta según el plan)
            for (let i = 1; i <= enrollPartes; i++) {
                paso++;
                actualizarProgreso(`Comprimiendo enrollments... (parte ${i} de ${enrollPartes})`, paso, pasoTotal);
                await descargarBlob(
                    `${BASE}/settings/database/download-enrollments?parte=${i}`,
                    `diplomatic_enrollments_parte${i}.zip`
                );
            }

            // 7. SQL (troceado; el total real se confirma tras generar la parte 1)
            let sqlPartesReales = sqlPartesEstimadas;
            for (let i = 1; i <= sqlPartesReales; i++) {
                paso++;
                actualizarProgreso(`Generando dump SQL... (parte ${i} de ${sqlPartesReales})`, paso, pasoTotal);
                const resultado = await descargarBlob(
                    `${BASE}/settings/database/download-sql?parte=${i}`,
                    `diplomatic_sql_parte${i}.zip`
                );
                if (i === 1 && resultado.totalPartes && resultado.totalPartes !== sqlPartesReales) {
                    pasoTotal += (resultado.totalPartes - sqlPartesReales);
                    sqlPartesReales = resultado.totalPartes;
                }
            }

            // 8. Instrucciones (con los conteos reales ya confirmados)
            paso++;
            actualizarProgreso('Generando instrucciones de restauración...', paso, pasoTotal);
            await descargarBlob(
                `${BASE}/settings/database/download-instrucciones?sql_partes=${sqlPartesReales}&enrollments_partes=${enrollPartes}`,
                'INSTRUCCIONES_RESTAURACION.txt'
            );

            Swal.fire({
                icon: 'success',
                title: 'Respaldo completo generado',
                html: `<div style="font-size:14px;color:#555">
                         Se descargaron ${pasoTotal} archivos: código fuente, público, uploads,
                         raíz, ${enrollPartes} parte(s) de enrollments, ${sqlPartesReales} parte(s) de SQL
                         e instrucciones de restauración.
                       </div>`,
                confirmButtonText: 'Entendido'
            });

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error al generar el respaldo',
                text: err.message || 'Ocurrió un error inesperado.'
            });
        } finally {
            btn.dataset.procesando = '0';
            btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btnRespaldoCompleto');
        if (btn) btn.addEventListener('click', generarRespaldoCompleto);
    });
})();