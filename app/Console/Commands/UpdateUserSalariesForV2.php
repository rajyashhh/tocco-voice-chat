<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Traits\Salaries\UserSalaryTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Services\FixedTargetService;
use Modules\FixedTarget\Services\FixedTargetV2Service;

class UpdateUserSalariesForV2 extends Command
{
    use UserSalaryTrait;
  
    protected $signature = 'users:update-salaries-v2';

    protected $description = 'add user achievements after 30 day';

    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $month = "06";
        $year = request()->year ?? now()->year;

        User::query()
                ->where('agency_id', '!=', 0)
                // ->where('salary_is_updated', 1)
                ->where('type_user', '!=', 0)
                ->chunk(500, function ($users) use($month, $year){
                    foreach ($users as $user) {
                        try {

                            $targetService = new FixedTargetV2Service($user, month: $month, year: $year);
                            $targetService->calculateTarget();
                        } catch (\Throwable $e) {
                            dd($e->getMessage());
                            // $this->error("Failed user ID {$user->id}");
                        }
                    }
                });
    }


}
