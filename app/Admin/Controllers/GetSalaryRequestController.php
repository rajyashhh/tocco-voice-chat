<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AcceptRequestToGetSalary;
use App\Notifications\RefuseRequestToGetSalary;
use App\Models\RequestTakeSalary;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Models\UserSallary;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;

class GetSalaryRequestController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'get-salary-requests';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('requests-for-get-salary'))
            ->body($this->grid()));
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
        return parent::show($id,$content
            ->title(trans('requests-for-get-salary'))
            ->body($this->detail($id)));
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
        return parent::edit($id,$content
            ->title(trans('requests-for-get-salary'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('requests-for-get-salary'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RequestTakeSalary);
        $grid->model ()->where("status",0)->orderByDesc ('id');

        $grid->id(__ ('ID'));
        $grid->column ('user.name',__ ('name'));
        $grid->column ('user.id',__ ('id'));
        $grid->column ('amount',__ ('amount'));
        $grid->column ('phone',__ ('phone'));
        $grid->column ('gmail',__ ('gmail'));
        $grid->column ('country',__ ('country'));
        $grid->column('paymentWithDraw.name', __('Payment method'))->modal(__('data'), function ($model) {
            $data =$this->paymentWithDraw?->userWithdrawFields->where("user_id",$model->user_id);
            $results = [];
            if ($data != null) {
                foreach ($data as $da) {
                    $key = $da->payment_withdraw_field->name ?? null;
                    $value = $da->value ?? null;

                    if ($key) {
                        $results[$key] = $value;
                    }
                }
            }
            return new Table([__('Field Name'),__('Value')], $results);
        });
        // $grid->column ('bank_num',__ ('bank_num'));
        // $grid->column ('other',__ ('other'));
        $grid->column('status',__('status'))->using (
            [
                0=>__('pending'),
                1=>__ ('accepted'),
                2=>__ ('denied')
            ]
        );

        $grid->column('created_at',trans('time'));
        $this->extendGrid ($grid);
        $grid->disableCreateButton();
        $grid->disableExport();

        $grid->actions (function ($actions){
            $actions->disableDelete();
            // $actions->disableEdit();
            $actions->disableView();
            // if ($grid->country) {
            //     # code...
            // }
            // $actions->add(new DeliverdSalaryAction());
        });
        $grid->tools(function (Grid\Tools $tools){
            $url = '/admin/requests-for-get-salary-history';
            $button = '<a href="'.$url.'" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;'.__("admin.history").'</a>';
            $tools->append($button);
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
        $show = new Show(RequestTakeSalary::findOrFail($id));

//        $show->id('ID');
//        $show->user_id('user_id');
//        $show->agency_id('agency_id');
//        $show->status('status');
//        $show->change_status_admin_id('change_status_admin_id');
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
        $form = new Form(new RequestTakeSalary);
        $form->display('user.uuid',__ ('ID'));
        $form->display('user.name', __ ('name'));
        $form->display('amount', __ ('amount'));
        $form->display('phone', __ ('phone'));
        $form->display('gmail', __ ('gmail'));
        $form->display('country', __ ('country'));
        // $form->display('bank_num', __ ('bank_num'));
        // $form->display('other', __ ('other'));
        $form->select('status', __('status'))->options (
            [
                0=>__('pending'),
                1=>__ ('accepted'),
                2=>__ ('denied')
            ]
        )->attribute(['id'=>'status']);
        $form->text('reason_rejected', 'سبب الرفض')->attribute(['id'=>'additional_value']);

        $script = <<<SCRIPT
        $(document).ready(function() {
            function toggleWinProbability() {
                var status = $('#status').val();
                if(status == '2') {
                    $('#additional_value').closest('.form-group').show();
                } else {
                    $('#additional_value').closest('.form-group').hide();
                }
            }
            toggleWinProbability();

            $('#status').change(function() {
                toggleWinProbability();
            });
        });
        SCRIPT;
        Admin::script($script);


        // $form->display(trans('admin.created_at'));
        // $form->display(trans('admin.updated_at'));
        $form->saving(function (Form $form) {
            $user = User::query ()->where ('id',$form->model ()->user_id)->first ();
            if (!$user) {
                return back()->with(admin_error(__('admin.error'), __('User not found')));
            }
            if ($form->status == 2) {
                $amount = (float) $form->model()->amount;
                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount - $amount"),
                        'pending_dollar' => DB::raw("pending_dollar - $amount")
                    ]
                );
              if($user->email) Notification::route('mail',  $user->email)->notify(new RefuseRequestToGetSalary($amount,$form->reason_rejected));
              $reason= $form->reason_rejected;
              $value=$amount;
                CustomNotification::acceptRequestToGetMony($user,2,$reason,$value);

            }elseif ($form->status == 1) {
                $value = (float) $form->model()->amount;
                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'pending_dollar' => DB::raw("pending_dollar - $value")
                    ]
                );
                CustomNotification::acceptRequestToGetMony($user,1,'',$value);
                if($user->email) Notification::route('mail',  $user->email)->notify(new AcceptRequestToGetSalary($value));
            }
        });


        return $form;
    }
}
