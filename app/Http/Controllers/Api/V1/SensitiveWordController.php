<?php

namespace App\Http\Controllers\Api\V1;


use App\Helpers\Common;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SensitiveWordController extends Controller {

public function index(Request $request)
    {
        $lang = $request->header('X-localization', 'ar');

        $words = \App\Models\SensitiveWord::query()
            ->where('is_active', 1)
            ->pluck('word')
            ->map(function ($word) use ($lang) {
                $decoded = is_array($word) ? $word : json_decode($word, true);
                return $decoded[$lang] ?? ($decoded['ar'] ?? null);
            })
            ->filter()
            ->values()
            ->toArray();

        return Common::apiResponse(1, '', $words);
    }
}
