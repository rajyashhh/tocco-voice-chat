<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WelcomeController extends Controller
{
    public static function appName()
    {
        $locale = app()->getLocale();

        if ($locale == 'ar') {
            return Cache::get('app_title_ar') ?? Setting::where('key', 'app_title_ar')->value('value') ?? 'Default';
        }

        return Cache::get('app_title_en') ?? Setting::where('key', 'app_title_en')->value('value') ?? 'Default';
    }
    
    public function index()
    {
        $appName = $this->appName();
        $logo = getAppLogo();
        $settings = Setting::pluck('value', 'key')->toArray();
        $firstChar = mb_substr($appName, 0, 1);

        return view('welcome', [
            'appName' => $appName,
            'logo' => $logo,
            'settings' => $settings,
            'firstChar' => $firstChar
        ]);
    }
}
