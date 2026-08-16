<?php

namespace App\Admin\Controllers;

use App\Admin\Services\AgencyService;
use App\Admin\Services\UserService;
use App\Models\SalaryTrx;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

use function request;

class SallariesHistoryController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'salary-history';

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Salary History'))
            ->body($this->grid()));
    }

    protected function grid()
    {
        $grid = new Grid(new SalaryTrx);
        $grid->model()->where('type', request('type'))->orderByDesc('id')
            ->with([
                'user.profile:id,user_id,avatar',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'agency',
            ]);
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->id(__('Id'));
        if (request('type') == 0) {

            $grid->column('name', __('user'))
                ->display(function () {

                    $user = $this->user;
                    if (! $user) {
                        return '';
                    }

                    return app(UserService::class)->adminUserCard($user);
                });
        } else {
            $grid->column('name', __('Agency'))->display(function ($name) {
                $agency = $this->agency;
                if (! $agency) {
                    return '';
                }

                return app(AgencyService::class)->adminAgencyData($agency);
            });
        }
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->amount()->display(function ($num) {
            if ($num > 0) {
                return "<span class='text-primary '>$num</span>";
            }
            $num *= -1;

            return "<span class='text-danger '>$num</span>";

        });
        $grid->disableExport();
        $grid->disableCreateButton();

        return $grid;
    }
}
