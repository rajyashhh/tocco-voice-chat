<?php

namespace App\Admin\Controllers;

use App\Models\Room;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\User;

use App\Admin\Selectable\Users;


class TestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Room';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    //id uid = user_id , room_name , room_cover ,  session
    protected function grid()
    {
        $grid = new Grid(new Room());

        $grid->column('id', __('Id'));
        //$grid->column('numid', __('Numid'));
        //$grid->column('uid', __('Uid'));
        $grid->column('user.name', __('User Name')); 
        $grid->column('user.profiles.avatar', __('Avatar'))->image(); 

        $grid->column('room_name', __('Room name'));
        $grid->column('room_cover', __('Room cover'));
        $grid->column('session', __('Session'));

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
        $show = new Show(Room::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('numid', __('Numid'));
        $show->field('uid', __('Uid'));
        $show->field('room_status', __('Room status'));
        $show->field('room_name', __('Room name'));
        $show->field('room_cover', __('Room cover'));
        $show->field('room_intro', __('Room intro'));
        $show->field('room_pass', __('Room pass'));
        $show->field('room_class', __('Room class'));
        $show->field('room_type', __('Room type'));
        $show->field('room_welcome', __('Room welcome'));
        $show->field('room_admin', __('Room admin'));
        $show->field('room_visitor', __('Room visitor'));
        $show->field('room_speak', __('Room speak'));
        $show->field('room_sound', __('Room sound'));
        $show->field('room_black', __('Room black'));
        $show->field('ranking', __('Ranking'));
        $show->field('is_popular', __('Is popular'));
        $show->field('secret_chat', __('Secret chat'));
        $show->field('is_top', __('Is top'));
        $show->field('sort', __('Sort'));
        $show->field('room_background', __('Room background'));
        $show->field('is_afk', __('Is afk'));
        $show->field('hot', __('Hot'));
        $show->field('room_judge', __('Room judge'));
        $show->field('microphone', __('Microphone'));
        $show->field('is_prohibit_sound', __('Is prohibit sound'));
        $show->field('is_recommended', __('Is recommended'));
        $show->field('play_num', __('Play num'));
        $show->field('free_mic', __('Free mic'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('mode', __('Mode'));
        $show->field('session', __('Session'));
        $show->field('hour_hot', __('Hour hot'));
        $show->field('top_room', __('Top room'));
        $show->field('max_admin', __('Max admin'));
        $show->field('count_room_socket', __('Count room socket'));
        $show->field('is_show_pk', __('Is show pk'));
        $show->field('top_user_id', __('Top user id'));
        $show->field('muted_users', __('Muted users'));
        $show->field('pin', __('Pin'));
        $show->field('charizma_status', __('Charizma status'));
        $show->field('charizma_timestamp', __('Charizma timestamp'));
        $show->field('sort_num', __('Sort num'));
        $show->field('game_id', __('Game id'));
        $show->field('total_diamond', __('Total diamond'));
        $show->field('level', __('Level'));
        $show->field('exp', __('Exp'));
        $show->field('level_id', __('Level id'));
        $show->field('total_game_coins', __('Total game coins'));
        $show->field('writing_disabled', __('Writing disabled'));
        $show->field('is_pk_custom', __('Is pk custom'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Room());

        $form->text('numid', __('Numid'));
        //$form->number('uid', __('Uid'));
        //$form->select('uid', __('Uid'))->options(User::pluck('name', 'id'))->rules('required');
        //$form->select('uid', __('Uid'))->options(User::all()->pluck('name','id'))->rules('required');
        $form->belongsTo('uid', Users::class, 'Author');

        $form->text('room_name', __('Room name'));
        $form->text('room_cover', __('Room cover'));
        $form->number('session', __('Session'));
        return $form;
    }
}
