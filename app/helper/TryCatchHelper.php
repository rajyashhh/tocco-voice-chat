<?php

namespace App\helper;

use Exception;
use App\Helpers\Common;

class TryCatchHelper
{
    public static function handle(callable $callback, int $successCode = 200, int $errorCode = 407)
    {
        try {
            $result = $callback();
            return Common::apiResponse(true, '', $result, $successCode);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, $errorCode);
        }
    }
}
