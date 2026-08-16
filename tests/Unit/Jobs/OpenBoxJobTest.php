<?php

namespace Tests\Unit\Jobs;

use App\Jobs\OpenBoxJob;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OpenBoxJobTest extends TestCase
{

    public function testUpdateBoxUse()
    {

        $vars = $this->updateDatabase();
        $this->assertTrue(true);
    }
    public function updateDatabase()
    {
        $keys = Redis::keys('*LuckyBox*');
        foreach ($keys as $key) {
            $cleanKey = str_replace(config('database.redis.options.prefix') , "", $key);
            $type = Redis::type($cleanKey)->getPayload();
            $value = null;
            if ($type == 'string') {
                $value = Redis::get($cleanKey);
                if ($value !== false || $value === 'b:0;') {
                    $unserializedValue = @unserialize($value);
                    $value = $unserializedValue;
                }
                $value['created_at'] =now()->toDateTimeString();
                $value['updated_at'] =now()->toDateTimeString();
                \DB::table('user_box_gifts')->insert($value);
                Redis::del($cleanKey);
            }
        }
        $this->assertTrue(true);
    }

    public function updateBoxUse()
    {
        $keys = Redis::keys('*BoxUse_*');

        foreach ($keys as $key) {
            $cleanKey = str_replace(config('database.redis.options.prefix') , "", $key);
            $type = Redis::type($cleanKey)->getPayload();
            $value = null;
            if ($type == 'string') {
                $value = Redis::get($cleanKey);
                if ($value !== false || $value === 'b:0;') {
                    $unserializedValue = @unserialize($value);
                    $value = $unserializedValue;
                }
                $value['updated_at'] =now();
                \DB::table('box_uses')->update($value);
            }
        }
        $this->assertTrue(true);
    }
}
