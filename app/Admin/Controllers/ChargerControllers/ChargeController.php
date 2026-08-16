<?php

namespace App\Admin\Controllers\ChargerControllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Charge;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;

class ChargeController extends AdminController
{
    use HasResourceActions;

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
            ->row(function($row) {
                $row->column(10, $this->grid());
                $row->column(2, view('admin.grid.users.actions'));
            });
    }




    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Charge);
        $grid->model ()->orderByDesc ('id');
        $grid->model ()->where ('charger_id',@Auth::id ());
        $grid->id('ID');
        $grid->column('charger_id',__('charger id'))->modal ('Charger info',function ($model){
            if ($model->charger_id){
                if ($model->charger_type == 'app'){
                    if (!User::query ()->where ('id',$model->user_id)->exists ()){
                        return null;
                    }
                    return Common::getUserShow($model->charger_id);
                }else{
                    if (!Admin::query ()->where ('id',$model->user_id)->exists ()){
                        return null;
                    }
                    return Common::getAdminShow($model->charger_id);
                }
            }
            return null;
        });
        $grid->column('charger_type',__ ('charger type'))->using (
            [
                'app'=>__ ('app'),
                'dash'=>__ ('office')
            ]
        );
        $grid->column('user_id',__('user id'))->modal ('User info',function ($model){
            if ($model->user_id){
                if ($model->user_type == 'app'){
                    if (!User::query ()->where ('id',$model->user_id)->exists ()){
                        return null;
                    }
                    return Common::getUserShow($model->user_id);
                }else{
                    if (!Admin::query ()->where ('id',$model->user_id)->exists ()){
                        return null;
                    }
                    return Common::getAdminShow($model->user_id);
                }

            }
            return null;
        });
        $grid->column('user_type',__ ('user type'))->using (
            [
                'app'=>__ ('app'),
                'dash'=>__ ('office')
            ]
        );
        $grid->amount(__('coins'));
//        $grid->amount_type('amount_type');
        $grid->column('created_at',trans('admin.created_at'))->diffForHumans ();
//        $grid->updated_at(trans('admin.updated_at'));
        $grid->disableActions ();
        $grid->disableCreateButton ();
        return $grid;
    }


}
