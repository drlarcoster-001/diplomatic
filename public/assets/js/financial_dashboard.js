/**
 * MÓDULO: FINANCIERO / DASHBOARD
 * ARCHIVO: public/assets/js/financial_dashboard.js
 * PROPÓSITO: Renderiza la gráfica de barras de ingresos vs egresos
 *            de los últimos 6 meses usando Chart.js.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const data = window.GRAFICA_DATA || [];
    if (!data.length) return;

    const labels   = data.map(d => d.label);
    const ingresos = data.map(d => d.ingreso);
    const egresos  = data.map(d => d.egreso);

    const ctx = document.getElementById('graficaMensual');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: ingresos,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    borderRadius: 6,
                },
                {
                    label: 'Egresos',
                    data: egresos,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    borderWidth: 1,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => `$${ctx.parsed.y.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => `$${val.toLocaleString('es-VE')}`
                    }
                }
            }
        }
    });
});