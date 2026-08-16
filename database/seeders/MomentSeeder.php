<?php

namespace Database\Seeders;

use App\Models\MomentGallery;
use Illuminate\Database\Seeder;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentLikes;
use Modules\Moment\Entities\MomentCommint;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MomentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Moment::factory()->count(500)->create();

        // $moments = Moment::inRandomOrder()->take(300)->get();
        // foreach($moments as $moment){
        //     MomentLikes::create([
        //         'moment_id'=> $moment->id,
        //         'user_id' => rand(1,2)
        //     ]);
        // }

        // $moments = Moment::inRandomOrder()->take(300)->get();

        // foreach($moments as $moment){
        //     MomentCommint::create([
        //         'moment_id'=> $moment->id,
        //         'user_id' => rand(1,2),
        //         'comment' => fake()->text()
        //     ]);
        // }


        $moments = Moment::get();

        foreach($moments as $moment)
        {
            if($moment->img)
            {
                MomentGallery::create([
                    'moment_id' =>  $moment->id,
                    'image' => $moment->img,
                ]);
            }
           

        }



    }
}
