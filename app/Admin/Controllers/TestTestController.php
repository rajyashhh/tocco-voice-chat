<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Country;
use App\Models\Target;
use App\Models\User;
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
use function Doctrine\Common\Cache\Psr6\get;

class TestTestController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    // public function index(Content $content)
    // {
    //     $targets = Target::orderBy('diamonds')->get();

    //     // تمرير البيانات إلى الـ Blade view
    //     return view('admin.targets.test', compact('targets'));
    // }

    public function index(Content $content)
    {
        $targets = Target::orderBy('diamonds')->get();

        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body(view('admin.targets.test', ['targets' => $targets])->render());
    }
 
    // protected function grid()
    // {
    //     $grid = new Grid(new Target());
    
    //     $grid->disableTable();
    
    //     $grid->column('custom_card', __('Card'))->display(function () {
    //         return '
    //             <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-around;">
    //                 <div class="card" style="width: 300px; border: 1px solid #ddd; background-color: #28a745; color: white; padding: 20px; margin-bottom: 20px;">
    //                     <div class="card-body text-center">
    //                         <h2 class="card-title" style="font-size: 48px;">' . $this->your_data_value . '</h2>
    //                         <p class="card-text" style="font-size: 18px;">' . __('Your Label Here') . '</p>
    //                     </div>
    //                     <div class="card-footer">
    //                         <a href="#" class="btn btn-outline-light">' . __('More') . '</a>
    //                     </div>
    //                 </div>
    //                 <div class="card" style="width: 300px; border: 1px solid #ddd; background-color: #28a745; color: white; padding: 20px; margin-bottom: 20px;">
    //                     <div class="card-body text-center">
    //                         <h2 class="card-title" style="font-size: 48px;">' . $this->your_data_value . '</h2>
    //                         <p class="card-text" style="font-size: 18px;">' . __('Your Label Here') . '</p>
    //                     </div>
    //                     <div class="card-footer">
    //                         <a href="#" class="btn btn-outline-light">' . __('More') . '</a>
    //                     </div>
    //                 </div>
    //             </div>';
    //     });
    
    //     $grid->disableExport();
    //     $grid->disableFilter();
    //     $grid->disableCreateButton();
    
    //     return $grid;
    // }

    protected function form()
    {
        $form = new Form(new Target);


        $form->display(__ ('ID'));
        $form->number('level', __('target no'));
        $form->number('diamonds', __('diamonds'));
        $form->decimal('usd', __('usd'));
//        $form->text('coin', 'coin');
//        $form->text('gold', 'gold');
//        $form->text('minuts', 'minuts');
        $form->number('hours', __('hours'));
        $form->number('days', __('days'));
//        $form->text('img', 'img');
        $form->decimal('agency_share', __('agency share').'(%)');
        $form->html('',('<h1>Reel</h1>'));
        $form->hidden('reel', 'reel');
        $form->number('reel1', __('uploadReel'))->default(function ($form) {
            $reel = $form->model()->reel;
            $str    = @explode(',', $reel)[0];
            return $str == null || $str == '' ? 0: $str;
        });
        $form->number('reel2', __('LikeReel'))->default(function ($form) {
            $reel = $form->model()->reel;

            return @explode(',', $reel )[1] ?? 0;
        });;
        $form->number('reel3', __('commentReel'))->default(function ($form) {
            $reel = $form->model()->reel;

            return @explode(',', $reel )[2] ?? 0;
        });
        $form->html('',('<h1>Moment</h1>'));
        $form->hidden('moment', 'moment');

        $form->number('moment1', __('uploadMoment'))->default(function ($form) {
            $moment = $form->model()->moment;
            $str    = @explode(',', $moment)[0];
            return $str == null || $str == '' ? 0: $str;
        });
        $form->number('moment2', __('likeMoment'))->default(function ($form) {
            $moment = $form->model()->moment;

            return @explode(',', $moment )[1] ?? 0;
        });
        $form->number('moment3', __('commentMoment'))->default(function ($form) {
            $moment = $form->model()->moment;

            return @explode(',', $moment )[2] ?? 0;
        });


        return $form;

    }
    
    
    
    
    
}
