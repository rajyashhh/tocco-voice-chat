<?php

namespace App\Admin\Controllers;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\VipAuth;
use Encore\Admin\Layout\Content;
use App\Services\AppFeatureService;
use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\HasResourceActions;

class VipAuthController extends Controller
{
    use HasResourceActions;

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("vips");
    }

    public function store()
    {
        if (! Admin::user()->can('*')) {
            abort(403);
        }

        return $this->form()->store();
    }

    public function update($id)
    {
        if (! Admin::user()->can('*')) {
            abort(403);
        }

        return $this->form()->update($id);
    }

    public function destroy($id)
    {
        if (! Admin::user()->can('*')) {
            abort(403);
        }

        return $this->form()->destroy($id);
    }

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
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
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VipAuth);

        $grid->id('ID');
        $grid->column('type',trans ('type'))->select (
            [
                3=>trans('vip'),
                5=>trans ('guardian cp')
            ]
        );
        $grid->column('level',trans ('level'));
        $grid->column('enable',trans ('enable'))->switch (Common::getSwitchStates ());
        $grid->column('name',trans ('name'));
        $grid->column('title',trans ('title'));
        $grid->column('img_0',trans ('img_0'))->image ('',30);
        $grid->column('img_1',trans ('img_1'))->image ('',30);
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
        $show = new Show(VipAuth::findOrFail($id));

        $show->id('ID');
        $show->type('type');
        $show->level('level');
        $show->enable('enable');
        $show->name('name');
        $show->title('title');
        $show->img_0('img_0');
        $show->img_1('img_1');
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
        $form = new Form(new VipAuth);

        $form->display('ID');
        $form->select('type', trans('type'))->options (
            [
                3=>trans('vip'),
                5=>trans ('guardian cp')
            ]
        );
        $form->text('level', trans('level'));
        $form->switch('enable', trans('enable'))->states (Common::getSwitchStates ());
        $form->text('name', trans('name'));
        $form->text('title', trans('title'));
        $form->image('img_0', trans('img_0'));
        $form->image('img_1', trans('img_1'));

        return $form;
    }
}
