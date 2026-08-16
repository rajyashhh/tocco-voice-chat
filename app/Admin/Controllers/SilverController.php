<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Silver;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class SilverController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'gold-coins';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Silver'))
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
        return parent::show($id,$content
            ->title(trans('Silver'))
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
        return parent::edit($id,$content
            ->title(trans('Silver'))
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
            ->title(trans('Silver'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Silver);

        $grid->id('ID');
        $grid->column('coin', __('coin'))->display(function ($coin) {

            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$coin}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('silver',__ ('silver'));
        $grid->column('sort',__ ('sort'));
        $this->extendGrid ($grid);
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
        $show = new Show(Silver::findOrFail($id));

//        $show->id('ID');
//        $show->coin('coin');
//        $show->silver('silver');
//        $show->sort('sort');
//        $show->created_at(trans('admin.created_at'));
//        $show->updated_at(trans('admin.updated_at'));
        $this->extendShow ($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Silver);
        $this->disableFormTools($form);

        $form->display('ID');
        $form->number('coin', __('coin'));
        $form->number('silver', __('silver'));
        $form->number('sort', __('sort'));


        return $form;
    }
}
