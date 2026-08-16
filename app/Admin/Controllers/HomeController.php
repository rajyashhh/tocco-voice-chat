<?php

namespace App\Admin\Controllers;

use App\Admin\Customization\Dashboard\CustomDashboard;
use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\Ware;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(CustomDashboard::title())
            ->row(function (Row $row) {
                $row->column(3, function (Column $column) {
                    $labels = ["- 20", "20:30", "30:40", "40:50", "50:60", "+ 60"];
                    $numbers = [12, 19, 3, 5, 2, 3];
                    $data = [
                        'labels'=>$labels,
                        'numbers'=>$numbers
                    ];
                    $column->append(new Box('user age', view('admin.components.users-age-chart',['data'=>$data])));
                });
                $row->column(3, function (Column $column) {
                    $labels = ["male", "female"];
                    $numbers = [12, 19];
                    $data = [
                        'labels'=>$labels,
                        'numbers'=>$numbers
                    ];
                    $column->append(new Box('user gender', view('admin.components.users-gender-chart',['data'=>$data])));
                });
            });
    }

    public function devIndex(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->description('Description...')
            ->row(Dashboard::title())
            ->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }

    public function infoBox(Content $content)
    {
        $roles = Auth::user ()->roles()->pluck('slug')->toArray();
        if (Auth::check () && !in_array ('admin',$roles) && request ()->is ('admin')) {
            if (in_array ('agency',$roles)){
                return redirect()->to(config('admin.route.prefix').'/ag');
            }
            elseif (in_array ('charger',$roles)){
                return redirect()->to(config('admin.route.prefix').'/ch');
            }elseif (Auth::check () && in_array ('admin',$roles) && request ()->is ('admin')) {

                $content->title('Info box');
                $content->description('Description...');

                $content->row(function ($row) {
                    $row->column(3, new InfoBox(__('Users'), 'users', 'aqua', route (config('admin.route.prefix').'.users'), User::query ()->count ()));
                    $row->column(3, new InfoBox(__('Rooms'), 'wechat', 'green', route (config('admin.route.prefix').'.rooms'), Room::query ()->count ()));
                    $row->column(3, new InfoBox(__('Gifts'), 'gift', 'yellow', route (config('admin.route.prefix').'.gifts'), Gift::query ()->count ()));
                    $row->column(3, new InfoBox(__('Store'), 'shopping-cart', 'red', route (config('admin.route.prefix').'.wares'), Ware::query ()->count ()));
                });
            }
        }
        return $content;
    }

}
