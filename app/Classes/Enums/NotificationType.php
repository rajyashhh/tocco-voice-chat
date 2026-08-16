<?php

namespace App\Classes\Enums;

enum NotificationType : int
{

    case SENDER_LEVEL = 0;
    case RECEIVED_LEVEL = 1;
    case TARGET = 2;
    case FAMILY = 3;

}
