<?php
namespace App\Helpers;

use App\Enums\UserCoinLogType;
use App\Models\UserCoinLog;
use Carbon\Carbon;

class UserCoinLogHelper
{
    public static function logByType(
        int $userId,
        float $amount,
        float $amountBefore,
        UserCoinLogType $type,
        ?string $itemNameOverride = null,
        float $helperAmount = 0,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $userType = null
    ): void {
        $meta = $type->meta();

        $logData = [
            'user_id'       => $userId,
            'type'          => $type->value,
            'sub_type'      => $meta['sub_type'],
            'amount'        => $amount,
            'amount_before' => $amountBefore,
            'item_name'     => $itemNameOverride ?? $meta['item_name'],
            'helper_amount' => $helperAmount,
            'from_date'     => $fromDate ?? now(),
            'to_date'       => $toDate ?? now(),
            'user_type'     => $userType,
        ];

        if ($meta['queue_job']) {
            $jobClass = $meta['queue_job'];
            dispatch(new $jobClass(
                $userId,
                $amountBefore,
                $amount,
                $helperAmount,
                $type->value,
                $meta['sub_type'],
                $itemNameOverride ?? $meta['item_name'],
                $fromDate ?? now()
            ))->onQueue('log_user_coin');
        } else {
            UserCoinLog::create($logData);
        }
    }
}
