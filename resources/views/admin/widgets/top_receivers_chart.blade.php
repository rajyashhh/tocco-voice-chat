<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">🎁 {{ __('Top Receivers') }}</h3>
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
</script>
