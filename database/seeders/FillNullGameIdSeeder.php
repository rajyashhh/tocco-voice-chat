<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FillNullGameIdSeeder extends Seeder
{
    public function run()
    {
        $startDate = '2025-09-22';
        $gameName = 'null'; 

        $game = DB::table('games')->where('name', $gameName)->first();

        if (!$game) {
            $gameId = DB::table('games')->insertGetId([
                'name'       => $gameName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Game '$gameName' created with ID: $gameId");
        } else {
            $gameId = $game->id;
            $this->command->info("Game '$gameName' already exists with ID: $gameId");
        }

        $updatedMain = DB::table('coin_game_users')
            ->whereNull('game_id')
            ->whereDate('created_at', '>=', $startDate)
            ->update(['game_id' => $gameId]);
        $this->command->info("Updated $updatedMain rows in coin_game_users.");

        $updatedArchive = DB::table('coin_game_users_archive')
            ->whereNull('game_id')
            ->whereDate('created_at', '>=', $startDate)
            ->update(['game_id' => $gameId]);
        $this->command->info("Updated $updatedArchive rows in coin_game_users_archive.");
    }
}
