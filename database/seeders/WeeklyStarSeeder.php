<?php

namespace Database\Seeders;

use App\Models\Gift;
use App\Models\GiftLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\WeeklyStarGift;

class WeeklyStarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        WeeklyStar::withoutEvents(function(){
            $admin = User::inRandomOrder()->first();
            $editor = User::inRandomOrder()->first();

            $weeklyStar = WeeklyStar::create([
                'admin_id' => $admin->id,
                'start_date' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfWeek()->format('Y-m-d'),
                'editor_id' => $editor->id,
                'description_en' => fake()->text(10),
                'description_ar' => fake()->text(10),

            ]);

            for($i =0; $i<10; $i++){
                $gift = Gift::inRandomOrder()->first();
                $sender = User::inRandomOrder()->first()->id;
                $receiver = User::inRandomOrder()->first()->id;
                $room_owner = User::inRandomOrder()->first()->id;

                if($sender == $receiver)
                continue;

                WeeklyStarGift::create([
                    'gift_id' => $gift->id,
                    'weekly_star_id' => $weeklyStar->id
                ]);

                $price = rand(100, 1000);
                GiftLog::create([
                    'giftId' => $gift->id,
                    'roomowner_id' => $room_owner,
                    'giftName' => fake()->name(),
                    'giftNum' => 2,
                    'giftPrice' =>$price,
                    'sender_id' =>$sender,
                    'receiver_id' => $receiver,
                    'app_profit_coins' => $price
                ]);
            }

        });
    }

}
