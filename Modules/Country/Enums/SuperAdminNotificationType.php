<?php

namespace Modules\Country\Enums;

enum SuperAdminNotificationType: string
{
    case NEW_USER = 'new_user';
    case NEW_ORDER = 'new_order';
    case REGECTED_BANNER_ORDER = 'rejected_banner_order';
    
    case PAYMENT = 'payment';
    case SYSTEM = 'system';
    case WARNING = 'warning';
}



