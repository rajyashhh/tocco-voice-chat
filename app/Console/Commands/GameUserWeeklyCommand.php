<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;


class GameUserWeeklyCommand extends Command
{
    protected $signature = 'game:user-calc-weekly';

    protected $description = 'Command description';

    public function handle()
    {
        $procedureName = '_SP_update_monthly_coin_game_users';

        $checkStoredProcedureExists = checkStoredProcedureExists($procedureName);


        if ($checkStoredProcedureExists) {

            $this->callProcedure();
        } else {
            $file = database_path('sql/game_users_weekly.sql');
            $query = file_get_contents($file);
            \DB::statement($query);

            $this->callProcedure();

        }

//        $this->info('Data refactor successfully on :' . now());
    }

    private function callProcedure()
    {
        \DB::statement('CALL _SP_update_monthly_coin_game_users()');
    }
}
