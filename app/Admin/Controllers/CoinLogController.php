<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\CoinLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CoinLogController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'coin-logs';
    public $hiddenColumns = [

    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CoinLog);
        $grid->model ()->with('user:id,uuid')->where ('status',1);
        $grid->filter (function (Grid\Filter $filter){
            $filter->expand ();
            $filter->column(1/2, function ($filter) {
                $filter->notEqual('uuid',__ ('uuid'));
                $filter->notEqual('created_at','from')->date();
                $filter->notEqual('updated_at','to')->date();
            });
            $filter->column(1/2, function ($filter) {
                $filter->like('method',__ ('method'));
            });
        });
        if (request ('uuid')){
            $user = User::where('uuid', request('uuid'))->first();
            if ($user) {
                $grid->model()->where('user_id', $user->id);
            } else {
                $grid->model()->whereRaw('1=0');
            }
        }
        if (request ('created_at')){
            $grid->model ()->where ('created_at','>=',request ('created_at'));
        }
        if(request ('updated_at')){
            $grid->model ()->where ('created_at','<=',request ('updated_at'));
        }
        $grid->model ()->where ('method','strip');
        $grid->id('ID');
        $grid->paid_usd(__('paid usd'));
        $grid->obtained_coins(__('obtained coins'));
        $grid->user_id(__('user id'));
        $grid->column('uuid',__('user uuid'))->display (function (){
            return $this->user?->uuid;
        });
        $grid->method(__('method'));
//        $grid->donor_id(__('donor id'));
//        $grid->donor_type(__('donor type'));
        $grid->column('status',__('status'))->using ([0=>__ ('unpaid'),1=>__ ('paid')]);
        $grid->trx(__('trx_no'));
        $grid->pid(__('payment id'));
        $grid->column('created_at',__ ('admin.created_at'))->display (function (){
            return $this->created_at->format('Y-m-d');
        });
        $grid->disableCreateButton ();
        $grid->disableActions ();
        $this->extendGrid ($grid);
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
        $show = new Show(CoinLog::findOrFail($id));

//        $show->id('ID');
//        $show->paid_usd('paid_usd');
//        $show->obtained_coins('obtained_coins');
//        $show->user_id('user_id');
//        $show->method('method');
//        $show->donor_id('donor_id');
//        $show->donor_type('donor_type');
//        $show->status('status');
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
        $form = new Form(new CoinLog);
//
//        $form->display('ID');
//        $form->text('paid_usd', __('paid_usd'));
//        $form->text('obtained_coins', __('obtained_coins'));
//        $form->text('user_id', 'user_id');
//        $form->text('method', 'method');
//        $form->text('donor_id', 'donor_id');
//        $form->text('donor_type', 'donor_type');
//        $form->text('status', 'status');
//        $form->display(trans('admin.created_at'));
//        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
