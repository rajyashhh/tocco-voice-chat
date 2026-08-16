{{-- <div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top 10 Rooms by Gifts Sent') }}</h3>
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
</script> --}}
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Top 10 Rooms by Gifts Sent') }}</h3>
    </div>
    <div class="box-body">
        <canvas id="giftedRoomsChart"></canvas>
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
    function loadTopGiftedRooms() {
        if (!window.topGiftedRoomsLoaded) {
            window.topGiftedRoomsLoaded = true;
            $.ajax({
                url: "{{ url($prefix . '/statistics/top-room-gifts') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    const ctxGift = document.getElementById('giftedRoomsChart').getContext('2d');

                    new Chart(ctxGift, {
                        type: 'bar',
                        data: {
                            labels: response.labels,
                            datasets: [{
                                label: '{{ __("Total Gifts") }}',
                                data: response.data,
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
                },
                error: function(xhr, status, error) {
                    console.error("Error loading chart data:", error);
                }
            });
        }
    }

    $(document).ready(function() {
        loadTopGiftedRooms();
    });
</script>
