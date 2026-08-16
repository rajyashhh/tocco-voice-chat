<?php

namespace Modules\CP\Enums;

enum CpStatus: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case ACCEPTED = 2;
    case STOPED = 3;
    case RESTORED = 4;
    case PENDING_RESTORED = 5;
}
