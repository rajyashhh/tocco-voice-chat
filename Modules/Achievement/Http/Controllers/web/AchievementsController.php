<?php

namespace Modules\Achievement\Http\Controllers\web;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Enums\AchievementType;
use Encore\Admin\Layout\Content;

class AchievementsController extends MainController
{
    public $permission_name = 'achievement';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Achievements'))
            ->body($this->grid()));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Achievements'))
            ->body($this->form()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id,$content
            ->title(trans('Achievements'))
            ->body($this->form()->edit($id)));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Achievements'))
            ->body($this->detail($id)));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Achievement());

        $grid->column('id', __('Id'));
        // $grid->column('type', __('Type'));

        $grid->column('type', __('Type'))->display(function ($value) {
            $prefix = request()->route()->getPrefix();
            $baseUrl = ($prefix === '/preview/admin') ? url('preview/admin/gift-achievements') : url('admin/gift-achievements');

            $lang = app()->getLocale();
            $add = 'اضف هدايا مستخدمين ';
            if($lang == 'en'){
                $add = 'Add gifts users ';
            }

            $button = '';
            if (Admin::user()->can('browse-' . 'user_achievement_level') || Admin::user()->can('*')) {
                $button = '<a href="' . $baseUrl . '?achievement_id=' . $this->getKey() . '" class="btn btn-xs btn-primary">'. $add . '</a>';
            }
            $button2 = ($value === 'gift_target') ? $button : null;

            return $value . '<br>' . $button2;
        });

        // $grid->column('valid_image', __('Valid image'));
        // $grid->column('invalid_image', __('Invalid image'));
        $grid->column('valid_image', __('Valid image'))->display(function ($value) {
            $value = getDriverUrl() . '/' . $value;
            return "<img src='$value' width='80' height='80'>";
        });

        $grid->column('invalid_image', __('Invalid image'))->display(function ($value) {
            $value = getDriverUrl() . '/' . $value;
            return "<img src='$value' width='80' height='80'>";
        });
        // $grid->column('description', __('description'));

        if (Admin::user()->can('browse-' . 'achievement_level') || Admin::user()->can('*')) {
            $grid->column(__('redirect_button'))->display(function ($value) {

                // $prefix = request()->route()->getPrefix();
                // $baseUrl = ($prefix === '/preview/admin') ? url('preview/admin/achievement-levels/') :
                $baseUrl =   url('admin/achievements-levels/');
                $url1 = url($baseUrl .'/'. $this->id);
                $lang = app()->getLocale();
                $add = 'اضف انواع';

                if($lang == 'en'){
                    $add = 'add types';
                }

                $button =
                    //'<a href="' . $baseUrl . '/' . $this->id . '" class="btn btn-xs btn-primary">اضافة انواع</a>';
                    "<a href='{$url1}' class='btn btn-xs btn-primary'>". $add . "</a>";
                return $button;
            });
        }

        // $grid->column('type', __('Type'));

        // $grid->column(__('redirect_button_user_gift'))->display(function ($value) {
        //     $redirectRoute = 'User-gift';
        //     $button = '<a href="'.route($redirectRoute, ['achievement_id' => $this->getKey()]).'" class="btn btn-xs btn-primary">اضف هدايا مستخدمين</a>';
        //     return $button;
        // });ump

        // dd($button);

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        $grid->disableCreateButton();
        //  $grid->disableActions();

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
        $show = new Show(Achievement::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('valid_image', __('Valid image'));
        $show->field('invalid_image', __('Invalid image'));
        // $show->field('created_at', __('Created at'));
        // $show->field('updated_at', __('Updated at'));
        // $show->field('deleted_at', __('Deleted at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Achievement());
        $this->disableFormTools($form);


        $form->select('type', __('Type'))->options(function () {
            $ops = [0 => ''];
            $typs = AchievementType::cases();
            foreach ($typs as  $cases) {
                $ops[$cases->value] = __($cases->value);
            }
            return $ops;
        });
        $form->image('valid_image', __('Valid image'));
        $form->image('invalid_image', __('Invalid image'));
        // $form->saving(function (Form $form) {
        //     // $data = $form->input('valid_image');
        //     // $add = Achievement::saveAchievement($data);


        //     //    redirect()->route(nameRoute('admin.achievements'));

        // });

        return $form;
    }
}
