<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    public function status()
    {

        if($this->checkRedis() && $this->checkSql()){
            return response()->json([
                'sql'    => $this->checkSql(),
                'redis'  => $this->checkRedis(),
                // 'rutor'  => $this->checkRutorrent()
                'rutor'  => true
            ]);
        }

        return response()->json([
            'sql'    => $this->checkSql(),
            'redis'  => $this->checkRedis(),
            // 'rutor'  => $this->checkRutorrent()
            'rutor'  => true
        ],404);
    }

    private function checkSql(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRutorrent(): bool
    {
        try {
            // Example: check if rutorrent web UI is reachable
            $response = Http::timeout(3)->get(env('APP_URL') .'/images/app-logo.png');

            return $response->ok(); // true if HTTP 200
        } catch (\Exception $e) {
            return false;
        }
    }
}
