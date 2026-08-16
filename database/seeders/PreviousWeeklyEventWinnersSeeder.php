<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Config;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\Winner;

class PreviousWeeklyEventWinnersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $previousWeeklyEvent = WeeklyStar::previousEvent()->weeklyStar()
            ->with('gifts')
            ->orderBy('start_date', 'desc')
            ->first();

        $users = User::take(3)->get();
        foreach ($users as $key => $user) {
            $data = [
                'weekly_star_id' => $previousWeeklyEvent->id,
                'user_id' => $user->id,
                'level' => $key + 1,
            ];
            Winner::create($data);
        }
    }
}
