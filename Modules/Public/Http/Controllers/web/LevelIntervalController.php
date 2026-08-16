<?php

namespace Modules\Public\Http\Controllers\web;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Modules\Public\Entities\LevelInterval;
use Encore\Admin\Layout\Content;



class LevelIntervalController extends MainController
{
    public $permission_name = 'level-interval';
    /**
     * Title for current resource.
     *
     * @var string
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Level Gifts'))
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
            ->title(trans('Level Gifts'))
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
            ->title(trans('Level Gifts'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Level Gifts'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LevelInterval());

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('type', __('type'))->select([
                    1 => __('receiver'),
                    2 => __('sender'),
                    3 => __('room'),

                ]);
            });

             $filter->column(1 / 2, function ($filter) {
                $filter->equal('min', __('min'));
            });
             $filter->column(1 / 2, function ($filter) {
                $filter->equal('max', __('max'));
            });
        });


        $grid->column('id', __('Id'));
        $grid->column('name', __('name'));
        $grid->column('type', __('type'))->display(function ($value) {
            return  $this->type == 3 ? "room" : ($this->type == 1 ? "receiver" : "sender");
        });
        $grid->column('min', __('min'));
        $grid->column('max', __('max'));

        if (!request()->filled('_export_')) {
            if (Admin::user()->can('browse-' . 'reward_level_interval') || Admin::user()->can('*')) {
                $grid->column(__('Procedures'))->display(function () {
                    // توليد الروابط
                    $url1 = url('admin/reward_level_interval/' . $this->id);
                    $gifts = __('Gifts');
                    // إنشاء أزرار HTML
                    $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . $gifts . " </a>";

                    // دمج الأزرار في سلسلة واحدة وإرجاعها
                    return $button1;
                });
            }
        }


        $this->extendGrid($grid);
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
        $show = new Show(LevelInterval::findOrFail($id));

        $show->field('id', __('id'));
        $show->field('name', __('name'));
        $show->field('min', __('min'));
        $show->field('max', __('max'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LevelInterval());
        $this->disableFormTools($form);

        $form->text('name', __('name'));
        $form->select('type', trans('type'))->options(
            [
                1 => trans('receiver'),
                2 => trans('sender'),
                3 => trans('room'),
            ]
        )->default(2);
        $form->number('min', __('min'));
        $form->number('max', __('max'));

        return $form;
    }
}
