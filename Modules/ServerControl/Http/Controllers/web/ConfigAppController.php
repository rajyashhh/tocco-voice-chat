<?php

namespace Modules\ServerControl\Http\Controllers\web;

use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Whatsapp\Entities\WhatsappApp;

class ConfigAppController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'ConfigApp';

    public $permission_name = 'config-apps';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        return parent::index($content);
    }

    public function show($id, Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        return parent::show($id, $content);
    }

    public function edit($id, Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        return parent::edit($id, $content);
    }

    public function create(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('create-' . $this->permission_name);
        }

        return parent::create($content);
    }

    public function store()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('create-' . $this->permission_name);
        }

        return parent::store();
    }

    public function update($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        return parent::update($id);
    }

    public function destroy($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('delete-' . $this->permission_name);
        }

        return parent::destroy($id);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WhatsappApp());
        $grid->model()->where("type","config");
        $grid->column('id', __('Id'));
        $grid->column('uuid', __('uuid'));
        $grid->column('username', __('name'));
        $grid->column('password', __('Password'));
        $grid->column('webhook_url', __('Webhook url'));
        $grid->column('phone', __('Phone'));
        $grid->column('phone_id', __('Phone Number Id'));


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
        $show = new Show(WhatsappApp::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('uuid', __('uuid'));
        $show->field('username', __('name'));
        $show->field('password', __('Password'));
        $show->field('webhook_url', __('Webhook url'));
        $show->field('phone', __('Phone'));
        $show->field('phone_id', __('Phone Number Id'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WhatsappApp());
        $form->hidden('type', __('name'))->value("config");
        $form->hidden('config', __('name'))->value(1);
        $form->text('username', __('name'));
        $form->password('password', __('Password'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly');
        $form->text('webhook_url', __('Webhook url'));
        $form->text('phone', __('Phone'));
        $form->text('phone_id', __('Phone Number Id'));

        return $form;
    }
}
