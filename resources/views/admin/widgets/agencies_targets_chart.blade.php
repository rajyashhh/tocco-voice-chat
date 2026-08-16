<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">🏆 {{ __('Top Agencies by Achieved Targets') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="agenciesTargetsChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

<script>
    const ctxTargets = document.getElementById("agenciesTargetsChart").getContext("2d");

    new Chart(ctxTargets, {
        type: 'bar',
        data: {
            labels: @json($labels),
            datasets: [{
                label: '{{ __("Achieved Targets") }}',
                data: @json($data),
                backgroundColor: [
                    '#4ade80','#60a5fa','#f87171','#fbbf24',
                    '#a78bfa','#f472b6','#38bdf8','#facc15',
                    '#ef4444','#10b981'
                ]
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true },
                title: {
                    display: true,
                    text: '{{ __("Top 10 Agencies by Targets Achieved") }}'
                }
            },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>
