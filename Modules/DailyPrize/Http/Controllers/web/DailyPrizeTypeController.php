<?php

namespace Modules\DailyPrize\Http\Controllers\web;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Modules\DailyPrize\Entities\DailyGiftType;
use Encore\Admin\Layout\Content;

class DailyPrizeTypeController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'DailyGiftType';
    public $permission_name = 'daily-prize';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('daily prize'))
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
            ->title(trans('daily prize'))
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
            ->title(trans('daily prize'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('daily prize'))
            ->body($this->form()));
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DailyGiftType());

        // $grid->column('type', __('Type'));
        $grid->column('type', __('Type'))
            ->display(function ($type) {
                $weeks = [
                    1 => __('first_week'),
                    2 => __('second_week'),
                    3 => __('third_week'),
                    4 => __('fourth_week'),
                ];
                return $weeks[$type] ?? $type; // عرض النص بدلاً من الرقم
            });

        if (Admin::user()->can('browse-' . 'daily-gift') || Admin::user()->can('*')) {
            $grid->column(__('procedures'))->display(function () {
                $url1 = url('admin/daily-gifts/' . $this->type);
                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('add a daily login gift') . "</a>";
                return $button1;
            });
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
        $show = new Show(DailyGiftType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DailyGiftType());
        $this->disableFormTools($form);

        // $form->select('type', __('type'))->options([1 => 1, 2 => 2, 3 => 3, 4 => 4]);
        $form->select('type', __('type'))->options([
            1 => __('first_week'),
            2 => __('second_week'),
            3 => __('third_week'),
            4 => __('fourth_week'),
        ]);

        return $form;
    }
}
