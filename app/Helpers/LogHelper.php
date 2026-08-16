<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class LogHelper
{
    public static function info($message, $context = [])
    {
        $logMessage = $message;

        if (!empty($context)) {
            $logMessage .= PHP_EOL . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
}
