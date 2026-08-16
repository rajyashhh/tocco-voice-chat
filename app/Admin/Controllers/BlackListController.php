<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\BlackList;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;

class BlackListController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;

    public $permission_name = 'black-list';

    public function index(Content $content)
    {
        return $content
            ->title(trans('Black List'))
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
            ->title(trans('Black List'))
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
            ->title(trans('Black List'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->title(trans('Black List'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlackList);
        $grid->model()->whereHas('user')->select('user_id')->groupBy('user_id');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;

                    $query->whereHas('user', function ($query) use ($input) {
                        $query->where('name', 'like', "%$input%")
                        ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('User'))->placeholder(__('Search by name or UUID'));
            });
        });
        $grid->column('user.name',__ ('name'));
        $grid->column('user.uuid',__ ('uuid'));
        $grid->column('user_id','القائمه السوداء')->display(function ($user_id) {
            // توليد الروابط
            $url1 = url('admin/black-lists?user_id='.$user_id);
            $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>القائمه السوداء</a>";
            // دمج الأزرار في سلسلة واحدة وإرجاعها
            return $button1;
        });
        $grid->disableActions();
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
        $show = new Show(BlackList::findOrFail($id));

        $show->id(__ ('ID'));
        $show->user_id(__ ('user_id'));
        $show->from_uid(__ ('from_uid'));
        $show->status(__ ('status'));
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));
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
        $form = new Form(new BlackList);

        $form->display(__ ('ID'));
        $form->text('user_id', __ ('user_id'))->rules ('required');
        $form->text('from_uid', __ ('from uid'))->rules ('required');
        // $form->text('status', __ ('status'))->rules ('required');
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
