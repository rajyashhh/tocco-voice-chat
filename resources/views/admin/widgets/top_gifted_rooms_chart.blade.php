<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">🎁 {{ __('Top 10 Rooms by Gifts Sent') }}</h3>
    </div>
    <div class="box-body">
        <canvas id="giftedRoomsChart"></canvas>
    </div>
</div>

<script>
    const ctxGift = document.getElementById('giftedRoomsChart').getContext('2d');

    new Chart(ctxGift, {
        type: 'bar',
        data: {
            labels: @json($labels),
            datasets: [{
                label: '{{ __("Total Gifts") }}',
                data: @json($data),
                backgroundColor: [
                    '#f87171','#60a5fa','#34d399','#fbbf24',
                    '#a78bfa','#f472b6','#38bdf8','#facc15',
                    '#ef4444','#10b981'
                ]
            }]
        },
        options: {
            indexAxis: 'y', // horizontal bars ✅
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
</script>
