<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Models\RemainingDiamond;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class RemainingDiamondHistoryController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'remaining-diamonds-history';
    protected $title = 'Remaining Diamonds History';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans($this->title))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RemainingDiamond());

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($subQuery) {
                        $subQuery->where('uuid', 'like', "%{$this->input}%");
                    });
                }, __('UUID'))->placeholder(__('search for host by UUID'));
            });
            $filter->column('1/2', function ($filter) {
                $filter->equal('type', __('type'))->select(['diamonds' => __('diamonds'), 'coins' => __('coins')]);
            });
            $filter->column('1/2', function ($filter) {
                $filter->equal('month', __('Month'))->select([
                    1 => __('January'),
                    2 => __('February'),
                    3 => __('March'),
                    4 => __('April'),
                    5 => __('May'),
                    6 => __('June'),
                    7 => __('July'),
                    8 => __('August'),
                    9 => __('September'),
                    10 => __('October'),
                    11 => __('November'),
                    12 => __('December'),
                ]);
            });

            $filter->column('1/2', function ($filter) {
                $currentYear = now()->year;
                $years = [];
                for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                    $years[$i] = $i;
                }
                $filter->equal('year', __('Year'))->select($years);
            });
        });

        $grid->model()->with([
            'user',
            'user.profile',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
        ]);

        $grid->column('id', __('Id'));

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('remaining', __('remaining diamonds'))->display(function ($usd) {

            $image = asset('images/diamond.jpg'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$usd}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('type', __('convert to'));
        $grid->column('amount', __('Amount'))->display(function ($usd) {

            $image = $this->type == "diamonds" ? asset('images/diamond.jpg') : asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$usd}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });


        $grid->column('remaining_at', __('remaining in month'))->display(function ($usd) {


            return $this->month . '/' . $this->year;
        });
        $grid->column('created_at', __('taken at'))->display(function ($createdAt) {
            return \Carbon\Carbon::parse($createdAt)->format('m/Y');
        });
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        return $grid;
    }
}
