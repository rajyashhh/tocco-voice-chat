<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Charge;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOnlineHistory;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use App\Helpers\UserCommon;
use App\Models\Gift;
use App\Models\Room;
use App\Models\Ware;
use Carbon\Carbon;
use DB;
use Encore\Admin\Widgets\InfoBox;
use Modules\AgencyApp\Entities\AgencyUserJob;

class UserOnlineHistoryController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'onlineHistory';
    public $hiddenColumns = [

    ];

    public function index(Content $content)
    {
        $query = "
            SELECT time, active_users FROM (
                SELECT time,
                    SUM(active) OVER (ORDER BY time ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS active_users
                FROM (
                    SELECT user_id, start_time AS time, 1 AS active
                    FROM user_online_histories
                    WHERE status = 1
                    GROUP BY user_id, start_time

                    UNION ALL

                    SELECT user_id, end_time AS time, -1 AS active
                    FROM user_online_histories
                    WHERE status = 1
                    GROUP BY user_id, end_time
                ) AS events
            ) AS cumulative_active_users
            ORDER BY active_users DESC, time ASC
            LIMIT 1;
        ";

        $result = DB::select($query);
        $maxActiveUsers = $result[0]->active_users;
        $maxActiveTime = $result[0]->time;
        $daily_active_user = DB::table('user_online_histories')
        ->join('users', 'user_online_histories.user_id', '=', 'users.id')
        ->whereDate('user_online_histories.start_time', date("Y-m-d"))
        ->select('users.device_token')
        ->distinct()
        ->get()
        ->unique('device_token')
        ->count();
         // Fetch weekly active users
        $endDate = Carbon::now()->endOfWeek();
        $startDate = Carbon::now()->startOfWeek();
        $online_users = User::where('online',1)->count();
            $weekly_active_users = DB::table('user_online_histories')
            ->join('users', 'user_online_histories.user_id', '=', 'users.id')
            ->whereBetween('user_online_histories.start_time', [$startDate, $endDate])
            ->select('users.device_token')
            ->distinct()
            ->get()
            ->unique('device_token')
            ->count();

        // Fetch monthly active users
        $monthly_active_users = DB::table('user_online_histories')
            ->join('users', 'user_online_histories.user_id', '=', 'users.id')
            ->whereMonth('user_online_histories.start_time', date("m"))
            ->select('users.device_token')
            ->distinct()
            ->get()
            ->unique('device_token')
            ->count();
        return $content
            ->title('Dashboard')
            ->description('Description')
            ->row(function ($row) use ($maxActiveUsers,$maxActiveTime,$daily_active_user,$weekly_active_users,$monthly_active_users,$online_users) {
                $row->column(2, new InfoBox($maxActiveTime, 'users', 'aqua', route(config('admin.route.prefix').'.users'), $maxActiveUsers));
                $row->column(2, new InfoBox(__('online users'), 'users', 'blue', route(config('admin.route.prefix').'.users'), $online_users));
                $row->column(2, new InfoBox("DAU", 'wechat', 'green', route(config('admin.route.prefix').'.rooms'), $daily_active_user));
                $row->column(2, new InfoBox("WAU", 'gift', 'yellow', route(config('admin.route.prefix').'.gifts'), $weekly_active_users));
                $row->column(2, new InfoBox("MAU", 'shopping-cart', 'red', route(config('admin.route.prefix').'.wares'), $monthly_active_users));
            })
            
            ->row($this->grid());
    }
    protected function grid()
    {
        $grid = new Grid(new UserOnlineHistory());
        $grid->model ()->with("user")->orderByDesc ('id');
        $grid->filter(function($filter){

            $filter->expand ();
            $filter->where(function ($query) {
                $date= UserCommon::convertArabicToEnglishNumbers($this->input);
                $query->where('start_time', '<=',$date)->where('end_time', '>=',$date);
            }, __('date'))->dateTime();
        });
        $grid->column('user.id',__('Id'));
        $grid->column('user.name',__('name'));
        $grid->column('user.uuid',__('user id'));
        $grid->column('start_time',__('start time'));
        $grid->column('end_time',__('end time'));
        $grid->actions (function ($actions){
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

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
