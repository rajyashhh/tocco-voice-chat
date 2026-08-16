{{-- <div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Average Session Duration per Room') }}</h3>
    </div>
    <div class="box-body">
        <canvas id="avgSessionChart"></canvas>
    </div>
</div>

<script>
    const ctxAvg = document.getElementById('avgSessionChart').getContext('2d');
    new Chart(ctxAvg, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [{
                label: '{{ __("Average Duration (hours)") }}',
                data: @json($data),
                fill: false,
                borderColor: '#6366f1',
                tension: 0.3,
                pointBackgroundColor: '#facc15'
            }]
        },
        options: {
            responsive: true
        }
    });</script> --}}

    <div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Average Session Duration per Room') }}</h3>
    </div>
    <div class="box-body">
        <canvas id="avgSessionChart"></canvas>
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
    function loadAvgSessionDuration() {
        if (!window.avgSessionDurationLoaded) {
            window.avgSessionDurationLoaded = true;
            $.ajax({
                url: "{{ url($prefix . '/statistics/active-rooms') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    const ctxAvg = document.getElementById('avgSessionChart').getContext('2d');

                    new Chart(ctxAvg, {
                        type: 'line',
                        data: {
                            labels: response.labels,
                            datasets: [{
                                label: '{{ __("Average Duration (hours)") }}',
                                data: response.data,
                                fill: false,
                                borderColor: '#6366f1',
                                tension: 0.3,
                                pointBackgroundColor: '#facc15'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.parsed.y.toFixed(2)} hours`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: '{{ __("Hours") }}'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: '{{ __("Rooms") }}'
                                    }
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
    }

    $(document).ready(function() {
        loadAvgSessionDuration();
    });
</script>

