<?php

namespace Modules\Achievement\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Modules\Achievement\Entities\UserAchievementLevel;

class ResetUserAchievmentMonthly extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'achivment:user-monthly';

    /**
     * The console command description.
     */
    protected $description = 'Update user_achivment  enable = 0  every month';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        UserAchievementLevel::where("user_id",1260)->where("end_at","<=",date("Y-m-d"))->update([
            'is_enable'=>0
        ]);
    }

}
