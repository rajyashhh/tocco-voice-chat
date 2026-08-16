<?php

namespace App\Admin\Actions;

use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use Modules\Vip\Entities\UserVip;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Classes\Enums\SubTypeMessagesType;
use Modules\Public\Http\Services\UpgradeLevelServices;
use App\Models\Setting;
use App\Models\UserSallary;
use App\Models\Charge;
class DeliverdSalaryAction extends RowAction
{
    public $name;
    public function __construct($id = 0)
    {
        $this->name = __("dashboard.giveSalary");
        parent::__construct();
    }
    public function handle(Model $model, Request $request)
    {
        $user = User::query()->find($model->user_id );
        // UserSallary::create([
        //     'user_id'=>$user->id,
        //     'cut_amount'=>$model->amount,
        //     'month'=>date("m"),
        //     'year'=>date("Y"),
        // ]);
        UserSallary::createOrUpdate([
            "user_id" => $user->id,
            "month" => date("m"),
            "year" => date("Y"),
        ],[
            "cut_amount" => DB::raw("cut_amount + {$model->amount}"),
        ]);
        $userCoins = \Cache::rememberForever('user_coins', function () {
            $setting = Setting::where('key', 'user_coins')->first();
            return $setting?->value ?? 1;
        });
        $usdAmount = $userCoins > 0 ? $model->amount / $userCoins : 0;

        Charge::query()->create([
            'user_id' => $user->id,
            'amount' => $model->amount,
            'usd' => $usdAmount,
            'charger_id' => auth()->user()->id,
            'charger_type' => 'dash',
            'user_type' => $user->type_user,
            'amount_type' => 3,
        ]);
        
        return $this->response()->success (__('dashboard.successful'))->refresh ();
    }

    public function form()
    {
        // $this->integer('days', 'days');
    }
    
}
