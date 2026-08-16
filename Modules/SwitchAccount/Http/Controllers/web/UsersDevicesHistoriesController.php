<?php

namespace Modules\SwitchAccount\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;
use Modules\SwitchAccount\Entities\UserDevicesHistory;

class UsersDevicesHistoriesController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'UserDevicesHistory';
    public $permission_name = 'users-devices';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans(__($this->title)))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans(__($this->title)))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans(__($this->title)))
            ->body($this->form()));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans(__($this->title)))
            ->body($this->detail($id)));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UserDevicesHistory());
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('device_token', __('Device token'));
            });
        });

      
        $grid->column('user.name', __('name'));
        $grid->column('user.uuid', __('uuid'));
        $grid->column('device_token', __('Device token'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();

        

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
        $show = new Show(UserDevicesHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('device_token', __('Device token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('device_name', __('Device name'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserDevicesHistory());

        $form->number('user_id', __('User id'));
        $form->textarea('device_token', __('Device token'));
        $form->text('device_name', __('Device name'));

        return $form;
    }
}
