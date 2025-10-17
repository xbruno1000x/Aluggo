document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('patrimonioChart') as HTMLCanvasElement | null;
    if (!canvas) return;
    const raw = canvas.getAttribute('data-series');
    if (!raw) return;
    let series = [];
    try { series = JSON.parse(raw); } catch (e) { return; }

    const labels = series.map((s: any) => s.label);
    const data = series.map((s: any) => Number(s.cumulative));

    // dynamic import of Chart.js via CDN would be preferable, but assume Chart.js is available
    // If not, the compiled assets or CDN must provide Chart
    // @ts-ignore
    if (typeof Chart === 'undefined') return;

    const ctx = canvas.getContext('2d');
    if (!ctx || !labels.length) return;

    // @ts-ignore
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Patrimônio (acumulado)',
                data,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.2,
                fill: true,
            }]
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        callback: function(value: any) { return 'R$ ' + Number(value).toLocaleString('pt-BR'); }
                    }
                }
            }
        }
    });
});
