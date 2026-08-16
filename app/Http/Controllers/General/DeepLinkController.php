<?php

namespace App\Http\Controllers\General;

use App\Helpers\Common;
use App\Http\Controllers\Controller;

class DeepLinkController extends Controller
{
    public function index(?string $target = null)
    {
        return view('general.deeplink', [
            'androidLink' => Common::getSettingValue('android_link'),
            //'https://play.google.com/store/apps/details?id=com.yourapp',
            'iosLink' => Common::getSettingValue('ios_link'),
            //  'https://apps.apple.com/app/id1234567890',
            'huaweiLink' => Common::getSettingValue('huawei_link'),
            'appName' => Common::getSettingValue('app_title_en'),
            // 'https://appgallery.huawei.com/#/app/C123456',
        ]);
    }
}
