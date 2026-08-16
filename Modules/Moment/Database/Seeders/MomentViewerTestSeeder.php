<?php

namespace Modules\Moment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentCommint;
use Modules\Moment\Entities\MomentLikes;
use App\Models\User;
use App\Models\MomentGallery;
use Faker\Factory as Faker;

class MomentViewerTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // جلب بعض المستخدمين الموجودين
        $users = User::take(20)->get();
        
        if ($users->isEmpty()) {
            $this->command->error('No users found! Please create users first.');
            return;
        }

        // جلب الهدايا الموجودة (إذا كانت موجودة)
        $gifts = DB::table('gifts')->pluck('id')->toArray();
        $hasGifts = !empty($gifts);

        $this->command->info('Creating test moments...');

        // إنشاء 30 moment تجريبية
        for ($i = 1; $i <= 30; $i++) {
            $user = $users->random();
            
            $moment = Moment::create([
                'user_id' => $user->id,
                'description' => $faker->paragraph(rand(1, 3)),
                'img' => null, // سنضيف الصور في المعرض
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now(),
            ]);

            // إضافة صور/فيديوهات للـ Moment
            $imageCount = rand(1, 5);
            for ($j = 0; $j < $imageCount; $j++) {
                MomentGallery::create([
                    'moment_id' => $moment->id,
                    'image' => 'storage/moments/test_' . rand(1, 10) . '.jpg', // مسار تجريبي
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // إضافة لايكات عشوائية
            $likesCount = rand(0, 15);
            $likedUsers = $users->random(min($likesCount, $users->count()));
            foreach ($likedUsers as $likeUser) {
                MomentLikes::firstOrCreate([
                    'moment_id' => $moment->id,
                    'user_id' => $likeUser->id,
                ], [
                    'created_at' => now()->subSeconds(rand(1, 86400)),
                    'updated_at' => now(),
                ]);
            }

            // إضافة تعليقات عشوائية
            $commentsCount = rand(0, 10);
            for ($k = 0; $k < $commentsCount; $k++) {
                $commentUser = $users->random();
                MomentCommint::create([
                    'moment_id' => $moment->id,
                    'user_id' => $commentUser->id,
                    'comment' => $faker->sentence(rand(5, 15)),
                    'created_at' => now()->subSeconds(rand(1, 86400)),
                    'updated_at' => now(),
                ]);
            }

            // إضافة هدايا عشوائية (فقط إذا كانت الهدايا موجودة)
            if ($hasGifts && rand(0, 1)) {
                $giftsCount = rand(1, min(5, count($gifts)));
                for ($l = 0; $l < $giftsCount; $l++) {
                    try {
                        DB::table('moment_user_gifts')->insert([
                            'moment_id' => $moment->id,
                            'user_id' => $users->random()->id,
                            'gift_id' => $gifts[array_rand($gifts)], // هدية موجودة فعلاً
                            'num' => rand(1, 5),
                            'created_at' => now()->subSeconds(rand(1, 86400)),
                            'updated_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        // تجاهل الأخطاء في الهدايا
                    }
                }
            }

            $this->command->info("Created moment #{$i} with {$imageCount} images, {$likesCount} likes, {$commentsCount} comments");
        }

        $this->command->info('✅ Successfully created 30 test moments!');
        $this->command->info('You can now visit: /admin/moment-viewer');
    }
}
