<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('game_provider_settings')
            ->whereIn('provider_code', ['utd_games', 'zero_games'])
            ->delete();
    }

    public function down(): void
    {
        // No-op: utd_games and zero_games are deprecated providers and are not restored.
    }
};
