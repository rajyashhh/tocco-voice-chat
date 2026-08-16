<?php

namespace App\Admin\Controllers;


use Encore\Admin\Grid;

use App\Models\BlackList;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Admin\Actions\DeleteBlack;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class BlackListUsersController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'black-list';
   
    public function index(Content $content)
    {
        return $content
            ->title(trans('Black List Users'))
            ->body($this->grid());
    }
    

  
    protected function grid()
    {
        $userId = request('user_id');
        $grid = new Grid(new BlackList);
        $grid->model()->whereHas('blockedPerson');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;

                    $query->whereHas('blockedPerson', function ($query) use ($input) {
                        $query->where('name', 'like', "%$input%")
                        ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('User'))->placeholder(__('Search by name or UUID'));
            });
        });
        $grid->model()->where('user_id',$userId );
        $grid->column('blockedPerson.name',__ ('name'));
        $grid->column('blockedPerson.uuid',__ ('uuid'));
        $grid->column('created_at', __('created'))->display(function () {
            return \Carbon\Carbon::createFromTimestamp(strtotime($this->created_at))
                                 ->timezone(auth()->user()->time_zone)->format("Y-m-d h:i A");
        });
        $grid->actions(function ($actions) {
            $model = $actions->row;
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
            $actions->add(new DeleteBlack($model->id));
        });
        $grid->disableCreateButton();
        $this->extendGrid ($grid);
        $grid->disableExport();
        return $grid;
    }
}