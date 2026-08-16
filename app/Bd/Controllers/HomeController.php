<?php

namespace App\Bd\Controllers;

use App\Admin\Customization\Dashboard\CustomDashboard;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\UserTarget;
use App\Models\Ware;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\Auth;
use Log;

class HomeController extends Controller
{

    public function index(Content $content)
    {
        $appID = Auth::user()->id;
        $agencyCount = Agency::where('bd_id', $appID)->count();

        $salaryData = \App\Models\BdSalary::where('bd_id', $appID)
            ->selectRaw('COALESCE(SUM(salary),0) AS total_sallary, COALESCE(SUM(cut_amount),0) AS total_cut')
            ->first();
        $finalSalary = truncateAndTrim($salaryData->total_sallary - $salaryData->total_cut, 2);

        $finalWallet = '';
        return $content
            ->title(__('Home'))
            ->description('إحصائيات عامة')

            ->row(function (Row $row) use ($agencyCount, $finalSalary, $finalWallet) {
                $row->column(6, new InfoBox(__('Agencies Count'), 'users', 'aqua', 'bd/agencies', $agencyCount));
                $row->column(6, new InfoBox(__('BD Wallet'), 'money', 'green', 'bd/salaries', $finalSalary . ' 💰'));
            });
    }



}
