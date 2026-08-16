<div class="stats-container">
    <div class="row g-3">
        <!-- BD Count -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>{{ __('Bd Count') }}</h3>
                <p class="amount" data-bdstat="bdCount">0</p>
                <a href="{{ admin_url('usersBD') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Total BD Salary -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
                <h3>{{ __('Total BD Salary') }}</h3>
                <p class="amount" data-bdstat="totalBDSalary">0</p>
                <a href="{{ admin_url('bd-salaries') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Total Cut Amount -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <h3>{{ __('Total Cut Amount') }}</h3>
                <p class="amount" data-bdstat="totalBDCut">0</p>
                <a href="{{ admin_url('bd-salaries') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Average Agencies per BD -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>{{ __('Average Agencies Per BD') }}</h3>
                <p class="amount" data-bdstat="averageAgenciesPerBD">0</p>
                <a href="{{ admin_url('usersBD') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>
    </div>
</div>

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
    function updateBdStats() {
        $.ajax({
            url: '{{ url($prefix . "/statistics/bd-stats") }}',
            type: 'GET',
            beforeSend: function () {
                $('#refreshBdStats').html('<i class="fa fa-spinner fa-spin"></i> {{ __("Loading...") }}');
            },
            success: function (data) {
                $('[data-bdstat="bdCount"]').text(data.bdCount);
                $('[data-bdstat="totalBDSalary"]').text(data.totalBDSalary);
                $('[data-bdstat="totalBDCut"]').text(data.totalBDCut);
                $('[data-bdstat="averageAgenciesPerBD"]').text(data.averageAgenciesPerBD);

                $('#refreshBdStats').html('<i class="fa fa-refresh me-1"></i> {{ __("Refresh Stats") }}');
            },
            error: function () {
                alert('{{ __("Error loading BD stats") }}');
                $('#refreshBdStats').html('<i class="fa fa-refresh me-1"></i> {{ __("Refresh Stats") }}');
            }
        });
    }

    $(function () {
        updateBdStats();

        $('#refreshBdStats').on('click', function () {
            updateBdStats();
        });
    });

    // Listen for PJAX completion to reload data
    $(document).on('pjax:complete', function () {
        updateBdStats();
    });
</script>
