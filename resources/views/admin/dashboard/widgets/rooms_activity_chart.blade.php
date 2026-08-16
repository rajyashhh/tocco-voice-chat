<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Rooms Activity Analytics') }}</h3>
        <select id="rooms-activity-filter" class="form-control" style="width:200px; display:inline-block;">
            <option value="day">{{ __('Last 7 Days') }}</option>
            <option value="week">{{ __('Last 4 Weeks') }}</option>
            <option value="month">{{ __('Last 6 Months') }}</option>
        </select>
    </div>
    <div class="box-body" style="height:365px;">
        <canvas id="roomsActivityChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('superadmin*')) {
        $fetchUrl = "superadmin/statistics/rooms-activity";
    } elseif (request()->is('areaManager*')) {
        $fetchUrl = "areaManager/statistics/rooms-activity";
    } else {
        $fetchUrl = "admin/statistics/rooms-activity";
    }
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let roomsActivityChart;

    function renderRoomsActivity(labels, newRooms, inactiveRooms) {
        const ctx = document.getElementById('roomsActivityChart').getContext('2d');

        if (roomsActivityChart) {
            roomsActivityChart.destroy();
        }

        roomsActivityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "{{ __('New Rooms') }}",
                        data: newRooms,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.3)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: "{{ __('Inactive Rooms') }}",
                        data: inactiveRooms,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.3)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    function loadRoomsActivity(period = 'day') {
        if (!window.roomsActivityLoaded) {
            window.roomsActivityLoaded = true;
            $.ajax({
                url: "{{ $fetchUrl }}",
                data: { period: period },
                success: function(res) {
                    if (res.success) {
                        renderRoomsActivity(res.labels, res.newRooms, res.inactiveRooms);
                    } else {
                        renderRoomsActivity([], [], []);
                    }
                }
            });
        }
    }

    $('#rooms-activity-filter').on('change', function () {
        // Allow filter changes to reload data
        window.roomsActivityLoaded = false;
        loadRoomsActivity($(this).val());
    });

    loadRoomsActivity();
</script>
