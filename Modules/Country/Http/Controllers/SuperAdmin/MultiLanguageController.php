<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use KevinSoft\MultiLanguage\MultiLanguage;

class MultiLanguageController extends Controller
{

    public function locale()
    {
        $locale = Request::input('locale');
        $languages = MultiLanguage::config('languages');

        $cookie_name = MultiLanguage::config('cookie-name', 'locale');
        if (array_key_exists($locale, $languages)) {

            return response('ok')->cookie($cookie_name, $locale);
        }
    }

    public function getLogin()
    {
        $test = request()->query('redirect_url');
        $languages = MultiLanguage::config("languages");
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        $current = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }
        return view("login", compact('languages', 'current', 'test'));
    }
}
