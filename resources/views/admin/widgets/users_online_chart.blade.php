<div class="box box-success">
    <div class="box-header with-border">
    <h4>{{ __('title_user') }}</h4>
    </div>
    <div class="box-body">
        <canvas id="usersOnlineChart" height="430"></canvas>
    </div>
</div>

@php
    $path = request()->path();

    info($path);

    if (str_starts_with($path, 'admin')) {
        $fetchUrl = admin_url('superadmin/users-online-stats');
    } elseif (str_starts_with($path, 'superadmin')) {
        $fetchUrl = superAdmin_url('users-online-stats');
    } elseif (str_starts_with($path, 'areaManager')) {
        $fetchUrl = areaManager_url('users-online-stats');
    } else {
        $fetchUrl = url('users-online-stats');
    }
@endphp

@if(request()->is('superadmin') || request()->is('areaManager') || request()->is('areaManager') || request()->is('admin/superadmin/statistics'))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('usersOnlineChart').getContext('2d');

            let chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Users'], // نقطة واحدة فقط على X
                    datasets: [
                        {
                            label: "{{ __('online_users') }}",

                            data: [0], // يبدأ من 0
                            borderColor: '#28a745',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
                        },
                        {
                            label: "{{ __('offline_users') }}",
                            data: [0], // يبدأ من 0
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

            function fetchData() {
                fetch("{{ $fetchUrl }}", {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(async res => {
                        const contentType = res.headers.get("content-type");
                        if (contentType && contentType.includes("application/json")) {
                            return res.json();
                        } else {
                            const text = await res.text();
                            throw new Error("Expected JSON, got: " + text);
                        }
                    })
                    .then(data => {
                        // تحديث الخطوط بالنقاط الجديدة
                        chart.data.datasets[0].data = [data.online];   // Online
                        chart.data.datasets[1].data = [data.offline];  // Offline
                        chart.update();
                        console.log("Online:", data.online, "Offline:", data.offline);
                    })
                    .catch(err => {
                        console.error("Fetch Error:", err); // طباعة الأخطاء في Console
                    });
            }

            fetchData();
            setInterval(fetchData, 10000);
        });
    </script>
@endif
