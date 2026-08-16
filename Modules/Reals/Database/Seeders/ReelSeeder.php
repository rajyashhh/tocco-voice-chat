<?php

namespace Modules\Reals\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Reel;
use App\Models\ReelLike;
use App\Models\ReelComment;
use App\Models\ReelGift;

class ReelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users first
        $users = [];
        $userNames = [
            'أحمد محمد',
            'فاطمة علي',
            'محمود سعيد',
            'نور الدين',
            'سارة أحمد',
            'عمر خالد',
            'ليلى حسن',
            'يوسف إبراهيم',
            'مريم عبدالله',
            'كريم محمود'
        ];

        foreach ($userNames as $name) {
            $users[] = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Sample video URLs (using placeholder videos)
        $videoUrls = [
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
        ];

        $reelTitles = [
            'لحظة رائعة من الطبيعة',
            'مشهد غروب الشمس الساحر',
            'رقصة تقليدية مميزة',
            'طبخة لذيذة من المطبخ العربي',
            'لحظات مضحكة مع الحيوانات',
            'منظر طبيعي خلاب',
            'مهارات كرة قدم احترافية',
            'فن الرسم بالرمل',
            'رحلة سفر إلى الجبال',
            'تحدي الطبخ السريع',
            'لحظات ملهمة من الحياة',
            'فيديو تحفيزي للنجاح',
        ];

        $descriptions = [
            'فيديو رائع يستحق المشاهدة',
            'محتوى مميز وجديد',
            'لا تفوت هذه اللحظة الجميلة',
            'شارك هذا الفيديو مع أصدقائك',
            'محتوى حصري وجديد',
            'لحظة لا تنسى',
            'استمتع بالمشاهدة',
            'محتوى ترفيهي ممتع',
            'فيديو يستحق الإعجاب',
            'شاهد حتى النهاية',
        ];

        // Create 50 reels
        for ($i = 0; $i < 50; $i++) {
            $user = $users[array_rand($users)];
            $likesCount = rand(100, 10000);
            $commentsCount = rand(10, 1000);
            $giftsCount = rand(5, 500);
            
            $reel = Reel::create([
                'user_id' => $user->id,
                'title' => $reelTitles[array_rand($reelTitles)],
                'description' => $descriptions[array_rand($descriptions)],
                'video_url' => $videoUrls[$i % count($videoUrls)],
                'thumbnail_url' => 'https://picsum.photos/400/700?random=' . $i,
                'views_count' => rand(1000, 100000),
                'likes_count' => $likesCount,
                'comments_count' => $commentsCount,
                'gifts_count' => $giftsCount,
                'duration' => rand(15, 60),
                'is_active' => true,
            ]);

            // Create random likes (limit to avoid performance issues)
            $numLikes = min(rand(5, 20), count($users));
            $likeUsers = collect($users)->random($numLikes);
            foreach ($likeUsers as $likeUser) {
                ReelLike::create([
                    'reel_id' => $reel->id,
                    'user_id' => $likeUser->id,
                ]);
            }

            // Create random comments
            $comments = [
                'رائع جداً! 😍',
                'محتوى مميز، استمر 👏',
                'أعجبني هذا الفيديو كثيراً',
                'شكراً على المحتوى الجميل',
                'فيديو ملهم ومفيد',
                'أحسنت الصنع 🌟',
                'محتوى رائع، شكراً لك',
                'استمتعت بالمشاهدة',
                'فيديو مذهل! 🔥',
                'أفضل فيديو شاهدته اليوم',
                'محتوى قيم ومفيد جداً',
                'رائع، أكمل إبداعك',
                'فيديو يستحق المشاركة',
                'محتوى احترافي',
                'جميل جداً ❤️',
            ];

            for ($j = 0; $j < min($commentsCount, 15); $j++) {
                $commentUser = $users[array_rand($users)];
                ReelComment::create([
                    'reel_id' => $reel->id,
                    'user_id' => $commentUser->id,
                    'comment' => $comments[array_rand($comments)],
                ]);
            }

            // Create random gifts
            $giftTypes = ['rose', 'heart', 'diamond', 'star', 'fire', 'crown'];
            for ($j = 0; $j < min($giftsCount, 20); $j++) {
                $giftUser = $users[array_rand($users)];
                ReelGift::create([
                    'reel_id' => $reel->id,
                    'user_id' => $giftUser->id,
                    'gift_type' => $giftTypes[array_rand($giftTypes)],
                    'gift_value' => rand(1, 100),
                ]);
            }
        }
    }
}
