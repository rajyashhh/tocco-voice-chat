<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ware;
use App\Models\Emoji;
use App\Models\Gift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SpecialId\Entities\UserWare;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SortGiftEmojiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


    public function run(): void
    {
        DB::transaction(function () {

            // ---------- EMOJIS ----------
            DB::statement('SET @i := 0');
            DB::statement("
            UPDATE emoji_categories
            SET sort = (@i := @i + 1)
            ORDER BY id
        ");

            // ---------- GIFTS ----------
            DB::statement('SET @i := 0');
            DB::statement("
            UPDATE gift_categories
            SET sort = (@i := @i + 1)
            ORDER BY id
        ");
        });

        // Raw UPDATEs bypass model events, so invalidate the catalog caches explicitly.
        try {
            Cache::tags(['gifts'])->flush();
            Cache::tags(['emojis'])->flush();
        } catch (\Exception $e) {
            // tag-unaware cache driver; ignore
        }
        Cache::forget('emoji_categories');
        Cache::forget('gift_categories:api');
    }
}
