@php use Carbon\Carbon; @endphp
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('User Signups Comparison (Weeks)') }}</h3>
    </div>
    <div class="box-body">
        <canvas id="weeklySignupsChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('weeklySignupsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [
                {
                    label: "{{ Carbon::now()->format('F') }}",
                    data: {!! json_encode($dataCurrent) !!},
                    backgroundColor: 'rgba(75, 192, 192, 0.7)'
                },
                {
                    label: "{{ \Carbon\Carbon::now()->subMonth()->format('F') }}",
                    data: {!! json_encode($dataPrevious) !!},
                    backgroundColor: 'rgba(255, 99, 132, 0.7)'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {position: 'top'}
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
