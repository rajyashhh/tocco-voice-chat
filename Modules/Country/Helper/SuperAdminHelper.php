<?php

namespace Modules\Country\Helper;

use App\Models\HomeCarousel;
use App\Models\Setting;
use Modules\Country\Entities\SuperAdmin;
use Exception;
use App\Helpers\Common;

class SuperAdminHelper
{

    public static function addCoins($userID,  $coins_deducted)
    {
        $user = SuperAdmin::find($userID);

        if (!$user) {
            return false;
        }

        $user->di +=  $coins_deducted;
        $user->save();
    }

    public static function bannerDeductAmount(HomeCarousel $banner, $type = null)
    {


        $hourlyPrice = self::getHourlyBannerPrice($type);

        $form  = $banner->form;
        $input = $banner->input;
        switch ($form) {
            case 1:
                $hours = $input;
                break;
            case 2:
                $hours = $input * 24;
                break;
            case 3:
                $hours = $input * 24 * 30;
                break;
            default:
                $hours = 0;
        }

        return $hours * $hourlyPrice;
    }

    public static function getHourlyBannerPrice(?string $type = null): float
    {
        $defaultPrices = [
            'live' => 10,
            'home_middle' => 15,
            'home_top' => 20,
            'discover' => 12,
            'room' => 0,
        ];

        $settings = Setting::whereIn('key', array_keys($defaultPrices))
            ->pluck('value', 'key')
            ->toArray();

        $prices = array_merge($defaultPrices, $settings);

        if (empty($type)) {
            return (float) $defaultPrices['live'];
        }

        $cleanType = str_replace('display_', '', $type);

        return (float) ($prices[$cleanType] ?? $defaultPrices['live']);
    }


}

