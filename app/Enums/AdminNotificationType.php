<?php

namespace App\Enums;

enum AdminNotificationType: string
{
    case NEW_USER = 'new_user';
    case NEW_ORDER = 'new_order';
    case PAYMENT = 'payment';
    case SYSTEM = 'system';
    case WARNING = 'warning';
}



