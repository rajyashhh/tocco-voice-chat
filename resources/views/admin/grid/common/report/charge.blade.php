<div class="box-body no-padding">
    <div class="nav-scroll-container">
        <ul class="nav nav-pills">
            <li class="{{ request()->name == 'dash' || request()->name == null ? 'active' : '' }}">
                <a href="?name=" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('dash_repo') }}
                </a>
            </li>
            <li class="{{ request()->name == 'shipping-agency-activity' ? 'active' : '' }}">
                <a href="?name=shipping-agency-activity" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('app_repo') }}
                </a>
            </li>
            <li class="{{ request()->name == 'host' ? 'active' : '' }}">
                <a href="?name=host" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('charge host agent') }}
                </a>
            </li>
            <li class="{{ request()->name == 'stripe' ? 'active' : '' }}">
                <a href="?name=stripe" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('payment gateway') }}
                </a>
            </li>
            <li class="{{ request()->name == 'in-app-purchas' ? 'active' : '' }}">
                <a href="?name=in-app-purchas" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('Recharge for self') }}
                </a>
            </li>
            <li class="{{ request()->name == 'exchange' ? 'active' : '' }}">
                <a href="?name=exchange" class="charge_action">
                    <i class="fa fa-arrow-right text-red"></i> {{ __('Convert diamonds to coins') }}
                </a>
            </li>
        </ul>
    </div>

    <div class="col">
        <h4 class="details-title">{{ __('Details') }}</h4>

        @php
            $isDashboard = request()->name == 'dash' || request()->name == null;
            $isApp = request()->name == 'shipping-agency-activity';
            $isStripe = request()->name == 'stripe';
            $isStripeNew = request()->name == 'stripenew';
            $isInApp = request()->name == 'in-app-purchas';
            $isExchange = request()->name == 'exchange';
            $isHost = request()->name == 'host';

            $getUserByUuid = function ($uuid) {
                // Avoid running queries for empty or default '0' UUIDs
                if (empty($uuid) || $uuid === '0') {
                    return null;
                }
                return \App\Models\User::where('uuid', $uuid)->first() ?? \App\Models\ShippingAgency::where('id', $uuid)->first();
            };

            $calculateReceiverValue = function ($user, $request) use ($isDashboard, $isApp, $isStripe, $isStripeNew, $isInApp) {
                if ($isDashboard || $isApp) {
                    $query = \App\Models\Charge::query();
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->sum('amount');
                } elseif ($isStripe) {
                    $query = \App\Models\CoinLog::whereNotIn('method', ['huawei_pay', 'google_pay', 'strip', 'apple_pay']);
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->sum('obtained_coins');
                } elseif ($isStripeNew) {
                    $query = \App\Models\CoinLog::where('method', 'strip');
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->sum('obtained_coins');
                } elseif ($isInApp) {
                    if ($request->name_for_url_shortcut) {
                        $query = \App\Models\CoinLog::where('method', $request->name_for_url_shortcut);
                    } else {
                        $query = \App\Models\CoinLog::whereIn('method', ['huawei_pay', 'google_pay', 'apple_pay']);
                    }
                    if ($user) {
                        $query->where('user_id', $user->id);
                    }
                    return $query->sum('obtained_coins');
                }
                return 0;
            };

            if($isDashboard || $isStripe || $isHost) {
                $fields = [
                    'dollar' => ['label' => __('total transfer dollars'), 'icon' => 'fa-dollar-sign', 'color' => 'success'],
                    'coins' => ['label' => __('total transfer coins'), 'icon' => 'fa-coins', 'color' => 'warning']
                ];
            } elseif ($isExchange) {
                $fields = [
                    'diamonds' => ['label' => __('total diamonds'), 'icon' => 'fa-gem', 'color' => 'info'],
                    'coins' => ['label' => __('total coins'), 'icon' => 'fa-coins', 'color' => 'warning']
                ];
            } else {
                $fields = [
                    'receiver' => ['label' => __('Total recharge to recharge agencies'), 'icon' => 'fa-arrow-down', 'color' => 'primary'],
                    'sender' => ['label' => __('Total recharge from recharge agencies'), 'icon' => 'fa-arrow-up', 'color' => 'danger']
                ];
            }
        @endphp

            <!-- Stats Cards -->
        <div class="stats-cards-container">
            @foreach ($fields as $name => $field)
                @php
                    $uuid = data_get(request($name), 'uuid') ?: null;
                    $user = $getUserByUuid($uuid);
                    $value = 0;

                    if ($name === 'receiver') {
                        if($isApp) {
                            $value = \App\Models\Charge::where('user_type', 'agency')->sum('amount');
                        } else {
                            $value = $calculateReceiverValue($user, request());
                        }
                    } elseif ($name === 'sender') {
                        if($isApp) {
                            $value = \App\Models\Charge::where('charger_type', 'agency')->sum('amount');
                        } else {
                            $value = $user ? \App\Models\Charge::where('charger_id', $user->id)->sum('amount') : \App\Models\Charge::sum('amount');
                        }
                    } elseif ($name === 'dollar') {
                        if($isDashboard) {
                            $value = \App\Models\Charge::where('charger_type', 'dash')->sum('usd');
                        } elseif ($isHost) {
                            $value = \App\Models\Charge::where('charger_type', 'host_agency')->sum('usd');
                        } else {
                            $value = DB::table('coin_logs')->join('coins', 'coin_logs.coin_id', '=', 'coins.id')->sum('coins.usd');
                        }
                    } elseif ($name === 'coins') {
                        if($isDashboard) {
                            $value = \App\Models\Charge::where('charger_type', 'dash')->sum('amount');
                        } elseif ($isExchange) {
                            $value = \App\Models\ExchangeLog::sum('value');
                        } elseif ($isHost) {
                            $value = \App\Models\Charge::where('charger_type', 'host_agency')->sum('amount');
                        } else {
                            $value = \App\Models\CoinLog::where('status', 1)->sum('obtained_coins');
                        }
                    } elseif ($name === 'diamonds') {
                        $value = \App\Models\ExchangeLog::sum('diamonds');
                    }
                @endphp

                <div class="stat-card stat-card-{{ $field['color'] }}">
                    <div class="stat-card-icon">
                        <i class="fa {{ $field['icon'] }}"></i>
                    </div>
                    <div class="stat-card-content">
                        <span class="stat-card-label">{{ $field['label'] }}</span>
                        <span class="stat-card-value">{{ number_format($value, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .nav-pills>li.active>a, .nav-pills>li.active>a:focus, .nav-pills>li.active>a:hover {
        background-color: var(--primary-color);
    }

    .nav-pills>li.active>a, .nav-pills>li.active>a:hover, .nav-pills>li.active>a:focus {
        border-top-color: var(--primary-color);
    }

    .nav-scroll-container {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .nav-pills {
        display: inline-flex;
        padding: 10px 0;
    }

    .nav-pills li {
        display: inline-block;
    }

    .details-title {
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 1.2rem;
        color: var(--text-secondary-color);
    }

    /* Stats Cards Styles */
    .stats-cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        padding: 15px 0;
    }

    .stat-card {
        flex: 1;
        min-width: 250px;
        max-width: 350px;
        background: var(--gradient-primary);
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transition: transform 0.5s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .stat-card:hover::before {
        transform: scale(2);
    }

    /* Card Colors */
    .stat-card-primary {
        background: var(--gradient-primary);
    }

    .stat-card-success {
        background: var(--gradient-primary);
    }

    .stat-card-warning {
        background: var(--gradient-primary);
    }

    .stat-card-danger {
        background: var(--gradient-primary);
    }

    .stat-card-info {
        background: var(--gradient-primary);
    }

    .stat-card-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-card-icon i {
        font-size: 24px;
        color: var(--text-secondary-color);
    }

    .stat-card-content {
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 1;
    }

    .stat-card-label {
        font-size: 0.9rem;
        color: var(--text-secondary-color);
        font-weight: 500;
    }

    .stat-card-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-secondary-color);
        letter-spacing: -0.5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-cards-container {
            flex-direction: column;
        }

        .stat-card {
            max-width: 100%;
        }
    }

    /* Dark Mode Support */
    .dark-mode .details-title {
        color: var(--text-secondary-color);
    }
</style>
