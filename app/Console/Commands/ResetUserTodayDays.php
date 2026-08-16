<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUserTodayDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-today-days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user today_days every 24 hours';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement("
            UPDATE users
            SET monthly_days = monthly_days + 1, total_days = total_days + 1
            WHERE today_days =1;
        ");

        DB::statement("
            UPDATE users
            SET today_days = 0
            WHERE today_days != 0;
        ");

       $this->info('users:reset-today-days Command Run Successfully !');
    }
}
