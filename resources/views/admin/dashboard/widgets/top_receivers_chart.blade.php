{{-- <div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top Receivers') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="topReceiversRadar" style="width:100%; height:100%;"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById("topReceiversRadar"), {
        type: 'radar',
        data: {
            labels: @json($labels),
            datasets: [{
                label: '{{ __("Total Received") }}',
                data: @json($data),
                backgroundColor: 'rgba(34,197,94,0.2)',
                borderColor: '#22c55e',
                pointBackgroundColor: '#22c55e'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: true, text: '{{ __("Top 10 Receivers by Gifts Received") }}' }
            }
        }
    });
</script> --}}

<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top Receivers') }}</h3>
    </div>
    <div class="box-body" style="height:500px;">
        <canvas id="topReceiversRadar" style="width:100%; height:100%;"></canvas>
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
function loadTopReceiversChart() {
    const canvas = document.getElementById("topReceiversRadar");
    if (!canvas) return;

    // Check if chart already exists
    if (window.topReceiversChartInstance) {
        window.topReceiversChartInstance.destroy();
    }

    $.ajax({
        url: '{{ url($prefix . "/statistics/top-receiver") }}',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            const ctx = canvas.getContext("2d");

            window.topReceiversChartInstance = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: response.labels,
                    datasets: [{
                        label: '{{ __("Total Received") }}',
                        data: response.data,
                        backgroundColor: 'rgba(34,197,94,0.2)',
                        borderColor: '#22c55e',
                        pointBackgroundColor: '#22c55e'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: '{{ __("Top 10 Receivers by Gifts Received") }}'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            grid: { color: '#d1d5db' },
                            angleLines: { color: '#d1d5db' },
                            pointLabels: { font: { size: 14 } }
                        }
                    }
                }
            });
        },
        error: function() {
            console.error('Error loading top receivers chart data');
        }
    });
}

// Load immediately - no DOM waiting, no multiple event listeners
loadTopReceiversChart();
</script>

