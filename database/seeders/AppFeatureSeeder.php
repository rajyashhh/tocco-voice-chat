<?php

namespace Database\Seeders;

use App\Models\AppFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $appFeatures =[
            ['name' => 'Game Feature', 'name_ar' => 'ميزه الالعاب', 'slug' => 'game', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Lucky Feature', 'name_ar' => 'ميزه الحظ', 'slug' => 'lucky', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        
            // events
            ['name' => 'Weekly Star Feature', 'name_ar' => 'ميزه نجم الاسبوع', 'slug' => 'weekly_star', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Periods Event Feature', 'name_ar' => 'ميزه الفتره الزمنيه', 'slug' => 'period_event', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Pk Event Feature', 'name_ar' => 'ميزه ال pk', 'slug' => 'pk_event', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Target Event Feature', 'name_ar' => 'ميزه ال target', 'slug' => 'target_events', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        
            ['name' => 'Achievement Feature', 'name_ar' => 'ميزه الانجاز', 'slug' => 'achievement', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Whatsapp Feature', 'name_ar' => 'ميزه الواتس اب', 'slug' => 'whatsapp', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Cp Feature', 'name_ar' => 'ميزه ال cp', 'slug' => 'cp', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Room Target Feature', 'name_ar' => 'ميزه ال Room Target', 'slug' => 'room_target', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Charizma Feature', 'name_ar' => 'ميزه ال Charizma', 'slug' => 'charizma', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Chat Feature', 'name_ar' => 'ميزه المحادثة', 'slug' => 'chat', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Moment Feature', 'name_ar' => 'ميزه البوستات', 'slug' => 'moment', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Reels Feature', 'name_ar' => 'ميزه الفديوهات', 'slug' => 'reel', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Salary Transaction Feature', 'name_ar' => 'ميزه طلبات التحويل', 'slug' => 'salary_transaction', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],       
            ['name' => 'config', 'name_ar' => ' تكوينات ', 'slug' => 'config', 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'commission', 'name_ar' => 'عموله', 'slug' => 'commission', 'status' => 0, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];
        foreach ($appFeatures as $appFeature) {

            $appFeatureExist = AppFeature::query()->where('slug', $appFeature['slug'])->exists();
            if ($appFeatureExist  ) continue;

            DB::table('app_features')->insert($appFeature);

        }
        
    }
}

