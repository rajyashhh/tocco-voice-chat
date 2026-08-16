<?php

namespace App\Traits\User;

use App\Models\User;
use Modules\Vip\Entities\Vip;
use Illuminate\Support\Facades\Log;

trait UserLevel
{

    public function senderLevel()
    {
        return $this->belongsTo(Vip::class, 'sender_level', 'level')
            ->where('type', 2);
    }

    public function receiverLevel()
    {
        return $this->belongsTo(Vip::class, 'received_level', 'level')
            ->where('type', 1);
    }

   

    public function totalSenderLevels()
    {
        return $this->belongsTo(Vip::class, 'total_sender_level', 'level')
            ->where('type', 2);
    }

    public function totalReceiverLevels()
    {
        return $this->belongsTo(Vip::class, 'total_received_level', 'level')
            ->where('type', 1);
    }

    public function chargeLevel()
    {
        return $this->belongsTo(Vip::class, 'charge_level', 'level')
            ->where('type', 5);
    }
    public function getNextSenderLevelInfoAttribute(): array
    {
        $currentLevel = $this->senderLevel;

        if (!$currentLevel) {
            return [
                'next_level' => null,
                'remaining_exp_ratio' => 0.0, // double
            ];
        }

        $nextLevel = Vip::where('type', 2)
            ->where('level', '>', $currentLevel->level)
            ->orderBy('level')
            ->first();

        if (!$nextLevel) {
            return [
                'next_level' => null,
                'remaining_exp_ratio' => 0.0,
            ];
        }

        $currentExp = $this->sender_exp ?? 0;
        $levelStartExp = $currentLevel->exp ?? 0;
        $levelEndExp = $nextLevel->exp ?? 0;

        $totalExpDiff = max($levelEndExp - $levelStartExp, 1);
        $remainingExp = max($levelEndExp - $currentExp, 0);

        $remainingRatio = $remainingExp / $totalExpDiff;

        return [
            'next_level' => $nextLevel->level,
            'remaining_exp_ratio' => round($remainingRatio, 2), 
        ];
    }





}
