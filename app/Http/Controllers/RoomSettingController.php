<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Config;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class RoomSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $paidRoom = Config::where('name', 'paid_room')->first();
        $paidRoomAmount = Config::where('name', 'paid_room_amount')->first();
        $data = [
            'paid_room' => (bool)$paidRoom?->value ?? false,
            'paid_room_amount' => $paidRoomAmount?->value ?? 0,
        ];

        return Common::apiResponse(true, '', $data, 200);
    }
}
