<?php

namespace Modules\Reals\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Reals\Entities\Real;
use Modules\Reals\Entities\RealUserLike;

class ReelsLikeTableSeeder extends Seeder
{
    private ?string $userId = null;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $realIds = Real::query()->pluck('id');

        $userId =  441;
        foreach ($realIds as $id){

            RealUserLike::query()->updateOrCreate(['real_id' => $id, 'user_id' => $userId]);
        }
    }


}
