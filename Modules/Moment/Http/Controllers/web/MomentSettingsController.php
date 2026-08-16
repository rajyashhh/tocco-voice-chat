<?php

namespace Modules\Moment\Http\Controllers\web;


use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;


class MomentSettingsController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Moment Settings';
    public $permission_name = 'moment-settings';

    public function index(Content $content)
    {
        return parent::index($content
        ->title(__('Moment settings'))
        ->view('moment_settings'));
    }

}
