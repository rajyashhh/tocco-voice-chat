<?php

namespace Modules\Events\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\Events\Entities\ChargeKingWinner;

class ChargeKingController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'charge-king';

    public function index(Content $content)
    {
        try {
            Permission::check('browse-' . $this->permission_name);
        } catch (\Exception $e) {
            \Log::warning('Permission denied for user ' . auth()->id() . ' on ' . $this->permission_name);
            abort(403, 'Unauthorized access');
        }

        $url = url('/admin/charge-king-rewards');
        $rewardsLabel = __('rewards');

        $buttonHTML = <<<HTML
    <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
        <i class="fa fa-gift"></i> {$rewardsLabel}
    </a>
    HTML;

        return $content
            ->header(__('Charge King'))
            ->description(trans('admin.description'))
            ->row($buttonHTML)
            ->row($this->grid());
    }

    public function store()
    {
        abort(404);
    }

    public function update($id)
    {
        abort(404);
    }

    public function destroy($id)
    {
        abort(404);
    }

    protected function grid()
    {
        $grid = new Grid(new ChargeKingWinner());
        $grid->model()->with('user')->orderByDesc('month')->orderBy('rank');
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableExport();

        $grid->column('id', __('Id'));
        $grid->column('month', __('Month'));
        $grid->column('rank', __('Rank'));
        $grid->column('user_id', __('User'))->display(function () {
            return $this->user ? "{$this->user->name} ({$this->user->uuid})" : $this->user_id;
        });
        $grid->column('prize', __('coins'));
        $grid->column('created_at', __('Created at'));

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('month', __('Month'));
            $filter->equal('user_id', __('User id'));
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        return $grid;
    }
}