{{-- <div class="box box-solid">

    <div class="box-header with-border">
        <h3 class="box-title">الحقول</h3>

        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
        <ul class="nav nav-pills nav-stacked">
            <li class="{{ request()->name == 'lucky_gift' || request()->name == null ? 'active' : '' }}"><a href="?name=lucky_gift"
                    class="charge_action"><i class="fa fa-arrow-right text-red"></i>{{ __('lucky_gift_title') }}</a>
            </li>
            <li class="{{ request()->name == 'games' ? 'active' : '' }}"><a href="?name=games" class="charge_action"><i
                        class="fa fa-arrow-right text-red"></i>{{ __('Games') }}</a>
            </li>
        </ul>
    </div>

</div> --}}

{{-- <div class="col">

    @if(request()->name == 'lucky_gift' || request()->name == null)
        <div class="col my-1 form-Roles">
            @php
                $subQuery = \Modules\LuckyBox\Entities\UserLuckyGift::query()
                                 ->selectRaw(
                                        'MIN(user_lucky_gifts.created_at) as earliest_created_at, ' .
                                        'SUM(user_lucky_gifts.number) as total_number, ' .
                                        'user_lucky_gifts.gift_id, ' .
                                        'user_lucky_gifts.user_id, ' .
                                        'MAX(users.name) as user_name, ' . // Aggregated using MAX
                                        'MAX(gifts.img) as gift_img, ' . // Aggregated using MAX
                                        'MAX(gifts.name) as gift_name, ' . // Aggregated using MAX
                                        'user_lucky_gifts.gift_price, ' .
                                        'SUM(CASE WHEN user_lucky_gifts.type = 1 THEN user_lucky_gifts.number ELSE 0 END) as total_number_win'
                                    )
                                    ->leftJoin('users', 'user_lucky_gifts.user_id', '=', 'users.id')
                                    ->leftJoin('gifts', 'user_lucky_gifts.gift_id', '=', 'gifts.id')
                                    ->groupBy(
                                        'user_lucky_gifts.gift_id',
                                        'user_lucky_gifts.user_id',
                                        'user_lucky_gifts.gift_price',
                                    );


                if (request('from_date') != null && request('to_date') != null){
                    $fromDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('from_date'));
                    $toDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('to_date'));
                    $subQuery=$subQuery->whereDate('user_lucky_gifts.created_at', '>=', $fromDate)->whereDate('user_lucky_gifts.created_at', '<=',$toDate);
                }
                if(request('Uid')){
                    $subQuery=$subQuery->where('users.uuid', request('Uid'));
                }
                $totalNumberWin = \DB::query()->fromSub($subQuery, 'subquery')
                                 ->selectRaw('SUM(total_number_win * gift_price) as grand_total_number_win,SUM(total_number * gift_price) as grand_total_number_cost')
                                 ->first();
            @endphp
            <label class="form-label">  {{ __('total cost') }}</label>
            <input type="text" class="form-control" name="receiver" id="receiver" value="{{@$totalNumberWin->grand_total_number_cost}}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles" >
            <label class="form-label">{{ __('total win') }}</label>
            <input type="text" class="form-control " id="sender" name="sender"  value="{{@$totalNumberWin->grand_total_number_win}}" >
        </div>
        <br>
        <div class=" col  my-1 form-Roles" >
            <label class="form-label">{{ __('difference result') }}</label>
            <input type="text" class="form-control " id="sender" name="sender"  value="{{ @$totalNumberWin->grand_total_number_win - @$totalNumberWin->grand_total_number_cost }}" >
        </div>
    @elseif(request()->name == 'games')
            <div class="col my-1 form-Roles">
                @php
                    $subQuery = \App\Models\CoinGameUser::query()
                                        ->selectRaw('MIN(coin_game_users.created_at) as earliest_created_at, coin_game_users.game_id, coin_game_users.user_id, MAX(users.name) as user_name, MAX(games.name) as game_name, SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win, SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose')
                                        ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
                                        ->leftJoin('games', 'coin_game_users.game_id', '=', 'games.id')
                                        ->groupBy('coin_game_users.game_id', 'coin_game_users.user_id');


                    if (request('from_date') != null && request('to_date') != null){
                        $fromDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('from_date'));
                        $toDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('to_date'));
                        $subQuery=$subQuery->whereDate('coin_game_users.created_at', '>=', $fromDate)->whereDate('coin_game_users.created_at', '<=',$toDate);
                    }
                    if(request('Uid')){
                        $subQuery=$subQuery->where('users.uuid', request('Uid'));
                    }
                    $totalNumberWin = \DB::query()->fromSub($subQuery, 'subquery')
                                     ->selectRaw('SUM(total_coins_lose ) as total_lose,SUM(total_coins_win) as total_win')
                                     ->first();
                @endphp
                <label class="form-label">{{ __('total lose') }}</label>
                <input type="text" class="form-control" name="receiver" id="receiver" value="{{@$totalNumberWin->total_lose}}">
            </div>
            <br>
            <div class=" col  my-1 form-Roles" >
                <label class="form-label">{{ __('total win') }}</label>
                <input type="text" class="form-control " id="sender" name="sender"  value="{{@$totalNumberWin->total_win}}" >
            </div>
            <br>
            <div class=" col  my-1 form-Roles" >
                <label class="form-label">{{ __('difference result') }}</label>
                <input type="text" class="form-control " id="sender" name="sender"  value="{{ @$totalNumberWin->total_lose - @$totalNumberWin->total_win }}" >
            </div>
    @endif
