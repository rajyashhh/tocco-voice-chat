<?php

namespace Modules\CP\Http\Controllers\web;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Enums\AchievementType;
use Modules\CP\Entities\CpRelation;
use Encore\Admin\Layout\Content;

class CpRelationController extends MainController
{
     public $permission_name = 'cp-relation';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('cp-relations'))
            ->body($this->grid()));
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('cp-relations'))
            ->body($this->detail($id)));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('cp-relations'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('cp-relations'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new CpRelation());
        $grid->disableRowSelector();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->equal('type', __('type'))->select([
                'friend' => trans('friend'),
                'bro' => trans('bro'),
                'lovely' => trans('lovely'),
                'solution' => trans('solution'),
            ]);
        });
        $grid->column('id', __('Id'));
        $grid->column("title", __("title"));
        $grid->column("description", __("description"));
        if (!request()->filled('_export_')) {
            $grid->column('image', __('Img'))->image('', 30, 30);
        }
        $grid->column("price", __("price"));
        $grid->column("type", __("type"))->display(function () {
            return $this->type;
        });
        $grid->column("relations_number", __("relations number"));
        if (!request()->filled('_export_')) {
            $grid->column('الاجرائات')->display(function () {
                if ($this->type === 'solution') {
                    return '';
                } else {

                    $url = url('admin/cp-levels/' . $this->id);
                    $button = "<a href='{$url}' class='btn btn-sm btn-info'>المستويات (levels)</a>";
                    return $button;
                }
            });
        }
        
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
        $show = new Show(CpRelation::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('valid_image', __('Valid image'));
        $show->field('invalid_image', __('Invalid image'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CpRelation());
        $this->disableFormTools($form);

        $form->text('title', __('title'))->rules('required');
        $form->image('image', __('Img'));
        $form->number('price', __('price'))->rules('required|min:1');

        $form->select('type', __('Type'))->options([
            'bro' => __('bro'),
            'friend' => __('friend'),
            'lovely' => __('lovely'),
            'solution' => __('solution'),
        ])->default(0)->rules('required')->when('!=', 'solution', function ($form){
            $form->switch('relations_number', __('relations number'))->default(0)->rules('required')->help(__("admin.relations_help"));
        });

        $form->saving(function (Form $form) {
            if ($form->type == 'solution') {
                $form->relations_number = 1;
            }
        });

        Admin::script(<<<'JS'
        function toggleRelationSwitch() {
            const type = $('select[name="type"]').val();
            const switchField = $('input[name="relations_number"]').closest('.form-group');

            if (type === 'solution') {
                switchField.hide();
            } else {
                switchField.show();
            }
        }

        $(document).ready(function () {
            toggleRelationSwitch();
            $('select[name="type"]').on('change', toggleRelationSwitch);
        });
    JS);

        return $form;
    }
}
