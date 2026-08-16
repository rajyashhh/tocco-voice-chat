<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisService
{

    public function get($key)
    {
        $data = Redis::get($key);
        return $data;

    }

    public function update($key, mixed $value)
    {
        Redis::set($key, ($value));
    }

    public function increment($key): void
    {

        $data = intval($this->get($key) ?? 0);
        Redis::set($key, (++$data));
    }

    public function decrement($key)
    {

        $data = intval($this->get($key) ?? 0);
        Redis::set($key, (--$data));
    }

    public function getUnSerialize($key)
    {
        $data = Redis::get($key);

        return $data ? unserialize($data, ['allowed_classes' => false]) : null;
    }


    public function updateUnSerialize($key, mixed $value)
    {
        Redis::set($key, serialize($value));
    }


}
