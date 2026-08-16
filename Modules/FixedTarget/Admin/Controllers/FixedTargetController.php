<?php

namespace Modules\FixedTarget\Admin\Controllers;

use App\Admin\Controllers\MainController;
use App\Models\Agency;
use Encore\Admin\Grid;
use Modules\FixedTarget\Entities\FixedTarget;

class FixedTargetController extends MainController
{
    public $permission_name = 'fixed-target';


    public function grid()
    {
        $grid = new Grid(new FixedTarget());

        return $grid;
    }

}
