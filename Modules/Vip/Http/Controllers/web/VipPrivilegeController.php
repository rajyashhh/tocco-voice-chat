<?php

namespace Modules\Vip\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\Vip\Entities\VipPrivilege;
use Encore\Admin\Layout\Content;
use App\Services\AppFeatureService;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;

class VipPrivilegeController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'vip-privilege';
    public $hiddenColumns = [];

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("vips");
    }

    public function index(Content $content)
    {
        return parent::index(
            $content->title(trans('vip_privilege'))->body($this->grid())
        );
    }

    public function show($id, Content $content)
    {
        return parent::show(
            $id,
            $content->title(trans('vip_privilege'))->body($this->detail($id))
        );
    }

    public function edit($id, Content $content)
    {
        return parent::edit(
            $id,
            $content->title(trans('vip_privilege'))->body($this->form()->edit($id))
        );
    }

    public function create(Content $content)
    {
        return parent::create(
            $content->title(trans('vip_privilege'))->body($this->form())
        );
    }

    protected function grid()
    {
        $grid = new Grid(new VipPrivilege);

        $grid->id(__('admin.ID'));

        $grid->column('name', __('name'))->display(function () {
            return app()->getLocale() === 'en' ? $this->en_name : $this->name;
        });

        $grid->column('title', __('title'))->display(function () {
            return app()->getLocale() === 'en' ? $this->en_title : $this->title;
        });

        $grid->type(__('type'));
        $grid->img1(__('img'))->image('', 30);

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $this->extendGrid($grid);
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(VipPrivilege::findOrFail($id));
        $this->extendShow($show);
        return $show;
    }

    protected function form()
    {
        $form = new Form(new VipPrivilege);
        $this->disableFormTools($form);

        $form->text('name', __('name'))->rules('required');
        $form->text('en_name', __('en_name'))->rules('required');
        $form->text('title', __('title'))->rules('required');
        $form->text('en_title', __('Title en'))->rules('required');

        $form->select('type', __('type'))->options([
            4 => trans('Avatar Frame'),
            5 => trans('Bubble Frame'),
            6 => trans('Vehicle'),
            9 => trans('NoKick'),
            10 => trans('Icon'),
            12 => trans('Wappel'),
            13 => trans('hide country'),
            14 => trans('vip gifts'),
            15 => trans('no ban profile'),
            16 => trans('hidden room'),
            17 => trans('anonymous man'),
            18 => trans('colored name'),
            19 => trans('profile visitors hide in'),
            20 => trans('last login'),
            21 => trans('sound effect'),
            22 => trans('upload GIF image'),
            28 => trans('profile frame'),
        ])->rules('required');

        $form->file('img1', __('active image'))->removable()->rules('required');
        $form->file('img2', __('inactive image'))->removable()->rules('required');

        return $form;
    }

}
