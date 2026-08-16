<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateOfflineUsers extends Command
{
    protected $signature = 'users:update-offline';
    protected $description = '';

    public function handle()
    {
        $threshold = now()->subMinutes(15);

        $ids = DB::table('users')
            ->where('online', true)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $threshold);
            })
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($ids->isEmpty()) {
//            $this->info('No offline users found.');
            return;
        }

        foreach ($ids->chunk(100) as $chunk) {
            DB::table('users')
                ->whereIn('id', $chunk)
                ->update(['online' => false, 'updated_at' => now()]);
        }

//        $this->info("Done. Updated {$ids->count()} users.");
    }
}
