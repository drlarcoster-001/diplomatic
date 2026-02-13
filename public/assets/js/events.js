/**
 * MÓDULO: CONFIGURACIÓN - SCRIPTS
 * Archivo: public/assets/js/events.js
 */

function filterLogs() {
    const form = document.getElementById('filter-events');
    const basePath = form.getAttribute('data-basepath');
    const params = new URLSearchParams(new FormData(form)).toString();
    const container = document.getElementById('event-logs');

    container.innerHTML = '<div class="text-info"># Sincronizando con el servidor...</div>';

    fetch(`${basePath}/settings/eventos/filter?${params}`)
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Error interno');
            return data;
        })
        .then(data => {
            container.innerHTML = '';
            if (data.length > 0) {
                data.forEach(log => {
                    const dateStr = new Date(log.created_at).toLocaleString('es-ES');
                    container.innerHTML += `
                    <div class="log-line mb-1">
                        <span class="log-date">[${dateStr}]</span>
                        <span class="log-ip mx-2" style="color:#0ff!important;font-weight:bold;">[IP: ${log.ip_address}]</span>
                        <span class="text-info fw-bold">@${log.user_id || '1'}</span>
                        <span class="text-success fw-bold mx-2">:: ${log.action}</span>
                        <span class="text-white fw-bold">${log.description}</span>
                    </div>`;
                });
            } else {
                container.innerHTML = '<div class="text-warning"># Sin registros encontrados.</div>';
            }
            container.innerHTML += '<div class="cursor">_</div>';
        })
        .catch(err => {
            container.innerHTML = `<div class="text-danger"># ERROR: ${err.message}</div>`;
        });
}

function clearFilters() {
    document.getElementById('filter-events').reset();
    filterLogs();
}

function exportConsoleToCSV() {
    const lines = document.querySelectorAll('.log-line');
    if (!lines.length) return;
    let csv = "FECHA,IP,USUARIO,ACCION,DESCRIPCION\n";
    lines.forEach(l => {
        const d = l.querySelector('.log-date').innerText.replace(/[\[\]]/g, '');
        const i = l.querySelector('.log-ip').innerText.replace(/[\[\]IP: ]/g, '');
        const u = l.querySelector('.text-info').innerText.replace('@', '');
        const a = l.querySelector('.log-action')?.innerText.replace(':: ', '') || 'ACTION';
        const ds = l.querySelector('.text-white').innerText.replace(/"/g, '""');
        csv += `"${d}","${i}","${u}","${a}","${ds}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `audit_view_${new Date().getTime()}.csv`;
    link.click();
}