<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('Peak Hour Analytics') }}</h3>
        <select id="peak-filter" class="form-control" style="width: 200px; display:inline-block;">
            <option value="day">{{ __('Today') }}</option>
            <option value="week">{{ __('This Week') }}</option>
            <option value="month">{{ __('This Month') }}</option>
        </select>
    </div>
    <div class="box-body" style="height:245px;">
        <canvas id="peakChart" style="width:100%; height:100%;"></canvas>
    </div>
</div>

@php
    if (request()->is('admin') || request()->is('admin/*')) {
        $fetchUrl = admin_url('superadmin/peak-hours');
    } elseif (request()->is('superadmin') || request()->is('superadmin/*')) {
        $fetchUrl = superAdmin_url('peak-hours');
    } elseif (request()->is('areaManager') || request()->is('areaManager/*')) {
        $fetchUrl = areaManager_url('peak-hours');
    } else {
        $fetchUrl = url('peak-hours');
    }
@endphp


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const translations = {
        count_sessions: "{{ __('count_sessions') }}",

    };
</script>
<script>
    let peakChart;

    function renderChart(labels, data) {
        const ctx = document.getElementById('peakChart').getContext('2d');

        if (peakChart) {
            peakChart.destroy();
        }

        peakChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: translations.count_sessions,
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    function loadPeakData(period = 'day') {
        $.ajax({
            url: "{{ $fetchUrl }}",
            data: { period: period },
            success: function (res) {
                if (res.success) {
                    renderChart(res.labels, res.data);
                } else {
                    renderChart([], []);
                }
            }
        });
    }

    $('#peak-filter').on('change', function () {
        loadPeakData($(this).val());
    });
    $(document).on('pjax:end', function() {
    loadPeakData(); 
});
</script>
