<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">💸 {{ __('Top Senders') }}</h3>
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
</script>
