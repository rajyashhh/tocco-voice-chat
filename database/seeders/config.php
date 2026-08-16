<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class config extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $configs = array(
                array('id' => '8','name' => 'login_from_only_one_device','value' => 'yes','desc' => 'الدخول من هاتف واحد','created_at' => '2023-03-05 12:10:19','updated_at' => '2023-03-05 12:10:19'),
                array('id' => '9','name' => 'one_usd_value_in_coins','value' => '800','desc' => 'واحد دولار يساوي كم كوين','created_at' => '2023-04-06 13:28:54','updated_at' => '2023-05-24 17:55:49'),
                array('id' => '17','name' => 'all_target_or_nothing','value' => 'true','desc' => 'لا يتم احتساب التارجيت اذا لم يكمل وقت البث','created_at' => '2023-05-24 17:55:51','updated_at' => '2023-05-24 17:58:28'),
                array('id' => '18','name' => 'group_chat','value' => '1','desc' => NULL,'created_at' => '2023-05-25 13:56:29','updated_at' => '2023-05-25 14:01:55'),
                array('id' => '20','name' => 'room_rule','value' => 'الرجاء من المستخدمين الكرام التحلي بالاخلاق مع الاخرين شاكرين تفهمكم, اهلا وسهلا بكم','desc' => NULL,'created_at' => '2023-06-13 16:58:12','updated_at' => '2023-06-13 16:58:12'),
                array('id' => '43','name' => 'max_room_admin','value' => '100','desc' => 'الحد الأقصي لعدد المشرفين','created_at' => '2023-06-17 15:13:47','updated_at' => '2023-06-17 15:13:47')
              );
        
        DB::table ('configs')->insert ($configs);
    }
}
