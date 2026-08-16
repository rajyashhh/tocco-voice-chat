<div class="box box-success">
    <div class="box-header"><h4>{{ __('Live Hours') }}</h4></div>
    <div class="box-body" style="height:380px;">
        <canvas id="salaryChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('superadmin*')) {
        $prefix = 'superadmin';
    } elseif (request()->is('areaManager*')) {
        $prefix = 'areaManager';
    } else {
        $prefix = 'admin';
    }
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let salaryChart;

    function loadUsersChart() {
        const canvas = document.getElementById('salaryChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        if (salaryChart) {
            salaryChart.destroy();
        }

        salaryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: '{{ __("Live Hours") }}',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        $.ajax({
            url: '{{ url($prefix . "/statistics/top-users-data") }}',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                salaryChart.data.labels = data.labels;
                salaryChart.data.datasets[0].data = data.data;
                salaryChart.update();
            },
            error: function(err) {
                console.error('AJAX Error loading chart data:', err);
            }
        });
    }

    // Execute immediately - this is the key!
    loadUsersChart();

    // Listen for PJAX completion to reload data
    $(document).on('pjax:complete', function() {
        setTimeout(loadUsersChart, 300);
    });

    // Also listen for pjax:end as backup
    $(document).on('pjax:end', function() {
        setTimeout(loadUsersChart, 300);
    });
</script>
