<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;


class GameUserCommand extends Command
{
    protected $signature = 'game:user-calc';

    protected $description = 'Command description';

    public function handle()
    {
        $procedureName = '_SP_update_coin_game_users_tbl';

        $checkStoredProcedureExists = checkStoredProcedureExists($procedureName);


        if ($checkStoredProcedureExists) {

            $this->callProcedure();
        } else {
            $file = database_path('sql/game_users.sql');
            $query = file_get_contents($file);
            \DB::statement($query);

            $this->callProcedure();

        }

//        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }

    private function callProcedure()
    {
        \DB::statement('CALL _SP_update_coin_game_users_tbl()');
    }
}
