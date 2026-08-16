<?php

namespace App\Admin\Controllers;

use App\Models\Target;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;

class  AgencySettingController extends MainController
{

   // public $permission_name = 'agency-setting';
    public $permission_name = 'agency-settings';
    public function index(Content $content)
    {
        $tab = new Tab();

        $targets = Target::orderBy('diamonds')->get();



        $tab->add(__("targets"), view('admin.targets.targets', ["targets" => $targets]));

        return parent::index($content
            ->header(__('Agency settings'))
            ->description('')
            ->body($tab));
    }
}
