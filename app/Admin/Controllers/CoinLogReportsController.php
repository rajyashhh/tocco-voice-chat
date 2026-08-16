<?php

namespace App\Admin\Controllers;

use App\Models\CoinLog;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class CoinLogReportsController extends MainController
{
    public $permission_name = 'charger-reports';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Coin Log Reports'))
            ->body($this->grid()));
    }

    protected function grid()
    {
        $grid = new Grid(new CoinLog());

        $grid->model()->with(['user:id,name', 'shippingAgency:id,name'])->orderBy('id', 'desc');

        // Default scope to the current month unless the admin picks an explicit
        // date range, so the grid never does a full-table ordered scan.
        if (!request()->filled('from_date') && !request()->filled('to_date')) {
            $grid->model()->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
        }

        // SQL-level pagination; the month scope above plus the created_at
        // index keep the COUNT(*) bounded.
        $grid->paginate(20);

        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_type', __('type'))->select([
                'user' => __('User'),
                'shipping_agency' => __('Shipping'),
            ]);
            $filter->where(function ($query) {
                $query->whereDate('created_at', '>=', $this->input);
            }, __('From Date'), 'from_date')->date();
            $filter->where(function ($query) {
                $query->whereDate('created_at', '<=', $this->input);
            }, __('To Date'), 'to_date')->date();
        });

        $grid->column('id', 'ID')->sortable();
        $grid->column('user_type', __('Type'))->label([
            'user' => 'success',
            'shipping_agency' => 'primary',
        ]);

        $grid->column('model_id', __('owner'))->display(function ($modelId) {
            if ($this->user_type === 'user') {
                return $this->user->name ?? '-';
            } elseif ($this->user_type === 'shipping_agency') {
                return $this->shippingAgency->name ?? '-';
            }
            return '-';
        });

        $grid->column('obtained_coins', __('obtained coins'))->sortable();
        $grid->column('trx', __('Transaction Ref'));
        $grid->column('method', __('payment method'));
        $grid->column('status', __('status'))->using([
            0 => __('pending'),
            1 => __('completed'),
        ])->label([
            0 => __('warning'),
            1 => __('success'),
        ]);

        $grid->column('created_at', __('Created At'))->date('Y-m-d H:i');

        $grid->disableCreateButton();
        $grid->disableActions();

        return $grid;
    }
}
