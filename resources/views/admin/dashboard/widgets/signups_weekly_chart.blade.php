@php use Carbon\Carbon; @endphp
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('User Signups Comparison (Weeks)') }}</h3>
    </div>
    <div class="box-body" style="height:380px;">
        <canvas id="weeklySignupsChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('superadmin*')) {
        $fetchUrl = "superadmin/statistics/comparison-user-signup";
    } elseif (request()->is('areaManager*')) {
        $fetchUrl = "areaManager/statistics/comparison-user-signup";
    } else {
        $fetchUrl = "admin/statistics/comparison-user-signup";
    }
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let weeklySignupsChart;

    function loadWeeklySignups() {
        $.ajax({
            url: "{{ $fetchUrl }}",
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const ctx = document.getElementById('weeklySignupsChart').getContext('2d');

                if (weeklySignupsChart) {
                    weeklySignupsChart.destroy();
                }

                weeklySignupsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: data.currentMonth,
                                data: data.dataCurrent,
                                backgroundColor: 'rgba(75, 192, 192, 0.7)'
                            },
                            {
                                label: data.previousMonth,
                                data: data.dataPrevious,
                                backgroundColor: 'rgba(255, 99, 132, 0.7)'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            },
            error: function(err) {
                console.error("AJAX Error:", err);
            }
        });
    }

    // Execute immediately - this is the key!
    if (!window.weeklySignupsLoaded) {
        window.weeklySignupsLoaded = true;
        loadWeeklySignups();
    }
</script>
