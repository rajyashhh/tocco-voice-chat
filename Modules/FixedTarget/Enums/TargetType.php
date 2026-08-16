<?php

namespace Modules\FixedTarget\Enums;

enum TargetType : string
{
    case REGULAR = 'regular';
    case FIXED = 'fixed';
    case APP = 'app';
}
