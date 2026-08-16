<?php

namespace Modules\UsersWallet\Services;

use App\Models\Agency;
use Exception;

class CheckAgencyExistence
{
    /**
     * @throws Exception
     */
    public static function agencyExists(Agency $agencyModel, $agencyId)
    {
        $user = $agencyModel::whereId($agencyId)->first();

        //Todo translate this
        if (! $user) throw new Exception(__('this agency not found'));

        return $user;
    }
}
