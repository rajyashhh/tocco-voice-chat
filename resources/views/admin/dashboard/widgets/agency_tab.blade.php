<div class="stats-container">
    <div class="row g-3">
        <!-- Agencies Count -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-building"></i></div>
                <h3>{{ __('Agencies Count') }}</h3>
                <p class="amount" data-stat="agencyCount">0</p>
                <a href="{{ admin_url('agencies') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Total Agency Salary -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-building"></i></div>
                <h3>{{ __('Total Agency Salary') }}</h3>
                <p class="amount" data-stat="agency_salaries">0</p>
                <a href="{{ admin_url('agencies') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Total Users Salary -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-money-bill"></i></div>
                <h3>{{ __('Total Users Salary') }}</h3>
                <p class="amount" data-stat="user_salaries">0</p>
                <a href="{{ admin_url('ag/users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Active Agencies -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-building"></i></div>
                <h3>{{ __('Active Agencies') }}</h3>
                <p class="amount" data-stat="activeAgencies">0</p>
                <a href="{{ admin_url('agencies?active=true') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- New Agencies Today -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-plus"></i></div>
                <h3>{{ __('New Agencies Today') }}</h3>
                <p class="amount" data-stat="newAgenciesToday">0</p>
                <a href="{{ admin_url('agencies?created=today') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- New Agencies This Month -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-calendar"></i></div>
                <h3>{{ __('New Agencies This Month') }}</h3>
                <p class="amount" data-stat="newAgenciesMonth">0</p>
                <a href="{{ admin_url('agencies?created=month') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Average Agency Wallet -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-money-bill"></i></div>
                <h3>{{ __('Average Agency Wallet') }}</h3>
                <p class="amount" data-stat="avgAgencyWallet">0</p>
                <a href="{{ admin_url('agencies') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Total Members in Agencies -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <h3>{{ __('Total Members in Agencies') }}</h3>
                <p class="amount" data-stat="totalMembers">0</p>
                <a href="{{ admin_url('users?agencyMembers=1') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Avg Members Per Agency -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-user"></i></div>
                <h3>{{ __('Avg Members Per Agency') }}</h3>
                <p class="amount" data-stat="avgMembersPerAgency">0</p>
                <a href="{{ admin_url('agencies') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Pending Join Requests -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-hourglass"></i></div>
                <h3>{{ __('Pending Join Requests') }}</h3>
                <p class="amount" data-stat="pendingJoins">0</p>
                <a href="{{ admin_url('agencies?pending=1') }}"><i
                        class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>

        <!-- Diamonds Achieved by Hosts -->
        <div class="col-md-3 col-sm-6">
            <div class="card finance-card">
                <div class="card-icon"><i class="fa-solid fa-gem"></i></div>
                <h3>{{ __('Diamonds Achieved by Hosts') }}</h3>
                <p class="amount" data-stat="diamondsAchieved">0</p>
                <a href="{{ admin_url('ag/users') }}"><i class="fa fa-arrow-circle-right"></i> {{ __('More') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- <style>
    .info-box {
        position: relative;
        min-height: 100px;
        border-radius: 8px;
        overflow: hidden;
        padding: 12px;
    }
    .info-box .info-box-more {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(0,0,0,0.15);
        height: 30px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        transition: background 0.2s ease;
    }
    .info-box:hover .info-box-more {
        background: rgba(0,0,0,0.3);
    }
</style> --}}

<style>
    .info-box {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 100px;
        border-radius: 12px;
        overflow: hidden;
        padding: 15px 20px;
        background-color: #fff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .info-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .info-box-icon {
        font-size: 2.5rem;
        width: 65px;
        height: 65px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .info-box-content {
        flex: 1;
        margin-left: 15px;
    }

    .info-box-text {
        font-size: 1.5rem;
        color: #040404;
        margin-bottom: 5px;
    }

    .info-box-number {
        font-size: 1.4rem;
        font-weight: 600;
        color: #222;
    }

    .info-box .info-box-more {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(0, 0, 0, 0.15);
        height: 30px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        transition: background 0.2s ease;
    }

    .info-box:hover .info-box-more {
        background: rgba(0, 0, 0, 0.3);
    }

    /* For large tablets and small laptops */
    @media (max-width: 1200px) {
        .info-box {
            padding: 12px 16px;
        }

        .info-box-icon {
            font-size: 2rem;
            width: 55px;
            height: 55px;
        }

        .info-box-number {
            font-size: 1.2rem;
        }
    }

    /* For tablets */
    @media (max-width: 992px) {
        .info-box {
            flex-direction: row;
            text-align: left;
        }

        .info-box-content {
            margin-left: 10px;
        }
    }

    /* For small tablets & landscape phones */
    @media (max-width: 768px) {
        .info-box {
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-height: 150px;
            padding: 18px 12px;
        }

        .info-box-icon {
            margin-bottom: 10px;
            font-size: 2.2rem;
        }

        .info-box-content {
            margin-left: 0;
        }

        .info-box-text {
            font-size: 0.95rem;
        }

        .info-box-number {
            font-size: 1.25rem;
        }

        .info-box .info-box-more {
            position: relative;
            margin-top: 8px;
            height: auto;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px 0;
            width: 100%;
        }
    }

    /* For small mobile screens */
    @media (max-width: 576px) {
        .info-box {
            min-height: 130px;
            padding: 14px 10px;
            flex-direction: column;
            align-items: center;
        }

        .info-box-icon {
            font-size: 1.8rem;
            width: 50px;
            height: 50px;
        }

        .info-box-text {
            font-size: 0.9rem;
        }

        .info-box-number {
            font-size: 1.1rem;
        }

        .info-box .info-box-more {
            font-size: 0.85rem;
            padding: 4px 0;
        }
    }
</style>

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
    $(function () {
        function updateStats() {
            $.ajax({
                url: '{{ url($prefix . "/statistics/agency-stats") }}', // ✅ matches your route
                type: 'GET',
                beforeSend: function () {
                    $('#refreshStats').html('<i class="fa fa-spinner fa-spin"></i> {{ __("Loading...") }}');
                },
                success: function (data) {
                    // ✅ update each stat dynamically
                    $('[data-stat="agencyCount"]').text(data.agencyCount);
                    $('[data-stat="agency_salaries"]').text(data.agency_salaries);
                    $('[data-stat="user_salaries"]').text(data.user_salaries);
                    $('[data-stat="activeAgencies"]').text(data.activeAgencies);
                    $('[data-stat="newAgenciesToday"]').text(data.newAgenciesToday);
                    $('[data-stat="newAgenciesMonth"]').text(data.newAgenciesMonth);
                    $('[data-stat="avgAgencyWallet"]').text(data.avgAgencyWallet);
                    $('[data-stat="totalMembers"]').text(data.totalMembers);
                    $('[data-stat="avgMembersPerAgency"]').text(data.avgMembersPerAgency);
                    $('[data-stat="pendingJoins"]').text(data.pendingJoins);
                    $('[data-stat="diamondsAchieved"]').text(data.diamondsAchieved);

                    $('#refreshStats').html('<i class="fa fa-refresh me-1"></i> {{ __("Refresh Stats") }}');
                },
                error: function () {
                    alert('{{ __("Error loading stats") }}');
                    $('#refreshStats').html('<i class="fa fa-refresh me-1"></i> {{ __("Refresh Stats") }}');
                }
            });
        }

        // Load stats once on page load
        updateStats();

        // Optional refresh button
        $('#refreshStats').on('click', function () {
            updateStats();
        });
    });
</script>

