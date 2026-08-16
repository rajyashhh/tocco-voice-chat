<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\FamilyLevel;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Services\AppFeatureService;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;

class FamilyLevelController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'families-level';
    public $hiddenColumns = [];
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("families");
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('family levels'))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('family levels'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('family levels'))
            ->body($this->form()));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('family levels'))
            ->body($this->detail($id)));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FamilyLevel);

        $grid->id(__('ID'));
        $grid->column('name', __('level'))->display(function ($name) {
            $path = @$this->img;
            $defaultImage = asset("images/level.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                  <strong>$name</strong><br>
            </div>
        ";
        });
        $grid->column('exp', __('exp'));
        $grid->column('members', __('members'));
        $grid->column('admins', __('admins'));

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="' . url('/admin/family-level') . '" target="_blank" class="btn btn-sm btn-success">
                <i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
        });


        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();
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
        $show = new Show(FamilyLevel::findOrFail($id));

        //        $show->id('ID');
        //        $show->name('name');
        //        $show->img('img');
        //        $show->exp('exp');
        //        $show->type('type');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FamilyLevel);
        $this->disableFormTools($form);


        $form->display(__('ID'));
        $form->text('name', __('Name ar'));
        $form->text('name_en', __('name_en'));
        $form->image('img', __('img'));
        $form->number('exp', __('exp'));
        $form->number('members', __('members'));
        $form->number('admins', __('admins'));
        //        $form->text('type', 'type');
        //        $form->display(trans('admin.created_at'));
        //        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
