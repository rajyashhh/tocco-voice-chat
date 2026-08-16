<?php
namespace App\Admin\Controllers;
use App\Models\UserLuckyGift;
use Encore\Admin\Grid;

class AppEarnedReportController extends MainController {
    protected function grid()
    {
        $grid = new Grid(new UserLuckyGift());

        if (!request()->filled('from_date') && !request()->filled('to_date')) {
            $grid->model()->whereDate('user_lucky_gifts.created_at', '>=', now()->subDays(7)->toDateString());
        }

        $grid->model()->selectRaw(
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
        )->orderByDesc('earliest_created_at');

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $query->where('users.uuid', $this->input);
                }, __('Uid'), 'Uid');
            });
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('user_lucky_gifts.created_at', '>=', $datt);
                }, __('from_date'), 'from_date')->date();
            });
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $datt=\App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);

                    $query->whereDate('user_lucky_gifts.created_at', '<=',$datt);

                }, __('to_date'), 'to_date')->date();
            });
        });

        $grid->column('user.uuid', __('user Id'));
        $grid->column('user_name', __('user name'));
        $grid->column('gift.img', __('gift image'))->image();
        $grid->column('gift.name', __('gift name'));
        $grid->column('total_number', __('number'));
        $grid->column(__('cost'))->display(function () {
            return $this->total_number * $this->gift_price;
        });
        $grid->column(__('win'))->display(function () {
            return $this->total_number_win * $this->gift_price;
        });
        $grid->column('earliest_created_at', __('created at'));

        return $grid;
    }



}
