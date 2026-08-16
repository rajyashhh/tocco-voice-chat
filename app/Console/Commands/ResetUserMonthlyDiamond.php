<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Traits\Salaries\UserSalaryTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetUserMonthlyDiamond extends Command
{
    use UserSalaryTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-monthly-diamond';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reset monthly diamond after 30 day';

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
        try {
            $timezone = getTimezone();
            $dt       = Carbon::now($timezone);
            if ($dt->format('j') == 1){
                $carbon = $dt->subDay();

                $this->calculateUserSalary(month: $carbon->month, year: $carbon->year);
            }else{

                $this->calculateUserSalary();
            }
         
            $this->info('users:ResetUserMonthlyDiamond Command Run Successfully !');


        }catch (\Exception $exception){
            $this->error('reset monthly diamond failed: '.$exception->getMessage());
        }
      }
}
