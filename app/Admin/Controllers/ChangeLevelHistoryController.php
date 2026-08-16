<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Models\ChangeLevelHistory;
use App\Admin\Controllers\MainController;

class ChangeLevelHistoryController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'ChangeLevelHistory';
    public $permission_name = 'level-user-history';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans(__('Level user history')))
            ->body($this->grid()));
    }
    protected function grid()
    {
        $grid = new Grid(new ChangeLevelHistory());

        $grid->model()->with(['user.profile', 'admin'])->orderByDesc('id');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('uuid'));
            });
        });

        $grid->column('id', __('Id'));
        $grid->column('user.name', __('User'))
            ->display(function ($name) {
                if (!$this->user) {
                    return '-';
                }
                $uid = @$this->user->uuid;
                $path = @$this->user?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;
                @$this->user->id ? $editUrl = url("admin/users/{$this->user->id}") : $editUrl = ''; // Using named route

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                <a href='{$editUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong style='text-decoration: underline; cursor: pointer;'>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </a>
            </div>
            ";
            });

        $grid->column('admin.name', __('creator'))
            ->display(function ($name) {
                if (!$this->admin) {
                    return '-';
                }
                $path = @$this->admin->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);
                $this->admin->id ? $showUrl = url("admin/auth/users/{$this->admin->id}") : $showUrl = "";
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        $image
                        <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                    </a>
                </div>
            ";
            });
        $arrowIcon = asset('images/arrows.png'); // Path to the arrows.png image
        $arrowdownIcon = asset('images/arrowdown.png'); // Path to the arrows.png image

        $grid->column('old_total_sender_level', __('Old Total Sender Level'))
            ->display(function ($value) use ($arrowdownIcon) {
                return "<div style='display: flex; align-items: center; gap: 5px;'>
                                <span>$value</span>
                                <img src='$arrowdownIcon' style='width: 16px; height: 16px;'>
                            </div>";
            });

        $grid->column('new_total_sender_level', __('New Total Sender Level'))
            ->display(function ($value) use ($arrowIcon) {
                return "<div style='display: flex; align-items: center; gap: 5px;'>
                                <span>$value</span>
                                <img src='$arrowIcon' style='width: 16px; height: 16px;'>
                            </div>";
            });

        $grid->column('old_total_received_level', __('Old Total Received Level'))
            ->display(function ($value) use ($arrowdownIcon) {
                return "<div style='display: flex; align-items: center; gap: 5px;'>
                                <span>$value</span>
                                <img src='$arrowdownIcon' style='width: 16px; height: 16px;'>
                            </div>";
            });

        $grid->column('new_total_received_level', __('New Total Received Level'))
            ->display(function ($value) use ($arrowIcon) {
                return "<div style='display: flex; align-items: center; gap: 5px;'>
                                <span>$value</span>
                                <img src='$arrowIcon' style='width: 16px; height: 16px;'>
                            </div>";
            });

        $grid->column('created_at', __('Created at'))->display(function ($date) {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        });



        $grid->disableActions();
        $grid->disableCreateButton();
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
        $show = new Show(ChangeLevelHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('admin_id', __('Admin id'));
        $show->field('old_total_sender_level', __('Old total sender level'));
        $show->field('new_total_sender_level', __('New total sender level'));
        $show->field('old_total_received_level', __('Old total received level'));
        $show->field('new_total_received_level', __('New total received level'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ChangeLevelHistory());

        $form->number('user_id', __('User id'));
        $form->number('admin_id', __('Admin id'));
        $form->number('old_total_sender_level', __('Old total sender level'));
        $form->number('new_total_sender_level', __('New total sender level'));
        $form->number('old_total_received_level', __('Old total received level'));
        $form->number('new_total_received_level', __('New total received level'));

        return $form;
    }
}
