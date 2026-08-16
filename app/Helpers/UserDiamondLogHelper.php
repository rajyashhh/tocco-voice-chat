<?php

namespace App\Helpers;

use App\Enums\UserDiamondLogType;
use App\Models\UserDiamondLog;
use DB;


class UserDiamondLogHelper
{
    public static function logByType(
        int $userId,
        float $amount,
        float $diamondBefore,
        UserDiamondLogType $type,
        ?int $getById = null,
        ?float $coin = null,

    ): void {
        $meta = $type->meta();

        $logData = [
            'user_id'       => $userId,
            'amount'        => $amount,
            'diamond_before' => $diamondBefore,
            'type'          => $type->value,
            'sub_type'      => $meta['sub_type'],
            'item_name'     =>  $meta['item_name'],
            'get_by_id' => $getById,
            'coin'     => $coin,
        ];

        UserDiamondLog::create($logData);
    }

    public static function bulkLogByType($receivedUsers, $featureType, $totalPrice, $senderUserId, $coin = null): void {
        $logsData = $receivedUsers->map(function ($receivedUser) use ($featureType, $totalPrice, $senderUserId, $coin) {
            $meta = $featureType->meta();

            return [
                'user_id'        => $receivedUser->id,
                'amount'         => $totalPrice,
                'diamond_before' => $receivedUser->currentMonthlyDiamond?->monthly_diamond_received ?? 0,
                'type'           => $featureType->value,
                'sub_type'       => $meta['sub_type'],
                'item_name'      => $meta['item_name'],
                'get_by_id'      => $senderUserId,
                'coin'           => $coin,
            ];
        })->toArray();

        DB::table('user_diamond_logs')->insert($logsData);
    }
}
