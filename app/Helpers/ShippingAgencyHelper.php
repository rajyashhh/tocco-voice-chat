<?php

namespace App\Helpers;

use App\Models\ShippingAgency;

class ShippingAgencyHelper
{
    public static function isReliableTransferEnabled(): bool
    {
        return settings()->get('transfer_salary_reliable_shipping_agency') == 1;
    }


    public static function isVerifiedChargeForAgency(ShippingAgency $agency): bool
    {
        if (self::isReliableTransferEnabled()) {
            return $agency->chargeAgency()->exists();
        }

        return true;
    }
}
