<?php

namespace Modules\Achievement\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;


use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\GiftAchievement;



class UserGiftAchController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */


    protected $title = 'giftAchievement';
    public $permission_name = 'user_achievement_level';

    /**
     * Make a grid builder.
     *
     * @return Content
     */


    public function index(Content $content)
    {
        return parent::index($content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid()));
    }


    public function create(Content $content)
    {

        $achievement_id = session()->get('achievement_id');
        return parent::create($content->header(trans('admin.create'))->description(trans('admin.description'))->body(view('admin.grid.users.UserGiftAchivement', compact('achievement_id'))))->render();
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('gift achievements'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('gift achievements'))
            ->body($this->form()->edit($id)));
    }


    protected function grid()
    {
        $grid = new Grid(new GiftAchievement());
        $grid->disableRowSelector();
        $id = request()->input('achievement_id');
        if ($id) {
            session()->put('achievement_id', $id);
        }
        $grid->column('id', __('Id'));
        $grid->column('Achievement.type', __('Achievement name'));
        $grid->column('gift.name', __('Gift name'));
        $grid->column('user.name', __('User name'));
        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));
        // $grid->disableCreateButton();

        //        $grid->disableEditButton();

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
        $show = new Show(GiftAchievement::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('achievement_id', __('Achievement id'));
        $show->field('gift_id', __('Gift id'));
        $show->field('user_id', __('User id'));
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
        $form = new Form(new GiftAchievement());
        $this->disableFormTools($form);

        $form->number('achievement_id', __('Achievement id'));
        $form->number('gift_id', __('Gift id'));
        $form->number('user_id', __('User id'));

        return $form;
    }

    // public function destroy ( $id )
    // {

    //     GiftAchievement::findOrFail($id)->delete();
    //     return parent ::destroy ($id);

    // }
}
