<?php

namespace App\Enums\Charges;

enum UserTypeEnum: string
{
    const AGENCY = 'agency';
    const USER = 'user';
    const SUPER_ADMIN = 'country';
    const AREA_MANAGER = 'region';
    const SUB_ADMIN = 'sub_country';
    const SUB_AREA_MANAGER = 'sub_region';
    const ADMIN = 'admin';
    const SHIPPING_SUPER_ADMIN = 'shipping_super_admin';
}
