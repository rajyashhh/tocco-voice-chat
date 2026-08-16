<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Question;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;


class QuestionController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'questions';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('questions'))
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
            ->title(trans('questions'))
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
            ->title(trans('questions'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('questions'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new Question());

        $grid->column('id', __('Id'));
        $grid->column('question', __('question'));
        $grid->column('answer', __('answer'));
        $grid->column('status', __('status'))->switch(Common::getSwitchStates());
        $this->extendGrid($grid);
        return $grid;
    }


    protected function detail($id)
    {
        $show = new Show(Question::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('question', __('question'));
        $show->field('answer', __('answer'));
        return $show;
    }


    protected function form()
    {
        $form = new Form(new Question());
        $this->disableFormTools($form);


        $form->text('question', __('question'))->required();
        $form->textarea('answer', __('answer'))->required();
        $form->switch('status', __('status'))->states(Common::getSwitchStates());

        return $form;
    }
}
