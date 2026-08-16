<?php

namespace App\Admin\Controllers;

use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Models\GameChargeHistory;
use App\Admin\Controllers\MainController;

/**
 * Read-only ledger of game-wallet top-ups. Manual charging from the panel is
 * gone (games balance lives at the UTD Games provider); rows are still written
 * automatically by paid game_type orders (PaymentMethodController::callBack)
 * and the historic manual entries remain visible for accounting.
 */
class GameChargeHistoryController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Game Charge History';

    public $permission_name = 'game-settings';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Game Charge History'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans(__($this->title)))
            ->body($this->detail($id)));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GameChargeHistory());

        $grid->model()->orderByDesc('id');
        $grid->column('id', __('Id'));
        $grid->column('value', __('Value'));
        $grid->column('admin_id', __('Admin id'))->display(function ($value) {
            return $value ? $value : __('Auto (payment)');
        });
        $grid->column('created_at', trans('admin.created_at'));

        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->between('created_at', trans('admin.created_at'))->datetime();
        });

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(GameChargeHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('value', __('Value'));
        $show->field('admin_id', __('Admin id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });

        return $show;
    }
}
