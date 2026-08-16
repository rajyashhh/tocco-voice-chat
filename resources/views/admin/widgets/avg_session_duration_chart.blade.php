<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">⏳ {{ __('Average Session Duration per Room') }}</h3>
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
    });</script>
