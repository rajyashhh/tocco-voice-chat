<?php

namespace Modules\SpecialId\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Models\Emoji;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\SpecialId\Entities\SpecialIdFram;
use Encore\Admin\Layout\Content;
use App\Rules\HexColor;

class SpecialIdFramController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'special-frame';
    public function index(Content $content)
    {
        return $content
            ->title(trans('frames'))
            ->body($this->grid());
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
        return $content
            ->title(trans('frames'))
            ->body($this->detail($id));
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
        return $content
            ->title(trans('frames'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('frames'))
            ->body($this->form());
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SpecialIdFram);

        $grid->id( __ ('ID'));
        $grid->title(__('name'));
        $grid->column('image',__ ('img'))->image ('',30);
        $grid->column('color',__('color'));
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
        $show = new Show(Emoji::findOrFail($id));

        $show->id('ID');
        $show->title('name');
        $show->color('color');
        $show->image('image');
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SpecialIdFram());
        $form->text('title', __('name'));
        $form->image('image', __('img'));
        $form->color('color', __('color'))->rules(['nullable', new HexColor()]);

        return $form;
    }
}
