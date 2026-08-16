<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Helpers\Common;
use App\Models\User;
use App\Models\RoomTarget;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class RoomTargetController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'room-target';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('room-target'))
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
            ->title(trans('room-target'))
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
            ->title(trans('room-target'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('room-target'))
            ->body($this->form()));
    }

    protected function grid()
    {

        $grid = new Grid(new RoomTarget);
        $grid->column('coins', __("coins"));
        $grid->column('usd', __("usd"));
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(RoomTarget::findOrFail($id));
        $this->extendShow($show);
        return $show;
    }

    protected function form()
    {
        $form = new Form(new RoomTarget);
        $form->number('coins', 'coins');
        $form->number('usd', 'usd');
        return $form;
    }
}
