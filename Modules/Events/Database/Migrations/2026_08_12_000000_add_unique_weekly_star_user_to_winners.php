<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('winners')
            ->select('weekly_star_id', 'user_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('weekly_star_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($dup) {
                DB::table('winners')
                    ->where('weekly_star_id', $dup->weekly_star_id)
                    ->where('user_id', $dup->user_id)
                    ->where('id', '<>', $dup->keep_id)
                    ->delete();
            });

        Schema::table('winners', function (Blueprint $table) {
            $table->unique(['weekly_star_id', 'user_id'], 'winners_weekly_star_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('winners', function (Blueprint $table) {
            $table->dropUnique('winners_weekly_star_user_unique');
        });
    }
};