</div> --}}


<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<!-- <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> -->
<style>
.floating-dots { position:absolute; inset:0; z-index:0; background: radial-gradient(circle, rgba(0,0,0,0.06) 2px, transparent 3px); background-size:40px 40px; animation: moveDots 40s linear infinite; opacity:0.6; pointer-events:none; }
@keyframes moveDots { from { background-position:0 0;} to { background-position:400px 400px; } }

.dashboard-wrap{ position:relative; z-index:1; }
.page-padding{ padding:1.25rem; }

.cards-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:20px; margin-bottom:40px; }
.ltr .card { position: relative;  background:#fff; border-radius:12px; padding:10px 12px; text-align:start; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; overflow:hidden; }
.rtl .card { position: relative; background:#fff; border-radius:12px; padding:10px 12px; text-align:start; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; overflow:hidden; }
.card:hover { transform: translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }
.card h2 { font-size:1.3rem; color:#333; }
.card h3 { font-size:1.3rem; margin-bottom:10px; color:#333; margin-top: 0 !important;}
.card .amount { font-size:1.5rem; font-weight:bold; margin-bottom:15px; color:#1e3a8a; transition: all 0.6s ease; }
.card canvas { width:100% !important; height:120px !important; }
.rtl .card .card-icon{   left: 14px; position: absolute; top:7px}
.ltr .card .card-icon{   right: 14px; position: absolute; top:7px}
.main-chart-container { background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.1);     overflow: hidden; }
.main-chart-container canvas {
    height: 200px !important;
    width: 100% !important;
}
.main-chart-container {
    position: relative;
    width: 100%;
    /* height: 260px;  */
}
.main-chart-container{
    background: none;
    border-radius:12px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
     height: 102%;
}

#shipmentsChart{
    width:100% !important;
    height: 100% !important;
}
.card-trans{min-height: 370px;}
.card-trans2{min-height: 468px;  max-height:468px ;}

.card-with{margin-top:20px;}
.carPay {min-height :456px;}
#applyFilter{
    margin-top: 19px  !important;
}
.tab-content {
    min-height: 2000px  !important;
}
.CardwalletLogsTable{
    overflow: scroll !important;
}
@media (max-width: 992px) {
    .main-chart-container {
        /* min-height: 300px; */
    }
}

@media (max-width: 600px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 10px;
    }
    .chart-container {
        position: relative;
        height: 320px !important;
    }
    .chart-title {
        font-size: 1rem;
    }
    #applyFilter{
            margin-top: 12px  !important;
    }
    .carPay {min-height :auto !important;}
}
#topUsersContainer .user-card {
    text-align: center;
    flex: 0 0 calc(50% - 0.5rem);
}
#topUsersContainer .user-card:nth-child(n+3) {
    flex: 0 0 calc(33.33% - 0.5rem);
}
.user-card img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 0.25rem;
    border: 2px solid #1d4ed8;
}
.user-card .user-name {
    font-size: 0.875rem;
    font-weight: 500;
}
#filterCard {
    display: none;
}
#toggleFilter{
    margin-bottom: 10px;
}
@media (max-width:600px){ .cards-container{ grid-template-columns:1fr; } }
</style>
<div class="cards-container mb-4" data-aos="fade-up">

    @php
        // Prepare data depending on the request type
        if(request()->name == 'lucky_gift' || request()->name == null) {

            $subQuery = \Modules\LuckyBox\Entities\UserLuckyGift::query()
                ->selectRaw(
                    'MIN(user_lucky_gifts.created_at) as earliest_created_at, ' .
                    'SUM(user_lucky_gifts.number) as total_number, ' .
                    'user_lucky_gifts.gift_id, ' .
                    'user_lucky_gifts.user_id, ' .
                    'MAX(users.name) as user_name, ' .
                    'MAX(gifts.img) as gift_img, ' .
                    'MAX(gifts.name) as gift_name, ' .
                    'user_lucky_gifts.gift_price, ' .
                    'SUM(CASE WHEN user_lucky_gifts.type = 1 THEN user_lucky_gifts.number ELSE 0 END) as total_number_win'
                )
                ->leftJoin('users', 'user_lucky_gifts.user_id', '=', 'users.id')
                ->leftJoin('gifts', 'user_lucky_gifts.gift_id', '=', 'gifts.id')
                ->groupBy('user_lucky_gifts.gift_id','user_lucky_gifts.user_id','user_lucky_gifts.gift_price');

            if(request('from_date') && request('to_date')){
                $fromDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('from_date'));
                $toDate   = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('to_date'));
                $subQuery = $subQuery->whereDate('user_lucky_gifts.created_at', '>=', $fromDate)
                                     ->whereDate('user_lucky_gifts.created_at', '<=', $toDate);
            }

            if(request('Uid')){
                $subQuery = $subQuery->where('users.uuid', request('Uid'));
            }

            $totalNumberWin = \DB::query()->fromSub($subQuery, 'subquery')
                ->selectRaw('
                    SUM(total_number_win * gift_price) as total_win,
                    SUM(total_number * gift_price) as total_cost
                ')
                ->first();

            $cards = [
                'total_cost' => $totalNumberWin->total_cost ?? 0,
                'total_win'  => $totalNumberWin->total_win ?? 0,
                'difference' => ($totalNumberWin->total_win ?? 0) - ($totalNumberWin->total_cost ?? 0),
            ];

        } elseif(request()->name == 'games') {

            $subQuery = \App\Models\CoinGameUser::query()
                ->selectRaw('
                    MIN(coin_game_users.created_at) as earliest_created_at,
                    coin_game_users.game_id,
                    coin_game_users.user_id,
                    MAX(users.name) as user_name,
                    MAX(games.name) as game_name,
                    SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win,
                    SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose
                ')
                ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
                ->leftJoin('games', 'coin_game_users.game_id', '=', 'games.id')
                ->groupBy('coin_game_users.game_id','coin_game_users.user_id');

            if(request('from_date') && request('to_date')){
                $fromDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('from_date'));
                $toDate   = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('to_date'));
                $subQuery = $subQuery->whereDate('coin_game_users.created_at', '>=', $fromDate)
                                     ->whereDate('coin_game_users.created_at', '<=', $toDate);
            }

            if(request('Uid')){
                $subQuery = $subQuery->where('users.uuid', request('Uid'));
            }

            $totalNumberWin = \DB::query()->fromSub($subQuery, 'subquery')
                ->selectRaw('
                    SUM(total_coins_win) as total_win,
                    SUM(total_coins_lose) as total_lose
                ')
                ->first();

            $cards = [
                'total_lose' => $totalNumberWin->total_lose ?? 0,
                'total_win'  => $totalNumberWin->total_win ?? 0,
                'difference' => ($totalNumberWin->total_lose ?? 0) - ($totalNumberWin->total_win ?? 0),
            ];
        }
    @endphp

    @foreach($cards as $id => $amount)
        <div class="card finance-card" id="{{ $id }}">
            <div class="card-icon">
                @php
                    $icons = [
                        'total_cost' => 'fa-money-bill-wave',
                        'total_win' => 'fa-coins',
                        'difference' => 'fa-scale-balanced',
                        'total_lose' => 'fa-circle-xmark'
                    ];
                @endphp
                <i class="fa-solid {{ $icons[$id] ?? 'fa-circle-info' }}"></i>
            </div>
            <h2 style="margin-top:0 !important;">{{ __("{$id}") }}</h2>
            <p class="amount">{{ $amount }}</p>
            <h3>{{ __("{$id}") }}</h3>
        </div>
    @endforeach
