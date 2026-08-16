<?php

namespace Modules\Payment\Enums;

enum PaymentStatus : string
{
    const INITIAL = 'initial';
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAIL = 'fail';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
