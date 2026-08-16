<?php

namespace Modules\RoomBoom\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Modules\RoomBoom\Entities\SuperBoomRule;

class SuperBoomRuleController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'super-boom-rules';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Super Boom Rules'))
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
            ->title(__('Super Boom Rule Details'))
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
            ->title(__('Edit Super Boom Rule'))
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
            ->title(__('Create Super Boom Rule'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperBoomRule());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('rules_ar', __('Rules (AR)'))->limit(50);
        $grid->column('rules_en', __('Rules (EN)'))->limit(50);
        $grid->column('content', __('Content'))->limit(50);
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('updated_at', __('Updated At'))->sortable();

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
        $show = new Show(SuperBoomRule::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('rules_ar', __('Rules (AR)'));
        $show->field('rules_en', __('Rules (EN)'));
        $show->field('content', __('Content'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SuperBoomRule());

        $form->textarea('rules_ar', __('Rules (AR)'))->rows(5);
        $form->textarea('rules_en', __('Rules (EN)'))->rows(5);
        $form->textarea('content', __('Content'))->rows(7);

        return $form;
    }
}
