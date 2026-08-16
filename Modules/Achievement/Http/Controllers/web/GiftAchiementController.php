<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Gift;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class GiftAchiementController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'gift_achievement';
    public $hiddenColumns = [

    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Gift);

        $grid->id('ID');
        $grid->name(__('name'));
        $grid->e_name(__('admin.e_name'));
        $grid->type(__('type'));
        $grid->vip_level(__('admin.vip_level'));
        $grid->column('hot',trans ('hot'));
        $grid->column('is_play',trans ('admin.is_play'))->switch (Common::getSwitchStates ());
        $grid->price(trans ('admin.price'));
        $grid->column('img',trans ('image'))->image ('','30');
        $grid->column('show_img',trans ('admin.show_img'))->image ('','30');
        $grid->column('show_img2',trans ('admin.show_img2'))->image ('','30');
        $grid->sort(trans ('admin.sort'));
        $grid->column('enable',trans ('enable'))->switch (Common::getSwitchStates ());
        $this->extendGrid ($grid);

        $grid->model()->where('type',7);

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
        $show = new Show(Gift::findOrFail($id));

//        $show->id('ID');
//        $show->name(__('name'));
//        $show->e_name(__('e_name'));
//        $show->type(__('type'));
//        $show->vip_level(__('vip_level'));
//        $show->hot(__('hot'));
//        $show->is_play('is_play');
//        $show->price(__('price'));
//        $show->img(__('img'));
//        $show->show_img(('show_img'));
//        $show->show_img2('show_img2');
//        $show->sort('sort');
//        $show->enable('enable');

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


        $form = new Form(new Gift);

        $form->display(__('admin.ID'));
        $form->text('name', __('admin.name'));
        $form->text('e_name', __('admin.e_name'));
        $form->select('type', __('admin.type'))->options (
            [
                7=>__ ('gift_achievement'),


            ]
        )->required()->default(7);
        // $form->number('luckyGift.win_probability', __('win_probability'))->min(10)->max(100)->placeholder(__('Enter win_probability'));
        // $form->number('luckyGift.win_probability', __('win_probability'))
        //      ->min(10)
        //      ->max(100)
        //      ->placeholder(__('Enter win_probability'));
        $form->number('vip_level', __('admin.vip_level'))->min (0)->placeholder (__ ('less than 256'));
//        $form->number('hot', 'hot')->min (0);
//        $form->switch('is_play', __ ('is_play'))->states (Common::getSwitchStates ());
        $form->currency('price', __('admin.price'))->symbol ('💎');
        $form->file('img', __('admin.img'));
        $form->file('show_img', __('admin.show_img'));
        $form->file('show_img2', __('admin.show_img2'));
        // $form->number('sort', __('admin.sort'));
        $form->switch('enable', __('enable'))->states (Common::getSwitchStates ());


        return $form;
    }
}
