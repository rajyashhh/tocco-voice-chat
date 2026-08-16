<div class="box box-success">
    <div class="box-header with-border">
    <h4>{{ __('title_user') }}</h4>
    </div>
    <div class="box-body" style="height:748px;">
        <canvas id="usersOnlineChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('superadmin*')) {
        $fetchUrl = "superadmin/statistics/users-online-stats";
    } elseif (request()->is('areaManager*')) {
        $fetchUrl = "areaManager/statistics/users-online-stats";
    } else {
        $fetchUrl = "admin/statistics/users-online-stats";
    }
    $onlineUsersLabel = __('online_users');
    $offlineUsersLabel = __('offline_users');
@endphp

@if(request()->is('superadmin') || request()->is('admin/superadmin/statistics') || request()->is('admin') ||  request()->is('areaManager'))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let usersOnlineChart;

        function renderUsersOnlineChart(online, offline) {
            const canvas = document.getElementById('usersOnlineChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            if (usersOnlineChart) {
                usersOnlineChart.destroy();
            }

            usersOnlineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Users'], // نقطة واحدة فقط على X
                    datasets: [
                        {
                            label: "{{ __('online_users') }}",
                            data: [online], // يبدأ من 0
                            borderColor: '#28a745',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
                        },
                        {
                            label: "{{ __('offline_users') }}",
                            data: [offline], // يبدأ من 0
                            borderColor: '#dc3545',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true, // المحور X يبدأ من 0
                            title: {
                                display: true,
                                text: "{{ __('online_users') }}",
                            }
                        },
                        y: {
                            beginAtZero: true, // المحور Y يبدأ من 0
                            title: {
                                display: true,
                                text: "{{ __('offline_users') }}"
                            }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }

        function loadUsersOnlineData() {
            $.ajax({
                url: "{{ $fetchUrl }}",
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log("Online:", data.online, "Offline:", data.offline);
                    renderUsersOnlineChart(data.online || 0, data.offline || 0);
                },
                error: function(err) {
                    console.error("Error:", err);
                    renderUsersOnlineChart(0, 0);
                }
            });
        }

        // تنفيذ مباشر - هذا هو المفتاح!
        loadUsersOnlineData();

        // تحديث كل 10 ثواني
        if (window.usersOnlineInterval) {
            clearInterval(window.usersOnlineInterval);
        }
        window.usersOnlineInterval = setInterval(loadUsersOnlineData, 10000);
    </script>
@endif
