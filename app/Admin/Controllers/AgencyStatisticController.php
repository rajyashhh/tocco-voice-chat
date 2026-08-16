<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Country;
use App\Models\User;
use App\Models\AgencySallary;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Encore\Admin\Facades\Admin as LaravelAdmin;
use function Doctrine\Common\Cache\Psr6\get;
use Encore\Admin\Widgets\InfoBox;

class  AgencyStatisticController extends MainController
{
    public function index ( Content $content )
    {
        $agencies = DB::table('agencies')->count() ?? 0;
        $month = date('m');
        $year = date('Y');

        $activeAgencyCount = Agency::whereHas('agencySalaries', function ($q) use ($month, $year) {
                $q->where('month', $month)
                    ->where('year', $year);
            })
            ->count();
        $agencySalaries = AgencySallary::where('month', $month)->where('year', $year)->sum (\DB::raw('sallary - cut_amount'));
        $users = User::where("is_host",1)->whereNotNull("agency_id")->count();
        return $content
            ->title(trans('statistics').' '.trans('agencies'))
            ->description(__(request('desc') ?: 'الرئيسيه'))
            ->row(function (\Encore\Admin\Layout\Row $row) use ( $users, $agencies,$activeAgencyCount,$agencySalaries) {
                $row->column(12, '
                <div style="text-align: right;margin-bottom: 15px;">
                    <a href="' . url('admin/agency-settings') . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                        اعدادات الوكالات
                        
                    </a>
                </div>
            ');
                $row->column(3, new InfoBox(__('Number of agencies'), 'building', 'green', url('admin/agencies'), number_format(@$agencies ?? 0)));
                $row->column(3, new InfoBox(__('Active Agencies'), 'bell', 'green', url('admin/agencies?active=true'), number_format(@$activeAgencyCount ?? 0)));
                $row->column(3, new InfoBox(__('Agencies Salaries'), 'dollar', 'green', url('admin/agencies'), number_format(@$agencySalaries ?? 0)));
                $row->column(3, new InfoBox(__('Host Users'), 'user', 'green', route('admin.users'), number_format(@$users ?? 0)));
            });
    }
}
