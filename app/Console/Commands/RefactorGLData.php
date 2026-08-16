<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;


class RefactorGLData extends Command
{
    protected $signature = 'gl:refactor';

    protected $description = 'Command description';

    public function handle()
    {

        $procedureName = 'refactor_gl_data';

        $checkStoredProcedureExists = checkStoredProcedureExists($procedureName);


        if ($checkStoredProcedureExists) {

            $this->callProcedure();
        } else {
            $file = database_path('sql/refactor_gl_data.sql');
            $query = file_get_contents($file);
            \DB::statement($query);

            $this->callProcedure();

        }

       $this->info('Data refactor successfully on :' . now());
    }

    private function callProcedure()
    {
        \DB::statement('CALL refactor_gl_data()');
    }
}
