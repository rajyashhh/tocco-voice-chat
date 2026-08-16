<?php

namespace Modules\Reals\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Reals\Entities\Real;
use Modules\Reals\Entities\RealUserLike;
use Modules\Reals\Entities\RealUserComment;

class AdditionalReelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->error('لا يوجد مستخدمين! قم بتشغيل ReelSeeder أولاً');
            return;
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
            'محتوى إبداعي جديد',
            'لحظات لا تُنسى من رحلتي',
            'تجربة طبخ مميزة اليوم',
            'فن الخط العربي الجميل',
            'رياضة الصباح المنعشة',
            'نصائح للحياة الصحية',
            'مشاهد من الطبيعة الخلابة',
            'موسيقى هادئة للاسترخاء',
            'تعلم شيء جديد كل يوم',
            'إلهام وتحفيز للنجاح',
            'سفر واستكشاف العالم',
            'فن الديكور المنزلي',
            'تصوير فوتوغرافي احترافي',
            'وصفات سريعة ولذيذة',
            'تمارين رياضية منزلية',
            'قراءة كتاب ملهم',
            'حرف يدوية مبتكرة',
            'تكنولوجيا المستقبل',
            'موضة وأناقة عصرية',
            'نباتات الزينة المنزلية',
        ];

        $descriptions = [
            'محتوى حصري وجديد كلياً',
            'تابعوا للمزيد من الإبداع',
            'شاركوني آرائكم',
            'أتمنى أن ينال إعجابكم',
            'محتوى يستحق المشاهدة',
            'دعمكم يعني الكثير',
            'إبداع لا حدود له',
            'محتوى ملهم ومفيد',
            'تجربة فريدة من نوعها',
            'شكراً لمتابعتكم الدائمة',
        ];

        $this->command->info('جاري إضافة 50 ريل إضافية...');
        
        $startId = Real::max('id') ?? 0;

        // Create additional 50 reels
        for ($i = 0; $i < 50; $i++) {
            $user = $users->random();
            $likesCount = rand(100, 15000);
            $commentsCount = rand(10, 1500);
            
            $reel = Real::create([
                'user_id' => $user->id,
                'description' => $reelTitles[array_rand($reelTitles)] . ' - ' . $descriptions[array_rand($descriptions)],
                'url' => $videoUrls[$i % count($videoUrls)],
                'intro_image' => 'https://picsum.photos/400/700?random=' . ($startId + $i + 100),
                'like_num' => $likesCount,
                'comment_num' => $commentsCount,
                'share_num' => rand(5, 600),
                'sub_video' => null,
            ]);

            // Create random likes (limit to avoid performance issues)
            $numLikes = min(rand(5, 20), $users->count());
            $likeUsers = $users->random($numLikes);
            foreach ($likeUsers as $likeUser) {
                try {
                    RealUserLike::create([
                        'real_id' => $reel->id,
                        'user_id' => $likeUser->id,
                    ]);
                } catch (\Exception $e) {
                    // Skip if duplicate
                }
            }

            // Create random comments
            $comments = [
                'محتوى رائع جداً! 🌟',
                'إبداع متواصل 👏',
                'استمر في التميز',
                'محتوى قيم ومفيد',
                'شكراً على المشاركة',
                'رائع، أكمل 💪',
                'محتوى ملهم فعلاً',
                'تسلم إيدك ❤️',
                'ما شاء الله عليك',
                'محتوى احترافي 🔥',
                'أفضل محتوى شاهدته',
                'إبداع في أبهى صوره',
                'محتوى يستحق المتابعة',
                'رائع ومميز',
                'الله يبارك فيك 🌹',
            ];

            for ($j = 0; $j < min($commentsCount, 15); $j++) {
                RealUserComment::create([
                    'real_id' => $reel->id,
                    'user_id' => $users->random()->id,
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
            
            if (($i + 1) % 10 == 0) {
                $this->command->info('تم إنشاء ' . ($i + 1) . ' ريل...');
            }
        }
        
        $this->command->info('✅ تم إضافة 50 ريل إضافية بنجاح!');
        $this->command->info('إجمالي الريلز الآن: ' . Real::count());
    }
}
