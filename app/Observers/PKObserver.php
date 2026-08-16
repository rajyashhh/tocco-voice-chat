<?php

namespace App\Observers;

use App\Models\PK;

class PKObserver
{
    public function creating(PK $pK)
    {
        $mics = $pK->mics;
        $m = is_string($mics) ? explode(',', $mics) : $mics;
//        $m = explode(',', $mics);
        $mic_1 = isset($m[1]) ? $m[1] : 0;
        $mic_2 = isset($m[2]) ? $m[2] : 0;
        $mic_3 = isset($m[3]) ? $m[3] : 0;
        $mic_4 = isset($m[4]) ? $m[4] : 0;
        $mic_5 = isset($m[5]) ? $m[5] : 0;
        $mic_6 = isset($m[6]) ? $m[6] : 0;
        $mic_7 = isset($m[7]) ? $m[7] : 0;
        $mic_8 = isset($m[8]) ? $m[8] : 0;
        $team_1 = [$mic_1, $mic_2, $mic_5, $mic_6];
        $team_2 = [$mic_3, $mic_4, $mic_7, $mic_8];
        $t1 = implode(',', $team_1);
        $t2 = implode(',', $team_2);
        $pK->team_1 = $t1;
        $pK->team_2 = $t2;
    }

    public function updating(PK $pK)
    {
        $mics = $pK->mics;
        $m = is_string($mics) ? explode(',', $mics) : $mics;
//        $m = explode(',', $mics);
        $mic_1 = isset($m[1]) ? $m[1] : 0;
        $mic_2 = isset($m[2]) ? $m[2] : 0;
        $mic_3 = isset($m[3]) ? $m[3] : 0;
        $mic_4 = isset($m[4]) ? $m[4] : 0;
        $mic_5 = isset($m[5]) ? $m[5] : 0;
        $mic_6 = isset($m[6]) ? $m[6] : 0;
        $mic_7 = isset($m[7]) ? $m[7] : 0;
        $mic_8 = isset($m[8]) ? $m[8] : 0;
        $team_1 = [$mic_1, $mic_2, $mic_5, $mic_6];
        $team_2 = [$mic_3, $mic_4, $mic_7, $mic_8];
        $t1 = implode(',', $team_1);
        $t2 = implode(',', $team_2);
        $pK->team_1 = $t1;
        $pK->team_2 = $t2;
    }
}
