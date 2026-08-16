<?php

namespace Modules\Reals\Http\Controllers\web;

use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\AdminController;

class ReelSettingsController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Reel Settings';
    public $permission_name = 'reel-settings';

    public function index(Content $content)
    {
        return parent::index($content
        ->title(__('reel settings'))
        ->view('reel_settings'));
    }
}
