<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\UsersChargeAction;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Jobs\SendChargeNotificationJob;
use App\Models\Charge;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserSallary;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;

class UsersChargeController extends MainController
{
    use HasResourceActions;

    const reason = 'return-coins-9-2025';
    public $permission_name = 'charge-to-user';


    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        if (! \Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }
        return $content
            ->title(trans('charges'))
            ->body($this->grid());
    }




    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    protected function grid()
    {
        $grid = new Grid(new User());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $grid->filter(function (Grid\Filter $filter) {

            $filter->expand();

            $filter->disableIdFilter();
            $filter->equal('ID', __('ID'));

            $filter->where(function ($query) {
                $query->where('name', 'like', "{$this->input}%");
            }, __('name'));

            $filter->where(function ($query) {

                $query->where('uuid', $this->input);
            }, __('uuid'));
        });

        $grid->model()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->select('id', 'name', 'uuid', 'coins', 'di', 'country_id', 'sender_level', 'received_level')
            ->with([
                'profile',
                'country',
                'senderLevel',
                'receiverLevel',
                'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            ])
            ->orderByDesc('id');

        $grid->id(__('ID'));

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('di', __('coins'))->display(function ($coin) {
            $icon = asset('images/coin.jpg');
            $coin = (float) $coin;
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        
        if (\Encore\Admin\Facades\Admin::user()->can('add-switch-' . $this->permission_name) || \Encore\Admin\Facades\Admin::user()->can('*') || \Encore\Admin\Facades\Admin::user()->can('history-switch-' . $this->permission_name)) {
            $grid->column('actions', __('Actions'))
                ->display(function () {

                    return (new UsersChargeAction())->setUserId($this->id)->render();
                })->style('white-space: nowrap; width: 100px;');
        }

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();

        return $grid;
    }


    public function chargeUser()
    {
        $month = 9;
        $year = 2025;
        $reason = self::reason;

        $chunkSize = 500; // adjust based on memory/performance

        $processedCount = 0;

        $userCoinsRate = \Cache::rememberForever('user_coins', function () {
            $setting = Setting::where('key', 'user_coins')->first();
            return $setting?->value ?? 1;
        });

        UserSallary::with('user:id,di,notification_id,is_logout')
            ->where('month', $month)
            ->where('year', $year)
            ->where('remaining_diamond', '!=', 0)
            ->select(['id', 'user_id', 'remaining_diamond'])
            ->chunk($chunkSize, function ($userSalaries) use (&$processedCount, $reason, $userCoinsRate) {

                // Idempotency guard: skip users who already have the charge row.
                $chargedUserIds = Charge::whereIn('user_id', $userSalaries->pluck('user_id'))
                    ->where('reason_en', $reason)
                    ->pluck('user_id')
                    ->toArray();

                $charges = [];
                $notifications = [];

                DB::transaction(function () use ($userSalaries, $chargedUserIds, $reason, $userCoinsRate, &$charges, &$notifications) {
                    foreach ($userSalaries as $userSalary) {
                        $user = $userSalary->user;

                        if (!$user || in_array($user->id, $chargedUserIds)) {
                            continue;
                        }

                        $coin = $userSalary->remaining_diamond * 0.5;
                        $amountBefore = $user->di;

                        // Log coins
                        UserCoinLogHelper::logByType(
                            $user->id,
                            $coin,
                            $amountBefore,
                            UserCoinLogType::ADMIN_CHARGES,
                        );

                        // Update user balance
                        $user->increment('di', $coin);

                        $usdAmount = $userCoinsRate > 0 ? $coin / $userCoinsRate : 0;

                        $charges[] = [
                            'charger_id'      => 1,
                            'charger_type'    => 'dash',
                            'user_id'         => $user->id,
                            'agency_id'       => null,
                            'user_type'       => 'user',
                            'amount'          => $coin,
                            'usd'             => $usdAmount,
                            'balance_before'  => $amountBefore,
                            'reason_en'       => $reason,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];

                        $notifications[] = ['user' => $user, 'coin' => $coin];
                    }

                    // Bulk insert charges so balance and ledger commit together
                    if (!empty($charges)) {
                        Charge::insert($charges);
                    }
                });

                // Dispatch notifications only after the chunk committed
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $coin = $notification['coin'];
                        SendChargeNotificationJob::dispatch(
                            $notification['user'],
                            'Coins Added',
                            "You have received {$coin} coins from admin.",
                            ['coins' => $coin]
                        )->onQueue('notifications');
                    }

                    $processedCount += count($charges);
                }
            });

        return response()->json([
            'message' => 'User charge process completed successfully.',
            'count'   => $processedCount,
        ]);
    }

}
