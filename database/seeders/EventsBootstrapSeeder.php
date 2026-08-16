<?php

namespace Database\Seeders;

use App\Models\Gift;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Modules\CP\Entities\WeeklyCpGift;
use Modules\Events\Entities\GeneralRole;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;
use Modules\Events\Entities\Reward;
use Modules\Events\Entities\WeeklyStar;

/**
 * Starter CONFIGURATION for the self-cycling events (Weekly Star, PK Tournament,
 * Weekly CP) plus the Charge King monthly prize setting.
 *
 * ROOT PROBLEM this fixes: these events are entirely admin-panel-driven — an
 * admin opens "Weekly Star" / "PK Events" / "Weekly CP", picks 3 gifts, sets a
 * start date and rank 1/2/3 rewards, and the events' own console commands
 * (weekly-star-update/winner, pk-event-update/winner, weekly-cp-winner) take it
 * from there forever, self-replicating each cycle. On a fresh install none of
 * that exists yet, so the events sit dead until someone configures them by hand.
 *
 * This seeder does exactly what an admin would do from those panel pages —
 * nothing more. Every row it writes matches, column for column, what
 * WeeklyEventNController / PkEventController / WeeklyCpController /
 * WeeklyEventGiftNController / PkEventGiftController / WeeklyCpGiftController
 * themselves persist, so the result looks and behaves like normal,
 * admin-entered data that just happens to already be there — fully visible and
 * editable from the panel afterwards.
 *
 * Idempotent and create-only: each event type is bootstrapped only if it has NO
 * row at all yet (a fresh/never-configured event). Once a first cycle exists,
 * the events' own update/winner commands own its lifecycle and this seeder
 * never touches it again — re-running (including on the live demo) is always
 * safe and never overwrites anything an admin has already set up.
 */
class EventsBootstrapSeeder extends Seeder
{
    private const RANK_REWARDS = [1 => 100000, 2 => 50000, 3 => 25000];

    public function run(): void
    {
        $this->bootstrapWeeklyStar();
        $this->bootstrapPkTournament();
        $this->bootstrapWeeklyCp();
        $this->bootstrapGeneralRoles();

        Setting::firstOrCreate(['key' => 'charge_king_prize'], ['value' => '100000']);
    }

    private function bootstrapWeeklyStar(): void
    {
        if (WeeklyStar::where('type', 'weekly_star')->exists()) {
            return;
        }

        $weeklyStar = WeeklyStar::create([
            'type' => 'weekly_star',
            'start_date' => now()->toDateString(),
            'description_en' => 'Weekly Star',
            'description_ar' => 'النجم الأسبوعي',
        ]);

        $this->attachStarterGifts($weeklyStar);

        foreach (self::RANK_REWARDS as $level => $target) {
            Reward::create([
                'weekly_star_id' => $weeklyStar->id,
                'type' => 'coins',
                'level' => $level,
                'target' => $target,
                'expire' => 1,
            ]);
        }
    }

    private function bootstrapPkTournament(): void
    {
        if (PkEvent::query()->exists()) {
            return;
        }

        $pkEvent = PkEvent::create([
            'start_date' => now()->toDateString(),
        ]);

        foreach (['pk-star', 'pk-king', 'pk-room'] as $pkType) {
            foreach (self::RANK_REWARDS as $level => $target) {
                PkReward::create([
                    'pk_event_id' => $pkEvent->id,
                    'type' => 'coins',
                    'level' => $level,
                    'target' => $target,
                    'pk_type' => $pkType,
                    'expire' => 1,
                ]);
            }
        }
    }

    private function bootstrapWeeklyCp(): void
    {
        if (WeeklyStar::where('type', 'weekly_cp')->exists()) {
            return;
        }

        $weeklyCp = WeeklyStar::create([
            'type' => 'weekly_cp',
            'start_date' => now()->toDateString(),
            'description_en' => 'Weekly CP',
            'description_ar' => 'حدث الـCP الأسبوعي',
        ]);

        $this->attachStarterGifts($weeklyCp);

        foreach (self::RANK_REWARDS as $level => $target) {
            WeeklyCpGift::create([
                'weekly_cp_id' => $weeklyCp->id,
                'type' => 'coins',
                'level' => $level,
                'target' => $target,
                'gender' => 'all',
                'expire' => 1,
            ]);
        }
    }

    /**
     * Weekly Star and Weekly CP both require exactly 3 counted gifts (enforced
     * by the admin form's `size:3` validation). We only link gifts that already
     * exist in the catalog — creating placeholder gifts is out of scope here;
     * if fewer than 3 gifts exist yet, the admin links them from the panel once
     * the gift catalog is populated.
     */
    private function attachStarterGifts(WeeklyStar $event): void
    {
        $giftIds = Gift::query()->orderBy('id')->limit(3)->pluck('id');
        if ($giftIds->count() === 3) {
            $event->gifts()->sync($giftIds);
        }
    }

    private function bootstrapGeneralRoles(): void
    {
        // URLs are stored RELATIVE on purpose: GeneralRole::getUrlAttribute()
        // resolves them against the current APP_URL, so every white-label
        // client gets links on its own domain with zero data changes.
        $defaults = [
            'weekly_star' => [
                'url' => '/weekly-star-view',
                'desc_en' => 'Weekly Star',
                'desc_ar' => 'النجم الأسبوعي',
                'desc_tr' => 'Haftalık Yıldız',
                'desc_hi' => 'साप्ताहिक स्टार',
                'desc_id' => 'Bintang Mingguan',
            ],
            'pk_event' => [
                'url' => '/pk-event-view',
                'desc_en' => 'PK Tournament',
                'desc_ar' => 'بطولة البيكي',
                'desc_tr' => 'PK Turnuvası',
                'desc_hi' => 'PK टूर्नामेंट',
                'desc_id' => 'Turnamen PK',
            ],
            'charge_event' => [
                'url' => '/charge-king-view',
                'desc_en' => 'Charge King',
                'desc_ar' => 'ملك الشحن',
                'desc_tr' => 'Şarj Kralı',
                'desc_hi' => 'चार्ज किंग',
                'desc_id' => 'Raja Top-Up',
            ],
            'weekly_cp' => [
                'url' => '/weekly-cp-view',
                'desc_en' => 'Weekly CP',
                'desc_ar' => 'حدث الـCP الأسبوعي',
                'desc_tr' => 'Haftalık CP',
                'desc_hi' => 'साप्ताहिक CP',
                'desc_id' => 'CP Mingguan',
            ],
        ];

        foreach ($defaults as $type => $attributes) {
            $role = GeneralRole::firstOrCreate(['type' => $type], $attributes);

            // Existing rows seeded before the H5 pages/translations existed:
            // back-fill only what is still empty, never overwrite admin edits.
            $updates = [];
            foreach ($attributes as $column => $value) {
                if (empty($role->{$column})) {
                    $updates[$column] = $value;
                }
            }
            if ($updates !== []) {
                $role->update($updates);
            }
        }
    }
}
