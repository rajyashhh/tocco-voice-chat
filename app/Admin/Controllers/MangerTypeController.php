<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Helpers\Common;
use App\Models\MangerType;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class MangerTypeController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'manger-type';

    /**
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('manger-types'))
            ->body($this->grid()));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('manger-types'))
            ->body($this->form()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id,$content
            ->title(trans('manger-types'))
            ->body($this->form()->edit($id)));
    }



    protected function grid()
    {
        $grid = new Grid(new MangerType);

        $grid->id(__('ID'));
        $grid->column('name_en', __("Name en"));
        $grid->column('name_ar', __("Name ar"));
        $grid->column('img', trans('image'))->image('', 30);
        $grid->column('description_en', __('Description en'));
        $grid->column('description_ar', __('Description ar'));
        $grid->column('badges', __('Badges'))->display(function () {
            $url = admin_url("manger-types/{$this->id}/badges");
            return '<a href="' . $url . '" class="btn btn-sm btn-info">
                        <i class="fa fa-certificate"></i> ' . __('Badges') . '
                    </a>';
        });
        $grid->disableExport();
        return $grid;
    }

    protected function form()
    {
        $form = new Form(new MangerType);

        $form->display(__('ID'));
        $form->text('name_en', __('Name en'));
        $form->text('name_ar', __('Name ar'));
        $form->image('img', trans('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });
        $form->text('description_en', __('Description en'));
        $form->text('description_ar', __('Description ar'));
        return $form;
    }
}
