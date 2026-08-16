<?php

namespace Modules\LuckyBox\Traits;

trait RoomBoxes {

    public function scopeWithLuckyBoxFlag($query, $userId)
    {
        $timestamp = now()->timestamp;

        $query->withExists([
            'boxUse as is_lucky_box' => function ($q) use ($userId, $timestamp) {
                $q->select('id')
                    ->where('not_used_num', '>', 0)
                    ->where('end_at', '>=', $timestamp)
                    ->whereDoesntHave('picks', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->limit(1); // ✅ Only existence is needed
            }
        ]);
    }
}
