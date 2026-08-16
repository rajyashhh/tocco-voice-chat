<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;

/**
 * Retired (owner 2026-08-14): the settings switches this page hosted
 * (live/reels/youtube/audio rooms/…) moved onto the unified App Features
 * cards page. The route stays as a redirect so old bookmarks and any stale
 * menu rows keep working.
 */
class FeatureAppController extends MainController
{

    public $permission_name = 'app-feature';
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App Feature';


    public function index(Content $content)
    {
        return redirect(admin_url('app-features'));
    }
}
