<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Controllers\MainController;
use App\Models\CharismaLevel;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CharismaLevelController extends  MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'CharismaLevel';
    public $permission_name = 'charisma-levels';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Charisma Levels'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Charisma Levels'))
            ->body($this->detail($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Charisma Levels'))
            ->body($this->form()));
    }


    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Charisma Levels'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CharismaLevel());
        $grid->model()->orderBy('level', 'asc');
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('level', __('Level'));
        $grid->column('points', __('Points'));
        $grid->column('image', __('Image'))->display(function ($path) {

            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
        });

        $this->extendGrid($grid);
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
        $show = new Show(CharismaLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('level', __('Level'));
        $show->field('points', __('Points'));
        $show->field('image', __('Image'));
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
        $form = new Form(new CharismaLevel());

        $form->text('name', __('Name'));
        $form->number('level', __('Level'))->rules('unique:charisma_levels,level,{{id}}')->required();
        $form->number('points', __('Points'))->rules('unique:charisma_levels,points,{{id}}')->required();
        $form->file('image', __('Image'))->name(function ($file) {
            $extension = $file->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $file->guessExtension();
            }
            return 'img_' . now()->timestamp . '_' . rand(100, 999) . '.' . $extension;
        })->required();

        return $form;
    }
}
