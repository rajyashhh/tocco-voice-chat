<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use Encore\Admin\Controllers\HandleController as BaseHandleController;
use Illuminate\Http\Request;

class HandleController extends BaseHandleController
{
    public function handleAction(Request $request)
    {
        return parent::handleAction($request);
    }
}