</div>



{{-- @elseif(request()->name == 'shipping_host')
        <div class="col my-1 form-Roles">
            @php
                $subQuery = \App\Models\Charge::query()
                                    ->selectRaw('MIN(charges.created_at) as earliest_created_at,charges.user_id, MAX(users.name) as user_name, sum(CASE WHEN charges.user_type = "app" THEN charges.amount ELSE 0 END) as total_coins_from_user, sum(CASE WHEN charges.user_type != "app" AND charges.user_type != "dash" THEN charges.amount ELSE 0 END) as total_coins_shipping')
                                    ->leftJoin('users', 'charges.user_id', '=', 'users.id')
                                    ->groupBy( 'charges.user_id');


                if (request('from_date') != null && request('to_date') != null){
                    $fromDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('from_date'));
                    $toDate = \App\Helpers\UserCommon::arabicToEnglishNumbers(request('to_date'));
                    $subQuery=$subQuery->whereDate('charges.created_at', '>=', $fromDate)->whereDate('charges.created_at', '<=',$toDate);
                }
                if(request('Uid')){
                    $subQuery=$subQuery->where('users.uuid', request('Uid'));
                }
                $totalNumberWin = \DB::query()->fromSub($subQuery, 'subquery')
                                 ->selectRaw('SUM(total_coins_shipping ) as total_shipping,SUM(total_coins_from_user) as total_user')
                                 ->first();
            @endphp
            <label class="form-label">مجموع ال total_shipping</label>
            <input type="text" class="form-control" name="receiver" id="receiver" value="{{@$totalNumberWin->total_shipping}}">
        </div>
        <br>
        <div class=" col  my-1 form-Roles" >
            <label class="form-label">مجموع ال total_user</label>
            <input type="text" class="form-control " id="sender" name="sender"  value="{{@$totalNumberWin->total_user}}" >
        </div>
    @endif --}}