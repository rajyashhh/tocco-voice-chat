<?php

namespace App\Admin\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use App\Admin\Actions\ResetUserSalary;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Services\UserService;

class ResetUserSalaryController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'reset-salary';
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'reset salary';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('reset salary'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->disableRowSelector();

        $grid->model()
            ->select(
                'users.id',
                'users.name',
                'users.uuid',
                'users.online',
                'users.sender_level',
                 'users.received_level',
                 'users.special_id',
                 "users.country_id",
                DB::raw('SUM(COALESCE(us.sallary, 0) - COALESCE(us.cut_amount, 0) ) as salary')
            )
            ->join('user_sallaries as us', 'us.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.uuid', 'users.online','users.sender_level',
                 'users.received_level',
                 'users.special_id',
                 "users.country_id",)
            ->havingRaw('salary < 0');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->where(function ($q) use ($input) {
                        $q->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('User'))->placeholder(__('Search by name , UUID '));
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('salary', trans('salary'));

        $grid->column('target', __('charge history'))->display(function ($model) {
            $url = url('admin/user-charge-history/' . $this->id);
            $button1 = "<a href='{$url}' class='btn btn-sm btn-info'>" . __('history') . "</a>";
            return $button1;
        });
        $grid->actions(function ($actions) {
            $actions->add(new ResetUserSalary());
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->disableCreateButton();

        return $grid;
    }
}
