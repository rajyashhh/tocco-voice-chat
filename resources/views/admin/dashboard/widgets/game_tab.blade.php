@php
    if (request()->is('superadmin*')) {
        $prefix = 'superadmin';
    } elseif (request()->is('areaManager*')) {
        $prefix = 'areaManager';
    } else {
        $prefix = 'admin';
    }
@endphp

{{-- Today summary cards --}}
<div class="stats-container">
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                    <h3>{{ __('Active Players Today') }}</h3>
                    <p class="amount" data-gamestat="today.active_players">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-coins"></i></div>
                    <h3>{{ __('Total Played Today') }}</h3>
                    <p class="amount" data-gamestat="today.total_played">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    <h3>{{ __('Total Loss Today') }}</h3>
                    <p class="amount" data-gamestat="today.total_loss">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <h3>{{ __('Total Win Today') }}</h3>
                    <p class="amount" data-gamestat="today.total_win">0</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- All-time summary cards --}}
<div class="stats-container">
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card" id="app_profit">
                    <div class="card-icon"><i class="fa-solid fa-gamepad"></i></div>
                    <h3>{{ __('App Profit') }}</h3>
                    <p class="amount" id="appProfit">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-layer-group"></i></div>
                    <h3>{{ __('Total Played') }}</h3>
                    <p class="amount" data-gamestat="total.total_played">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    <h3>{{ __('Total Loss') }}</h3>
                    <p class="amount" data-gamestat="total.total_loss">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="cards-container mb-4" data-aos="fade-up">
                <div class="card finance-card">
                    <div class="card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <h3>{{ __('Total Win') }}</h3>
                    <p class="amount" data-gamestat="total.total_win">0</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top games / top users tables --}}
<div class="row">
    <div class="col-lg-4 col-12 mb-3">
        <div class="box box-primary shadow-sm border-0">
            <div class="box-header with-border d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa fa-gamepad me-2"></i> {{ __('Top Games Today') }}</h4>
            </div>
            <div class="box-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>{{ __('Game') }}</th>
                            <th class="text-center">{{ __('Total Played') }}</th>
                        </tr>
                        </thead>
                        <tbody id="top-games-today-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12 mb-3">
        <div class="box box-success shadow-sm border-0">
            <div class="box-header with-border d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa fa-trophy me-2"></i> {{ __('Top Games All Time') }}</h4>
            </div>
            <div class="box-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>{{ __('Game') }}</th>
                            <th class="text-center">{{ __('Total Played') }}</th>
                        </tr>
                        </thead>
                        <tbody id="top-games-total-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-12 mb-3">
        <div class="box box-warning shadow-sm border-0">
            <div class="box-header with-border d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa fa-user me-2"></i> {{ __('Top Players Today') }}</h4>
            </div>
            <div class="box-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>{{ __('User') }}</th>
                            <th class="text-center">{{ __('Total Played') }}</th>
                        </tr>
                        </thead>
                        <tbody id="top-players-today-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const prefix = @json($prefix);
        const emptyRow = (cols) =>
            `<tr><td colspan="${cols}" class="text-center text-muted py-3">{{ __('No data') }}</td></tr>`;

        function setStat(path, value) {
            const el = document.querySelector('[data-gamestat="' + path + '"]');
            if (el) el.textContent = Number(value || 0).toLocaleString();
        }

        function gameEntityRow(index, name, image, played) {
            return `
                <tr>
                    <td class="text-center fw-bold">${index + 1}</td>
                    <td class="game-entity-cell">
                        <img src="${image}" alt="">
                        <span>${$('<div>').text(name).html()}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary fs-6">${Number(played).toLocaleString()}</span>
                    </td>
                </tr>`;
        }

        function renderList(bodyId, rows, isUser) {
            const tbody = document.getElementById(bodyId);
            if (!tbody) return;
            if (!rows || rows.length === 0) {
                tbody.innerHTML = emptyRow(3);
                return;
            }
            tbody.innerHTML = rows.map((r, i) =>
                gameEntityRow(i, isUser ? r.name : r.game_name, isUser ? r.avatar_url : r.game_image, r.total_played)
            ).join('');
        }

        // Truly lazy: this function is never self-invoked on page load. It runs
        // only when chart.blade.php's tab controller opens #game (or when #game is
        // the active tab on a direct load). The in-flight guard tracks the real
        // AJAX completion so repeated tab clicks never stack duplicate requests
        // (the endpoints are server-cached, so a re-fetch is cheap anyway).
        let gameStatsInFlight = false;

        window.updateGameStats = function () {
            if (gameStatsInFlight) return;
            gameStatsInFlight = true;

            const summary = $.get(prefix + '/statistics/game-summary', function (data) {
                $('#appProfit').text(Number((data.total && data.total.app_profit) || 0).toLocaleString());
                if (data.today) {
                    setStat('today.active_players', data.today.active_players);
                    setStat('today.total_played', data.today.total_played);
                    setStat('today.total_loss', data.today.total_loss);
                    setStat('today.total_win', data.today.total_win);
                }
                if (data.total) {
                    setStat('total.total_played', data.total.total_played);
                    setStat('total.total_loss', data.total.total_loss);
                    setStat('total.total_win', data.total.total_win);
                }
            }).fail(function () {
                $('#appProfit').text('Error');
            });

            const topGames = $.get(prefix + '/statistics/game-top-games', function (data) {
                renderList('top-games-today-body', data.today, false);
                renderList('top-games-total-body', data.total, false);
            }).fail(function () {
                renderList('top-games-today-body', [], false);
                renderList('top-games-total-body', [], false);
            });

            const topUsers = $.get(prefix + '/statistics/game-top-users', function (data) {
                renderList('top-players-today-body', data, true);
            }).fail(function () {
                renderList('top-players-today-body', [], true);
            });

            $.when(summary, topGames, topUsers).always(function () {
                gameStatsInFlight = false;
            });
        };
    })();
</script>

<style>
    .game-entity-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .game-entity-cell img {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 10px;
    }
</style>
