<?php

namespace App\Admin\Controllers;

use Encore\Admin\Layout\Content;


class AdminAuthController extends MainController
{
    public $permission_name = 'admin-profile';

    public function index(Content $content){
        return $content->title(__('user profile'))->body(view('admin_profile'));
    }

}
