<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\Charge;
use App\Models\CoinGameUser;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\LuckyBox\Entities\UserLuckyGift;

class CoinReportController extends MainController
{
    public $permission_name = 'user-coin-report';
    public function index(Content $content)
    {

        return $content
            ->title(trans('Coin Reports'))
            ->row(function ($row) {
                $row->column(12, $this->gridTabs()); // <-- Tab buttons
            })
            ->row(function ($row) {
                $row->column(12, view('admin.grid.common.report.coins')); // <-- Tab buttons
            })
            ->row(function ($row) {

                $row->column(12, $this->grid());
            });
    }


    protected function gridTabs()
    {
        $scope = request('name', 'lucky_gift');

        $html = '
            <style>
                .tab-buttons {
                    margin-bottom: 15px;
                }
                .tab-buttons .tab-button {
                    color: black !important;
                    margin-right: 10px;
                    text-decoration: none;
                    padding: 6px 12px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    background-color: #f7f7f7;
                }
                .tab-buttons .tab-button.active {
                    background-color: #007bff;
                    color: white !important;
                    border-color: #007bff;
                }
            </style>
            <div class="tab-buttons">
                <a href="?name=lucky_gift" class="tab-button btn-dash ' . ($scope === 'lucky_gift' ? 'active' : '') . '">' . __('Lucky Gifts') . '</a>
                <a href="?name=games" class="tab-button btn-agency ' . ($scope === 'games' ? 'active' : '') . '">' . __('Games') . '</a>
            </div>';

        return new \Encore\Admin\Widgets\Box(__(), $html);
    }


    protected function grid()
    {
        $default = "lucky_gift";
        $name = request("name") ?? $default;

        if (!method_exists($this, $name)) {
            $name = $default;
        }

        $grid = $this->{$name}();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableColumnSelector();

        return $grid;
    }


    protected function lucky_gift()
    {
        $grid = new Grid(new UserLuckyGift());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $hasDateFilter = filled(request('from_date')) || filled(request('to_date'));

        $grid->model()->with([
            'gift',
            'user',
            'user.profile',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ])
            ->when(!$hasDateFilter, fn($q) => $q->whereDate('user_lucky_gifts.created_at', '>=', now()->subDays(7)->toDateString()))
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->selectRaw(
                'MIN(user_lucky_gifts.created_at) as earliest_created_at, ' .
                    'SUM(user_lucky_gifts.number) as total_number, ' .
                    'user_lucky_gifts.gift_id, ' .
                    'user_lucky_gifts.user_id, ' .
                    'MAX(users.name) as user_name, ' .
                    'MAX(gifts.img) as gift_img, ' .
                    'MAX(gifts.name) as gift_name, ' .
                    'user_lucky_gifts.gift_price, ' .
                    'SUM(CASE WHEN user_lucky_gifts.type = 1 THEN user_lucky_gifts.number ELSE 0 END) as total_number_win, ' .
                    'SUM(total_win) as total_win_value, '  .
                    ' SUM(
                    CASE 
                        WHEN user_lucky_gifts.total_win = 0
                        THEN user_lucky_gifts.gift_price * user_lucky_gifts.number
                        ELSE 0
                    END
                ) AS total_lose_value'
            )
            ->leftJoin('users', 'user_lucky_gifts.user_id', '=', 'users.id')
            ->leftJoin('gifts', 'user_lucky_gifts.gift_id', '=', 'gifts.id')
            ->groupBy(
                'user_lucky_gifts.gift_id',
                'user_lucky_gifts.user_id',
                'user_lucky_gifts.gift_price',
            )->orderByDesc('earliest_created_at');

        $grid->filter(function ($filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('user_lucky_gifts.created_at', '>=', $datt);
                }, __('from_date'), 'from_date')->date();
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);

                    $query->whereDate('user_lucky_gifts.created_at', '<=', $datt);
                }, __('to_date'), 'to_date')->date();
            });
            $filter->column(1 / 2, function ($filter) {

                $filter->equal('user_id', __('user'))->select()->ajax('/api/search/users2', 'id', 'name');
            });
        });

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('gift_id', __('gifts'))->display(function ($name) {
            if (!$this->gift) {
                return __('No Gift');
            }
            $name = app()->getLocale() === 'ar' ? (@$this->gift->name ?? @$this->gift->e_name) : (@$this->gift->e_name ?? @$this->gift->name);
            $path = @$this->gift->img ?? '';
            $defaultImage = asset("images/reward.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id ?? uniqid(), $url, 40, 40);

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <a href='' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <span>$name</span>
                </div>
            ";
        });

        $grid->column('total_number', __('number'));

        $grid->column(__('win'))->display(function () {
            return $this->total_win_value;  // sum of positive 'value'
        });

        $grid->column(__('lose'))->display(function () {
            return $this->total_lose_value; // sum of negative 'value' as positive
        });

        $grid->column('earliest_created_at', __('created at'));

        return $grid;
    }

    protected function games()
    {
        $grid = new Grid(new CoinGameUser());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $hasDateFilter = filled(request('from_date')) || filled(request('to_date'));

        $grid->model()
            ->when(!$hasDateFilter, fn($q) => $q->whereDate('coin_game_users.created_at', '>=', now()->subDays(7)->toDateString()))
            ->when(
                $countryID,
                fn($q) =>
                $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID))
            )
            ->with([
                'game',
                'user',
                'user.profile',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) =>
                $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])
            ->selectRaw('
        MIN(coin_game_users.created_at) as earliest_created_at,
        coin_game_users.user_id,
        MAX(users.name) as user_name,
        MAX(all_games.name) as game_name,
        SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win,
        SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose
    ')
            ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
            ->leftJoin('all_games', 'coin_game_users.game_id', '=', 'all_games.id')
            ->groupBy('coin_game_users.user_id', 'all_games.id')
            ->orderByDesc('earliest_created_at');


        $grid->filter(function ($filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('coin_game_users.created_at', '>=', $datt);
                }, __('from_date'), 'from_date')->date();
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);

                    $query->whereDate('coin_game_users.created_at', '<=', $datt);
                }, __('to_date'), 'to_date')->date();
            });
            $filter->column(1 / 2, function ($filter) {

                $filter->equal('user_id', __('user'))->select()->ajax('/api/search/users2', 'id', 'name');
            });
        });


        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('game_name', __('game name'));
        $grid->column('total_coins_lose', __('loser'));
        $grid->column('total_coins_win', __('win'));
        $grid->column('earliest_created_at', __('created at'));

        return $grid;
    }
}
