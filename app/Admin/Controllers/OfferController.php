<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Offer;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;

class OfferController extends MainController
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

    public $permission_name = 'advertising-space';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Advertising Space'))
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
            ->title(trans('offers'))
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
            ->title(trans('offers'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('offers'))
            ->body($this->form()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Offer());

        $grid->column('id', __('Id'));
        if (app()->getLocale() == 'ar') {
            $grid->column('title', __('title'));
            $grid->column('body', __('body'));
        } else {
            $grid->column('title_en', __('title'));
            $grid->column('body_en', __('body'));
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
        $show = new Show(Offer::findOrFail($id));

        $show->field('id', __('Id'));
        if (app()->getLocale() == 'ar') {
            $show->field('title', __('title'));
            $show->field('body', __('body'));
        } else {
            $show->field('title_en', __('title'));
            $show->field('body_en', __('body'));
        }

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Offer());
        $this->disableFormTools($form);


        $form->textarea('title', __('title'));
        $form->textarea('body', __('body'));
        $form->textarea('title_en', __('title_en'));
        $form->textarea('body_en', __('body_en'));

        return $form;
    }
}
