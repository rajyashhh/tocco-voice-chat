<?php

namespace Database\Seeders;

use App\Models\BanType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $banTypes = [
            ['name_ar' => 'ارسال هداية', 'name_en' => 'send gift', 'route' => 'gifts/send',],
            ['name_ar' => 'دخول الغرفة', 'name_en' => 'enter room', 'route' => 'rooms/enter_room',],
            ['name_ar' => 'يصل الميكروفون', 'name_en' => 'up_microphone', 'route' => 'rooms/up_microphone',],
             [
                'name_ar' => 'طلب الانضمام الى وكاله', 'name_en' => 'join agency request',
                'route'   => 'agencies/join_request',
            ], ['name_ar' => 'شراء vip', 'name_en' => 'buy vip', 'route' => 'vips/buyVip',],
            ['name_ar' => 'طلب الانضمام الى عائلة', 'name_en' => 'join family', 'route' => 'families/join',],
            ['name_ar' => 'مجموعة محادثة', 'name_en' => 'group chat', 'route' => 'group-chat/send',],
            
            ['name_ar' => 'نشر ريلز ', 'name_en' => 'POST reel', 'route' => 'reals','method'=>'POST'],
            ['name_ar' => 'تعديل ريلز ', 'name_en' => 'update reel', 'route' => 'reals','method'=>"UPDATE"],
            ['name_ar' => 'نشر لحظة', 'name_en' => 'post moment', 'route' => 'moment','method'=>'POST'], 
            ['name_ar' => 'تعديل لحظة', 'name_en' => 'update moment', 'route' => 'moment','method'=>"UPDATE"], 
            ['name_ar' => 'عرض كل لحظة', 'name_en' => 'get moment', 'route' => 'moment','method'=>'GET'], 
            ['name_ar' => 'عرض كل ريلز ', 'name_en' => 'get reel', 'route' => 'reals','method'=>'GET'],
            ['name_ar' => 'عرض كل متبعين ريلز ', 'name_en' => 'get followers reel', 'route' => 'reals/user-followers',],
            ['name_ar' => ' انشاء دردشه الغرفه ', 'name_en' => ' Create chat room', 'route' => 'Chat-room','method'=>'POST'],
            ['name_ar' => ' عرض دردشه الغرفه ', 'name_en' => 'Show chat room', 'route' => 'Chat-room','method'=>'GET'],
            ['name_ar' => ' انشاء دردشه ', 'name_en' => ' Create chat ', 'route' => 'Chat-Message','method'=>'POST'],
            ['name_ar' => ' عرض دردشه  ', 'name_en' => 'Show chat ', 'route' => 'Chat-Message','method'=>'GET'],

        ];

        foreach ($banTypes as $banType) {

            $route = $banType['route'];
            $nameEn = $banType['name_en'];
            $method = @$banType['method'];
            $banExists = BanType::query()->where('route', $route)->when($method,function ($q, $method) {$q->where('method',$method );})->exists();
            if ($banExists ) continue;

            DB::table('ban_types')->insert($banType);

        }

    }
}
