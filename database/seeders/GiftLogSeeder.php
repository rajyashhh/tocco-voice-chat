<?php

namespace Database\Seeders;

use App\Models\Cp;
use App\Models\Gift;
use App\Models\GiftLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GiftLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        for($i=0;$i<10;$i++){
            $cp = Cp::inRandomOrder()->first();
            GiftLog::create([
                'giftId' => Gift::inRandomOrder()->first()->id,
                'roomowner_id' => User::inRandomOrder()->first()->id,
                'giftName' => 'present',
                'sender_id' => $cp->user_one_id,
                'receiver_id' => $cp->user_two_id,
                'cp_id' => $cp->id,
                'giftNum' => 2,
                'giftPrice' => 100,
                'app_profit_coins' => 100
            ]);
        }
    }
}
