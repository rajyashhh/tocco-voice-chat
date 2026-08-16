<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Traits\Salaries\UserSalaryTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Services\FixedTargetService;

class UpdateUserSalaries extends Command
{
    use UserSalaryTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-salaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add user achievements after 30 day';

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
        $this->calculateUserSalary();

//        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');

        return Command::SUCCESS;
    }


}
