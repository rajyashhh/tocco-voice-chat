<?php

namespace App\Services;

use App\Models\AppFeature;
use Illuminate\Support\Facades\Cache;
use Nette\Schema\ValidationException;

class AppFeatureService
{

    public static function isEnable($slug)
    {
        // Hermetic build: rememberForever opens a cache (Redis) connection to
        // read the key BEFORE the closure runs, so it throws during image build
        // (package:discover / view:cache — RouteServiceProvider calls this while
        // loading routes) when no Redis is up. Catch it and default to enabled;
        // runtime always has the stack and behaves exactly as before.
        try {
            return Cache::rememberForever("app_feature_status_{$slug}", function () use ($slug) {
                // Cold-boot safety: table is absent before migrations run.
                if (!\Illuminate\Support\Facades\Schema::hasTable('app_features')) {
                    return true;
                }
                $feature = AppFeature::where('slug', $slug)->first();
                if ($feature && $feature->status == 0) {
                    return false;
                }
                return true;
            });
        } catch (\Throwable $e) {
            return true;
        }
    }
    
    //UserTargetAchieveJob
    public function validateStatusEnable($slug)
    {
        $appFeature = AppFeature::where("slug",$slug)->first();
        if ($appFeature != null && $appFeature->status == 0) {
            abort(403, __('This feature has not been activated for you'));
        }
        return true;
    }
}
