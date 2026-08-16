<div class="box box-success">
    <div class="box-header"><h4>{{ __('Top Users Visits') }}</h4></div>
    <div class="box-body" style="height:380px;">
        <canvas id="topUsersChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('superadmin*')) {
        $fetchUrl = "superadmin/statistics/top-users-visits";
    } elseif (request()->is('areaManager*')) {
        $fetchUrl = "areaManager/statistics/top-users-visits";
    } else {
        $fetchUrl = "admin/statistics/top-users-visits";
    }
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let topUsersChart;

    function loadTopUsers() {
        $.ajax({
            url: "{{ $fetchUrl }}",
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                const ctx = document.getElementById('topUsersChart').getContext('2d');

                if (topUsersChart) {
                    topUsersChart.destroy();
                }

                topUsersChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '{{ __("Visits") }}',
                            data: data.data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 206, 86, 0.6)',
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(153, 102, 255, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                                'rgba(199, 199, 199, 0.6)',
                                'rgba(83, 102, 255, 0.6)',
                                'rgba(255, 99, 71, 0.6)',
                                'rgba(60, 179, 113, 0.6)'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    }
                });

                console.log('Labels:', data.labels);
                console.log('Data:', data.data);
                console.log('Data:', data);
            },
            error: function(err) {
                console.error("AJAX Error:", err);
            }
        });
    }

    // Execute immediately - this is the key!
    if (!window.topUsersLoaded) {
        window.topUsersLoaded = true;
        loadTopUsers();
    }
</script>
