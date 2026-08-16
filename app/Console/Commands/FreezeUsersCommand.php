<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class FreezeUsersCommand extends Command
{
    protected $signature = 'users:freeze-unfinished';
    protected $description = 'Freeze users with unfinished salary records every 10 minutes';

    public function handle()
    {
        $userIds = DB::table('user_sallaries as a')
            ->join('user_sallaries as b', function ($join) {
                $join->on('a.user_id', '=', 'b.user_id')
                    ->on('a.month', '=', 'b.month')
                    ->on('a.year', '=', 'b.year')
                    ->on('a.user_agency_id', '=', 'b.user_agency_id')
                    ->whereRaw('a.id <> b.id');
            })
            ->where('a.is_finished', 0)
            ->where('b.is_finished', 1)
            ->select('a.user_id', DB::raw('SUM(a.cut_amount + b.cut_amount) as total_cut_amount'))
            ->groupBy('a.user_id')
            ->pluck('a.user_id');


        if ($userIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $userIds)
                ->update(['transfer_salary' => 1]);

//            $this->info('Set transfer_salary=1 for these users: ' . $userIds->implode(', '));
        } else {
//            $this->info('No users matched the criteria.');
        }
        return 0;
    }
}
