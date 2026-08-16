<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Page;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class PageController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'page';
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Pages'))
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
            ->title(trans('Pages'))
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
            ->title(trans('Pages'))
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
            ->title(trans('Pages'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Page);

        $grid->id('ID');
        // $grid->type(__('type'));
        $grid->name(__('name_en'));
        $grid->column('link', __('url'))->display(function () {
            $url = url("/page/$this->name");
            return "<a href='$url'>$url</a>";
        });
        // $grid->content(__('content'));
        $grid->content(__('content'))->display(function ($content) {
            // Decode JSON content
            $decodedContent = json_decode($content, true);

            // You can customize how you display the decoded content here
            // For example, you can return specific values from the JSON
            // For simplicity, let's just return the entire decoded content
            return $decodedContent;
        });
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
        $show = new Show(Page::findOrFail($id));

        $show->id('ID');
        // $show->type('type');
        $show->name('name');
        $show->content('content');
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Page);
        $this->disableFormTools($form);
        $form->display('ID');
        // $form->text('type', __('type'));
        $form->text('name', __('name_en'));
        $form->textarea('content', __('content'));
        $form->textarea('content_en', __('content_en'));
        if ($form->model) {

            $content = json_decode($form->model->content, true);

            // Check if $content is a string (not an array), and then perform str_replace
            if (is_string($content)) {
                $content = str_replace('search_string', 'replacement_string', $content);
            }

            $form->textarea('content', function ($form) use ($content) {
                // You can use $content here, which may or may not have been replaced
                return $content;
            });


            $content_en = json_decode($form->model->content_en, true);

            // Check if $content is a string (not an array), and then perform str_replace
            if (is_string($content_en)) {
                $content = str_replace('search_string', 'replacement_string', $content_en);
            }

            $form->textarea('content', function ($form) use ($content_en) {
                // You can use $content here, which may or may not have been replaced
                return $content_en;
            });
        }

        return $form;
    }
}



