<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Charge;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\AgencyApp\Entities\AgencyUserJob;

class AgencyUserJobController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'agencyUserJob';
    public $hiddenColumns = [

    ];

    public function index(Content $content)
    {
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid());
    }
    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return $this->form()->store();
    }
    public function edit($id, Content $content)
    {
        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }
    protected function grid()
    {
        $agency_id = \request('agency_id');
        $grid = new Grid(new AgencyUserJob());
        $grid->model ()->where('agency_id',$agency_id)->orderByDesc ('id');
        $grid->column('agency.name',__('agency'));
        $grid->column('agency_id',__('agency id'));
        $grid->column('user.name',__('user'));
        $grid->column('user.uuid',__('user id'));
        $grid->column('type',__('type'));

        $grid->disableCreateButton();

        $grid->tools(function (Grid\Tools $tools){
            $url = '/admin/agency-user-job/'.request('agency_id').'/create';
            $create_new = __('admin.create_new');
            $button = '<a href="'.$url.'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i>&nbsp;&nbsp;' . $create_new . '</a>';
            $tools->append($button);
        });
        $grid->actions(function ($actions) {
            $actions->disableEdit();

            // Add your custom action
            $actions->add(new \App\Admin\Actions\CustomEditAction());
        });
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
        $show = new Show(AgencyUserJob::findOrFail($id));

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

//    public function create(Content $content)
//    {
//        $agency_id=request('agency_id');
//        dd($agency_id);
//        return $content
//            ->header(trans('admin.create'))
//            ->description(trans('admin.description'))
//            ->body($this->form());
//    }

    protected function form()
    {
        $users=User::query()->where('agency_id',request('agency_id'))->pluck('name', 'id')->toArray();
        $form = new Form(new AgencyUserJob);
        $form->hidden('agency_id')->value(request('agency_id'));
        $form->select('user_id', __('user id'))->options ($users);
        $form->select('type', __('type'))->options ([
            'requestManger'=>'Request Manger'
        ] );

        return $form;
    }
}
