{{-- <div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top Senders') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="topSendersPolar" style="width:100%; height:100%;"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById("topSendersPolar"), {
        type: 'polarArea',
        data: {
            labels: @json($labels),
            datasets: [{
                data: @json($data),
                backgroundColor: [
                    '#f87171','#60a5fa','#34d399','#fbbf24',
                    '#a78bfa','#f472b6','#38bdf8','#facc15',
                    '#ef4444','#10b981'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: '{{ __("Top 10 Senders by Gift Value") }}' }
            }
        }
    });
</script> --}}

<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top Senders') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="topSendersPolar" style="width:100%; height:100%;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
function loadTopSendersChart() {
    const canvas = document.getElementById("topSendersPolar");
    if (!canvas) return;

    // Check if chart already exists
    if (window.topSendersChartInstance) {
        window.topSendersChartInstance.destroy();
    }

    $.ajax({
        url: "{{ url($prefix . '/statistics/top-sender') }}",
        type: "GET",
        dataType: "json",
        success: function(response) {
            const ctx = canvas.getContext("2d");

            window.topSendersChartInstance = new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: response.labels,
                    datasets: [{
                        data: response.data,
                        backgroundColor: [
                            '#f87171','#60a5fa','#34d399','#fbbf24',
                            '#a78bfa','#f472b6','#38bdf8','#facc15',
                            '#ef4444','#10b981'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: '#333', font: { size: 13 } }
                        },
                        title: {
                            display: true,
                            text: '{{ __("Top 10 Senders by Gift Value") }}'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const name = context.label || '';
                                    const value = context.formattedValue;
                                    return `${name}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            ticks: { display: true },
                            grid: { color: '#ddd' }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Error loading chart data:", error);
        }
    });
}

// Load immediately - no DOM waiting, no multiple event listeners
loadTopSendersChart();
</script>

