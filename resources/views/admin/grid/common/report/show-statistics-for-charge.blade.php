{{--<div class="col">--}}
{{--    @php--}}
{{--        // Determine the current request name--}}
{{--        $isDashboard = request()->name == 'dash' || request()->name == null;--}}
{{--        $isApp = request()->name == 'app';--}}
{{--        $isStripe = request()->name == 'stripe';--}}
{{--        $isStripeNew = request()->name == 'stripenew';--}}
{{--        $isInApp = request()->name == 'in-app-purchas';--}}

{{--        // Anonymous function to get user based on UUID--}}
{{--        $getUserByUuid = function ($uuid) {--}}
{{--            return \App\Models\User::where('uuid', $uuid)->first();--}}
{{--        };--}}

{{--        // Function to calculate receiver value based on conditions--}}
{{--        $calculateReceiverValue = function ($user, $request) use ($isDashboard, $isApp, $isStripe, $isStripeNew, $isInApp) {--}}
{{--            if ($isDashboard || $isApp) {--}}
{{--                return \App\Models\Charge::where('user_id', $user?->id)->sum('amount');--}}
{{--            } elseif ($isStripe) {--}}
{{--                return \App\Models\CoinLog::where('user_id', $user?->id)--}}
{{--                    ->whereNotIn('method', ['huawei_pay', 'google_pay', 'strip', 'apple_pay'])--}}
{{--                    ->sum('obtained_coins');--}}
{{--            } elseif ($isStripeNew) {--}}
{{--                return \App\Models\CoinLog::where('user_id', $user?->id)--}}
{{--                    ->where('method', 'strip')--}}
{{--                    ->sum('obtained_coins');--}}
{{--            } elseif ($isInApp) {--}}
{{--                    return $request->name_for_url_shortcut--}}
{{--                        ? \App\Models\CoinLog::where('user_id', $user?->id)--}}
{{--                            ->where('method', $request->name_for_url_shortcut)--}}
{{--                            ->sum('obtained_coins')--}}
{{--                        : \App\Models\CoinLog::where('user_id', $user?->id)--}}
{{--                            ->whereIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])--}}
{{--                            ->sum('obtained_coins');--}}
{{--                }--}}

{{--            return 0; // Default return value--}}
{{--        };--}}
{{--    @endphp--}}
{{--    <div class="row my-1 form-Roles" style="overflow-x: auto;">--}}
{{--        <div class="d-flex">--}}
{{--            @foreach (['receiver' => __('admin.receiver'), 'sender' => __('admin.sender'), 'gameCoins' => __('admin.gameCoins'), 'luckyGiftCoin' => __('admin.luckyGiftCoin')] as $name => $label)--}}
{{--                <div class="col-md-3 flex-shrink-0">--}}
{{--                    <label class="form-label">{{ $label }}</label>--}}
{{--                    @php--}}
{{--                        $uuid = request($name)['uuid'] ?? '0';--}}
{{--                        $user = $getUserByUuid($uuid);--}}
{{--                        $value = 0;--}}

{{--                        if ($name === 'receiver') {--}}
{{--                            $value = $calculateReceiverValue($user, request());--}}
{{--                        } elseif ($name === 'sender') {--}}
{{--                            $value = \App\Models\Charge::where('charger_id', $user?->id)->sum('amount');--}}
{{--                        } elseif ($name === 'gameCoins') {--}}
{{--                            $coinResult = \App\Models\CoinGameUser::select(--}}
{{--                                \DB::raw("SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as sum_type_1"),--}}
{{--                                \DB::raw("SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as sum_type_0")--}}
{{--                            )->where('user_id', $user?->id)->first();--}}
{{--                            $value = ($coinResult->sum_type_1 ?? 0) - ($coinResult->sum_type_0 ?? 0);--}}
{{--                        } elseif ($name === 'luckyGiftCoin') {--}}
{{--                            $giftResult = \App\Models\UserLuckyGift::select(--}}
{{--                                \DB::raw("SUM(CASE WHEN type = 1 THEN value ELSE 0 END) as sum_type_1"),--}}
{{--                                \DB::raw("SUM(CASE WHEN type = 0 THEN value ELSE 0 END) as sum_type_0")--}}
{{--                            )->where('user_id', $user?->id)->first();--}}
{{--                            $value = ($giftResult->sum_type_1 ?? 0) - ($giftResult->sum_type_0 ?? 0);--}}
{{--                        }--}}

{{--                    @endphp--}}
{{--                    <input type="text" class="form-control" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" readonly>--}}


{{--                </div>--}}
{{--            @endforeach--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
