<?php

//namespace App\Admin\Controllers;

//use App\Models\DailyTask;

namespace Modules\Tasks\Http\Controllers;//App\Admin\Controllers;

use Modules\Tasks\Entities\DailyTask;
use Modules\Tasks\Entities\Day;
use Modules\Tasks\Selectable\Days;
use Illuminate\Support\Facades\Request;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class DailyTaskController extends AdminController
{
    protected $title = 'DailyTask';

    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid());
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        $model = DailyTask::findOrFail($id);

        $form = $this->form()->edit($id);

        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($form);
    }

    public function create(Content $content)
    {
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    public function update($id)
    {
        $id = request()->route('id');
        return $this->form()->update($id);
    }

    protected function grid()
    {
        $grid = new Grid(new DailyTask());
        $dayId = request('day_id');
        $grid->model()->where('day_id', $dayId);
        $grid->column('id', __('Id'));
        $grid->column('title', __('Title'));
        $grid->column('type', __('Type'));
        $grid->column('sub_type', __('Sub Type'));
        $grid->column('count', __('Count'));
        $grid->column('total_points', __('Total Points'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->tools(function ($tools) use ($dayId){
            $day = Day::find($dayId);
            $createUrl = url('admin/days');
            $tools->append('<a href="' . $createUrl . '" class="btn btn-success btn-sm">الذهاب الي قائمه الايام</a>');
            $tools->append('<div><h5 style="color:yellow">مهام  '.$day?->title.' </h5></div>');
        });

        return $grid;
    }


    protected function detail($id)
    {
        $show = new Show(DailyTask::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('day_id', __('Day id'));
        $show->field('title_en', __('Title'));
        $show->field('type', __('Type'));
        $show->field('sub_type', __('Sub type'));
        $show->field('count', __('Count'));
        $show->field('total_points', __('Total points'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new DailyTask());

        $dayId = request('day_id');

        if ($dayId) {
            $form->hidden('day_id')->default($dayId);
        } else {
            $form->select('day_id', 'Day')
                ->options(Day::pluck('title', 'id'))
                ->required();
        }

        $form->select('type', __('Type'))
            ->options([
                'images' => __('images'),
                'enter_room' => __('Enter Room'),
                'up_mic' => __('Up mic')
            ])
            ->required()
            ->when('images', function (Form $form) {
                $form->select('sub_type', __('Sub Type'))
                    ->options([
                        'profile' => __('Profile'),
                        'row' => __('Room')
                    ])
                    ->placeholder('Select Sub Type')
                    ->default(null);
            });

        $form->text('title', __('Title'))->required();

        $form->number('count', __('Count'))
            ->attribute(['step' => 1])
            ->min(0)
            ->default(0)
            ->required();

        $form->number('total_points', __('Total Points'))
            ->attribute(['step' => 1])
            ->min(0)
            ->default(0)
            ->required();


            $form->saving(function (Form $form) use ($dayId) {
                if ($dayId) {
                    $form->day_id = $dayId;
                }
            });

        return $form;
    }

}
