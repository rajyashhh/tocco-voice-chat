<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Config;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\Winner;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkWinner;
use Modules\Events\Entities\WeeklyStar;

class PkEventSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $previousEvent = PkEvent::with('rewards')->first();

        $users = User::take(3)->get();
        foreach ($users as $key => $user) {
            $data = [
                'pk_event_id' => $previousEvent->id,
                'user_id' => $user->id,
                'level' => $key + 1,
                'pk_type' => 'pk-star',
            ];
            $winner =    PkWinner::create($data);
            DB::table('reward_winner_pks')->insert([
                'pk_winner_id' => $winner->user_id,
                'pk_reward_id' => $previousEvent->rewards->where('level', 1)->where('pk_type', 'pk-star')->first()->id,

            ]);
        }
    }
}
