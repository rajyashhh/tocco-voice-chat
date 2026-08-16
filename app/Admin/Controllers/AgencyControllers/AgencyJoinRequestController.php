<?php

namespace App\Admin\Controllers\AgencyControllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\User;
use App\Models\UsersJoinedAgency;
use App\Services\AppFeatureService;
use Encore\Admin\Actions\Response;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Modules\Milestones\Helpers\MilestoneHelper;

class AgencyJoinRequestController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    public $permission_name = 'agent-request';


    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }


    public function update($id)
    {

        if (request('_edit_inline') == "true") {
            if (request('status')) {
                request()->request->add(['change_status_admin_id' => Auth::id()]);
            }
        }
        return $this->form()->update($id);
    }

    public function index(Content $content)
    {
        return $content
            ->title(trans('Join To Agency Requests'))
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->title(trans('Join To Agency Requests'))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title(trans('Join To Agency Requests'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('Join To Agency Requests'))
            ->body($this->form());
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $agency = Agency::query()->where('owner_id', Auth::id())->first();
        $grid = new Grid(new AgencyJoinRequest);
        $grid->model()->where('agency_id', @$agency->id)->orderByDesc('id');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('status', __('status'))->select([0 => __('pending'), 1 => __('accepted'), 2 => __('denied')]);
            });
        });

        $grid->id('ID');
        $grid->column('user_id', __('user id'))->modal('user info', function ($model) {
            if ($model->user_id) {
                return Common::getUserShow($model->user_id);
            }
            return null;
        });
        $grid->column('agency_id', __('agency id'))->modal('agency info', function ($model) {
            if ($model->agency_id) {
                return Common::getAgencyShow($model->agency_id);
            }
            return null;
        });
        $grid->column('whatsapp', __('whatsapp'));
        $grid->column('status', __('status'))->using(
            [
                0 => __('pending'),
                1 => __('accepted'),
                2 => __('denied')
            ]
        );
        $grid->column('change_status_admin_id', __('change status admin id'))->modal('admin info', function ($model) {
            if ($model->change_status_admin_id) {
                return Common::getAdminShow($model->change_status_admin_id);
            }
            return null;
        });
        $grid->column('created_at', trans('time'))->diffForHumans();

        $grid->disableCreateButton();

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
        $show = new Show(AgencyJoinRequest::findOrFail($id));

        $show->id('ID');
        $show->user_id('user_id');
        $show->agency_id('agency_id');
        $show->status('status');
        $show->change_status_admin_id('change_status_admin_id');
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        $form = new Form(new AgencyJoinRequest);
        $form->display('ID');
        $form->text('user_id', 'user_id');
        $form->text('agency_id', 'agency_id');
        $form->select('status', 'status')->options(
            [
                0 => __('pending'),
                1 => __('accepted'),
                2 => __('denied')
            ]
        );;
        $form->hidden('change_status_admin_id', 'change_status_admin_id');
        $form->display(trans('admin.created_at'));
        $form->display(trans('admin.updated_at'));
        $form->saving(function (Form $form) {
            if ($form->model()->status == 1) {
                $user_id = $form->model()->user_id;
                $update = DB::table('users')
                    ->where('id', $user_id)
                    ->update(['type_user' => 1]);

                uploadMonthlyDiamondReceive($user_id, 0);

                $user = User::query()->where('id', $form->model()->user_id)->first();
                if ($user->agency_id) {
                    $error = new MessageBag(
                        [
                            'title'   => 'forbidden',
                            'message' => 'user already in agency',
                        ]
                    );
                }
                return back()->with(compact('error'));
                MilestoneHelper::grantMilestoneToUser($user, 'host');

                // UserCommon::userVip($user,'agency-join-form-dash');
                $checkAgencyUser = UsersJoinedAgency::where([
                    'user_id' => $user_id,
                    'agency_id' => $form->model()->agency_id,
                    'type' => 2,
                ])->where('leave_date', null)->exists();
                if (!$checkAgencyUser) {
                    UsersJoinedAgency::create([
                        'user_id' => $user_id,
                        'agency_id' => $form->model()->agency_id,
                        'type' => 2,
                        'join_date' => now(),
                        'status' => 'Joined'
                    ]);
                }
            }
        });



        //        $form->deleting (function (Form $form) {
        //            $user = User::query ()->where ('id',$form->model ()->user_id)->first ();
        //            if ($form->model ()->status == 1 && $user->agency_id == $form->model ()->id){
        //                $user->agency_id = 0;
        //                $user->save();
        //            }
        //        });


        return $form;
    }
}
