<?php

namespace App\Admin\Controllers\AgencyControllers;

use App\Admin\Controllers\MainController;
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

class ChargeController extends MainController
{
    use HasResourceActions;

    protected $title = "Charges";

    public $permission_name = "charges-agency";

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
        $grid->model ()->where ('charger_id',@Auth::id ());
        $grid->model ()->orderByDesc ('id');
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

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Charge::find($id));

//        $show->id('ID');
//        $show->charger_id('charger_id');
//        $show->charger_type('charger_type');
//        $show->user_id('user_id');
//        $show->user_type('user_type');
//        $show->amount('amount');
//        $show->amount_type('amount_type');
//        $show->created_at(trans('admin.created_at'));
//        $show->updated_at(trans('admin.updated_at'));
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
        $form = new Form(new Charge);

//        $form->display('ID');
//        $form->text('charger_id', 'charger_id');
//        $form->text('charger_type', 'charger_type');
//        $form->text('user_id', 'user_id');
//        $form->text('user_type', 'user_type');
//        $form->text('amount', 'amount');
//        $form->text('amount_type', 'amount_type');
//        $form->display(trans('admin.created_at'));
//        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
