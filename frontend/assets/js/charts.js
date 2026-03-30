/* ============================================================
   charts.js — Dashboard Charts (Chart.js)
   Used by admin/reports.html
   ============================================================ */

function renderStatusChart(canvasId, statuses, existingChart) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    if (existingChart) existingChart.destroy();

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statuses.map(s => s.label),
            datasets: [{
                data: statuses.map(s => s.count),
                backgroundColor: [
                    '#dbeafe', '#fed7aa', '#dcfce7', '#fee2e2'
                ],
                borderColor: [
                    '#3b82f6', '#f97316', '#22c55e', '#ef4444'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12 } }
                }
            }
        }
    });
}

function renderZoneChart(canvasId, zones, existingChart) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !zones?.length) return;

    if (existingChart) existingChart.destroy();

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: zones.map(z => z.zone),
            datasets: [{
                label: 'Parcels',
                data: zones.map(z => z.total),
                backgroundColor: '#dbeafe',
                borderColor: '#3b82f6',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}
