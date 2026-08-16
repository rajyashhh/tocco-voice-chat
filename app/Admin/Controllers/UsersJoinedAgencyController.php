<?php

namespace App\Admin\Controllers;

use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Content;
use App\Models\UsersJoinedAgency;
use App\Admin\Controllers\MainController;

class UsersJoinedAgencyController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'UsersJoinedAgency';
    public $permission_name = 'agency-join-logs';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Agency join logs'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UsersJoinedAgency());

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = request('join_date');

                    if ($date) {
                        $converted = \App\Helpers\UserCommon::convertArabicNumbers($date);
                        try {
                            $parsedDate = \Carbon\Carbon::parse($converted)->toDateString();
                            $query->whereDate('join_date', $parsedDate);
                        } catch (\Exception $e) {
                            // silently fail
                        }
                    }
                }, __('Join date'), 'join_date', request('join_date')) // ✅ return user input
                    ->date();
            });
        });

        $grid->model()->orderByDesc('id')->with(['user.profile', 'agency']);
        $grid->column('id', __('Id'));
        $grid->column('user.name', __('User'))
            ->display(function ($name) {
                if (!$this->user) {
                    return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
                }
                $uid = @$this->user->uuid;
                $path = @$this->user?->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </div>
            ";
            });
        $grid->column('agency.name', __('Agency'))
            ->display(function ($name) {
                if (!$this->agency) {
                    return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
                }
                $path = @$this->agency->img;
                $id = $this->agency->id;
                $defaultImage = asset("images/icon-agency.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Fallback if image doesn't exist
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                <img src='{$url}' alt='agency' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px;'>
                <span>{$name}</span>
                <span>id: $id</span>
            </div>
        ";
            });
        $grid->column('status', __('status'))->display(function ($name) {
            $name = __($name);
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>{$name} </span>

                </div>
            ";
        });
        $grid->column('join_date', __('Join date'));
        $grid->column('leave_date', __('Leave date'));

        $grid->disableActions();
        $grid->disableCreateButton();
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
        $show = new Show(UsersJoinedAgency::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('agency_id', __('Agency id'));
        $show->field('user_id', __('User id'));
        $show->field('type', __('Type'));
        $show->field('join_date', __('Join date'));
        $show->field('leave_date', __('Leave date'));
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
        $form = new Form(new UsersJoinedAgency());

        $form->number('agency_id', __('Agency id'));
        $form->number('user_id', __('User id'));
        $form->number('type', __('Type'));
        $form->datetime('join_date', __('Join date'))->default(date('Y-m-d H:i:s'));
        $form->datetime('leave_date', __('Leave date'))->default(date('Y-m-d H:i:s'));

        return $form;
    }
}
