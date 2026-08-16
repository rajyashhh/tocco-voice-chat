<?php

namespace Modules\UsersWallet\Services;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\User;
use Exception;

class CheckSystemConfigs
{
    /**
     * @throws Exception
     */
    public static function checkSystemConfigs(): void
    {
        if (Common::stopSwitch('stop_charge')) {
            throw new Exception(__('api_responses.freeze_charge_settings'));
        }
    }

    /**
     * @throws Exception
     */
    public static function checkUserTransferAvailability(User $sender, $receiver): void
    {
        if ($sender->transfer_salary == 1) {
            throw new Exception(__('api_responses.freeze_transfer_charger'));
        }

        if ($receiver->transfer_salary == 1) {
            throw new Exception(__('api_responses.freeze_transfer_receiver'));
        }
    }

    /**
     * @throws Exception
     */
    public static function getConfigRate()
    {
        $rate = Common::getCoinsValue('user_coins');

        if (!$rate){
            throw new Exception( __('please set usd_value_in_coins in configs. Contact the administration!'));
        }

        return $rate;
    }
}
