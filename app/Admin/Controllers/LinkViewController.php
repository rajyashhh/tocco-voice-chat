<?php

namespace App\Admin\Controllers;

use App\Models\Like;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Language;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;
use App\Models\Country;
use Encore\Admin\Controllers\HasResourceActions;

class LinkViewController extends MainController
{

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('helper links'))
            ->body(view('admin.link')));
    }


}
