<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BadgeController extends Controller
{
    public function index(){

        $code = request('code','en');

        // PERFORMANCE FIX: Cache badges for 30 minutes
        // Issue: 4.1-4.5s for only 150 bytes response (DevOps Report 2026-05-05)
        // Root cause: 3 separate DB queries + Config table scan
        // Solution: Cache + single query with whereIn
        return Cache::remember("badges_{$code}", 1800, function () use ($code) {
            // Single query instead of 3 separate queries
            $badges = Config::whereIn('name', [
                $code . '_host',
                $code . '_shipping',
                $code . '_agency_owner'
            ])->get()->keyBy('name');

            return [
                'host' => $badges->get($code . '_host'),
                'shipping' => $badges->get($code . '_shipping'),
                'agency_owner' => $badges->get($code . '_agency_owner')
            ];
        });
    }
}
