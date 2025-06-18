
<div class="widget revenue-chart-widget col-span-2 bg-white shadow rounded-lg p-4 mb-4 max-w overflow-x-auto">    <h3 class="text-xl font-bold mb-4">Omzet &amp; Marge (12 maanden)</h3>
    <canvas id="revenueChart"></canvas>
</div>

{{-- Chart.js laden --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('revenueChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($labels),
                datasets: [
                    {
                        label: 'Omzet',
                        data: @json($currentRevenue),
                        yAxisID: 'y_revenue',
                        fill: false,
                    },
                    {
                        label: 'Marge',
                        data: @json($currentMargin),
                        yAxisID: 'y_margin',
                        fill: false,
                    },
                    {
                        label: 'Omzet vorig jaar',
                        data: @json($lastRevenue),
                        yAxisID: 'y_revenue',
                        borderDash: [5, 5],
                        fill: false,
                    },
                    {
                        label: 'Marge vorig jaar',
                        data: @json($lastMargin),
                        yAxisID: 'y_margin',
                        borderDash: [5, 5],
                        fill: false,
                    },
                ]
            },
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y_revenue: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Omzet (€)'
                        }
                    },
                    y_margin: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Marge (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    });
</script>
