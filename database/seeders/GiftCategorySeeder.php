<?php

namespace Database\Seeders;

use App\Models\Gift;
use App\Models\User;
use App\Models\Follow;
use App\Models\RoomGame;
use App\Models\GiftCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $normal  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Normal ',
                'ar' => 'عادية',
                'hi' => 'साधारण ',
                'ur' => 'عام',
            ],

        ]);
        Gift::where('type', 1)->update(['gift_category_id' => $normal->id]);
        $hot  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Hot',
                'ar' => 'ساخنة',
                'hi' => 'गरम',
                'ur' => 'گرم',
            ],

        ]);
        Gift::where('type', 2)->update(['gift_category_id' => $hot->id]);
        $country  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Country',
                'ar' => 'دولة',
                'hi' => 'देश',
                'ur' => 'ملک',
            ],

        ]);
        Gift::where('type', 3)->update(['gift_category_id' => $country->id]);
        $moment  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Moment',
                'ar' => 'لحظة',
                'hi' => 'पल',
                'ur' => 'لمحہ',
            ],

        ]);
        Gift::where('type', 4)->update(['gift_category_id' => $moment->id]);
        $vip  = GiftCategory::create([
            "type" => 'vip',
            "title" => [
                'en' => 'VIP',
                'ar' => 'شخص مهم',
                'hi' => 'वीआईपी',
                'ur' => 'وی آئی پی',
            ],

        ]);
        Gift::where('type', 9)->update(['gift_category_id' => $vip->id]);
        $event  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Events',
                'ar' => 'الفعاليات',
                'hi' => 'इवेंट्स',
                'ur' => 'تقریبات',
            ],

        ]);
        Gift::where('type', 7)->update(['gift_category_id' => $event->id]);
        $lucky  = GiftCategory::create([

            "type" => 'lucky_gift',
            'title' => [
                'en' => 'Lucky gifts',        // English
                'ar' => 'هدايا محظوظة',       // Arabic
                'hi' => 'भाग्यशाली उपहार',    // Hindi
                'ur' => 'نصیب والے تحفے',     // Urdu
            ],

        ]);
        Gift::where('type', 6)->update(['gift_category_id' => $lucky->id]);
        $famous  = GiftCategory::create([
            "type" => 'normal',
            "title" => [
                'en' => 'Famous gifts',
                'ar' => 'هدايا مشهورة',
                'hi' => 'प्रसिद्ध उपहार',
                'ur' => 'مشہور تحفے',
            ],

        ]);
        Gift::where('type', 5)->update(['gift_category_id' => $famous->id]);
        $cp  = GiftCategory::create([
            "type" => 'cp',
            "title" => [
                'en' => 'Cp',
                'ar' => 'cp',
                'hi' => 'cp',
                'ur' => 'cp',
            ],

        ]);
        Gift::where('type', 10)->update(['gift_category_id' => $cp->id]);
    }
}
