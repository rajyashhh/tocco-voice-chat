<?php
namespace App\Helpers;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheHelper{
    public static function put($key,$value){
        if (config ('app.cache') == 'enabled'){
            Cache::put ($key,$value);
        }
    }

    public static function get($key,$repo){
        if (config ('app.cache') == 'enabled'){
            if (Cache::has ($key)){
                $data = Cache::get ($key);
            }else{
                $data = $repo->all();
                Cache::put ($key,$data);
            }
        }else{
            $data = $repo->all();
        }
        return $data;
    }

    public static function forget($key){
        Cache::forget ($key);
    }

    public static function cacheSettings()
    {
        return Cache::rememberForever('all_settings', function () {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return collect();
            }
            return Setting::select(['value', 'key'])->get();
        });
    }

    public static function cacheConfig()
    {
        return cache()->rememberForever('all_configs', function () {
            if (!\Illuminate\Support\Facades\Schema::hasTable('configs')) {
                return [];
            }
            return DB::table('configs')->pluck('value', 'name')->toArray();
        });
    }
}
