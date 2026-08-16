<?php

namespace App\Admin\Controllers;


use Encore\Admin\Grid;
use App\Models\Commission;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;

use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;


class CommissionController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'charge';
    public $hiddenColumns = [];

    public function index(Content $content)
    {
        Permission::check('browse-' . $this->permission_name);

        Admin::style('
            .box {
                background: var(--gray-800) !important;
                border: 1px solid var(--gray-600) !important;
                border-radius: var(--border-radius) !important;
                box-shadow: var(--shadow-md) !important;
            }
            .box-header {
                background: var(--secondary-color) !important;
                color: var(--white) !important;
                border-bottom: 1px solid var(--gray-600) !important;
            }
            .box-title {
                color: var(--primary-color) !important;
                font-weight: 600 !important;
            }
            .table {
                background: var(--gray-800) !important;
                color: var(--text-primary-color) !important;
            }
            .table thead th {
                background: var(--gray-700) !important;
                color: var(--white) !important;
                font-weight: 600 !important;
                border-bottom: 2px solid var(--primary-color) !important;
            }
            .table tbody td {
                color: var(--text-primary-color) !important;
                border-bottom: 1px solid var(--gray-600) !important;
            }
            .table tbody tr:hover {
                background: var(--primary-hover-alpha) !important;
            }
            .btn-primary {
                background: var(--primary-button) !important;
                border: none !important;
                border-radius: var(--border-radius) !important;
                color: var(--white) !important;
            }
            .btn-primary:hover {
                background: var(--primary-color) !important;
                box-shadow: var(--shadow-md) !important;
            }
            .pagination > .active > a {
                background: var(--primary-color) !important;
                border-color: var(--primary-color) !important;
            }
            .filter-box {
                background: var(--gray-800) !important;
                border: 1px solid var(--gray-600) !important;
                border-radius: var(--border-radius) !important;
            }
            .form-control {
                background: var(--gray-50) !important;
                border: 1px solid var(--gray-300) !important;
                color: var(--gray-800) !important;
                border-radius: var(--border-radius) !important;
            }
            .grid-per-pager select {
                background: var(--gray-50) !important;
                border: 1px solid var(--gray-300) !important;
            }
        ');

        return $content
            ->title(__('Commission History'))
            ->description(__('View commission transactions'))
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Commission());

        $grid->filter(function (Grid\Filter $filter) {

            $filter->disableIdFilter();
            $filter->where(function ($query) {
                $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '>=', $datt);
            }, __('from_date'), 'from_date')->date();

            $filter->where(function ($query) {
                $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);

                $query->whereDate('created_at', '<=', $datt);
            }, __('to_date'), 'to_date')->date();

        });

        $grid->column('amount', __('amount'))->display(function ($amount) {
            return '<span style="color: var(--primary-color); font-weight: 600;">' . number_format($amount, 2) . '</span>';
        });

        $grid->column('created_at', __('date'))->display(function () {
            return '<span style="color: var(--text-secondary-color);">' .
                \Carbon\Carbon::createFromTimestamp(strtotime($this->created_at))
                    ->timezone(auth()->user()->time_zone)->format("Y-m-d h:i A") .
                '</span>';
        });

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();

        return $grid;
    }
}