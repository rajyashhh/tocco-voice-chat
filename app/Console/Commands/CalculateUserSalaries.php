<?php

namespace App\Console\Commands;

use App\Traits\Salaries\UserSalaryTrait;
use Illuminate\Console\Command;


class CalculateUserSalaries extends Command
{

    use UserSalaryTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:calculate-salaries';

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
        if (now() < now()->startOfMonth()->addMinutes(15) || now() >= now()->endOfMonth()->subMinutes(15)) return;

        $this->updateUserSalary();
    }




}
