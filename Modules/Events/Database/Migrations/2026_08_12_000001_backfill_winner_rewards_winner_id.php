<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('winner_rewards as wr')
            ->join('rewards as r', 'r.id', '=', 'wr.reward_id')
            ->join('winners as w', function ($join) {
                $join->on('w.weekly_star_id', '=', 'r.weekly_star_id')
                    ->on('w.user_id', '=', 'wr.winner_id');
            })
            ->update(['wr.winner_id' => DB::raw('w.id')]);
    }

    public function down(): void
    {
        DB::table('winner_rewards as wr')
            ->join('winners as w', 'w.id', '=', 'wr.winner_id')
            ->update(['wr.winner_id' => DB::raw('w.user_id')]);
    }
};