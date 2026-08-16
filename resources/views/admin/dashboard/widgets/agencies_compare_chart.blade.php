{{-- <div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Agencies Target Comparison') }}</h3>
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
</script> --}}
<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Agencies Target Comparison') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="agenciesCompareChart" style="width:100%; height:100%;"></canvas>
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

<script>
function loadAgenciesCompareChart() {
    const canvas = document.getElementById("agenciesCompareChart");
    if (!canvas) return;

    // Check if chart already exists
    if (window.agenciesCompareChartInstance) {
        window.agenciesCompareChartInstance.destroy();
    }

    $.ajax({
        url: "/{{ $prefix }}/statistics/comparison-agencies-target",
        type: "GET",
        dataType: "json",
        success: function(response) {
            const ctx = canvas.getContext("2d");

            window.agenciesCompareChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [
                        "{{ __('Achieved Targets') }}",
                        "{{ __('Not Achieved') }}"
                    ],
                    datasets: [{
                        label: "{{ __('Agencies') }}",
                        data: [response.achieved, response.notAchieved],
                        backgroundColor: ['#22c55e', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
        },
        error: function(xhr, status, error) {
            console.error('Error fetching agency comparison data:', error);
        }
    });
}

// Load immediately - no DOM waiting, no multiple event listeners
loadAgenciesCompareChart();
</script>
