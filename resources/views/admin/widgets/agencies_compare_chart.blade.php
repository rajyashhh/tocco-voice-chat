<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">🏁 {{ __('Agencies Target Comparison') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="agenciesCompareChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById("agenciesCompareChart"), {
        type: 'bar',
        data: {
            labels: [
                "{{ __('Achieved Targets') }}",
                "{{ __('Not Achieved') }}"
            ],
            datasets: [{
                label: "{{ __('Agencies') }}",
                data: [{{ $achieved }}, {{ $notAchieved }}],
                backgroundColor: ['#22c55e', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: "{{ __('Comparison of Agencies Achieved vs Not Achieved') }}"
                }
            },
            scales: { y: { beginAtZero: true, precision: 0 } }
        }
    });
</script>
