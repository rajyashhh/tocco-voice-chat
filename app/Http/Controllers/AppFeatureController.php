<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Setting;

class AppFeatureController extends Controller
{
    public function show()
    {
        $hostAgencySetting = Setting::where('key', 'host_agency')->first();
        $data = [
            'host_agency' => (bool)($hostAgencySetting?->value ?? true),
        ];

        return Common::apiResponse(true, '', $data, 200);

    }
}
