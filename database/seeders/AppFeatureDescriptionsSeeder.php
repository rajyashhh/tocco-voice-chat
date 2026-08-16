<?php

namespace Database\Seeders;

use App\Models\AppFeature;
use Illuminate\Database\Seeder;

class AppFeatureDescriptionsSeeder extends Seeder
{
    /**
     * Fill Arabic marketing descriptions for the customer-facing features.
     * Idempotent: only writes when the row exists and its description_ar is
     * still empty, so a later owner edit is never overwritten by re-running.
     * Images are intentionally left untouched — the owner uploads them.
     */
    public function run(): void
    {
        $descriptions = [
            'game'          => 'العب داخل الغرف مع أصدقائك واكسب المكافآت لحظة بلحظة.',
            'lucky'         => 'جرّب حظك مع هدايا الحظ واربح جوائز مضاعفة في كل جولة.',
            'weekly_star'   => 'تنافس أسبوعيًا على القمة واحصد لقب نجم الأسبوع مع هدايا حصرية.',
            'period_event'  => 'فعاليات موقوتة بجوائز متجددة، شارك خلال الفترة واجمع أكبر قدر من المكافآت.',
            'pk_event'      => 'تحدَّ الآخرين في مواجهات مباشرة مثيرة واثبت أنك الأقوى.',
            'target_events' => 'حقّق الأهداف اليومية والموسمية وافتح مكافآت تصاعدية.',
            'achievement'   => 'اجمع أوسمة الإنجاز وارتقِ عبر المستويات.',
        ];

        foreach ($descriptions as $slug => $descriptionAr) {
            AppFeature::where('slug', $slug)
                ->where(function ($query) {
                    $query->whereNull('description_ar')->orWhere('description_ar', '');
                })
                ->update(['description_ar' => $descriptionAr]);
        }
    }
}
