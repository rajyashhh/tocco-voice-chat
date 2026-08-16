<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUserMonthlyDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-monthly-days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reset all users monthly_days every 30 days';

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
     * @return int
     */
    public function handle()
    {
        DB::statement("
            UPDATE users
            SET monthly_days = 0
        ");

    //    $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }
}
