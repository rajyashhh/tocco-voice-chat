<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reorders the ROOT admin sidebar sections (admin_menu.parent_id = 0) into the
 * owner's requested top-to-bottom order (2026-08-10):
 *
 *   1 Dashboard   2 Users   3 VIP   4 CP   5 Events   6 Levels
 *   7 Agencies    8 Rooms & Live   9 Gifts   10 Store
 *
 * Everything else at root level keeps its current RELATIVE order and is pushed
 * below the ten (order starting at 100). Child rows (parent_id != 0) are never
 * touched — this only rewrites the `order` column of root sections.
 *
 * Matching is by the English title key that AdminMenuRebuildSeeder::tree() plants
 * (Arabic labels are resolved from resources/lang/*.json at render time), with a
 * uri fallback where a stable uri exists. Nothing is inserted or deleted; a
 * section absent from the menu is skipped and logged.
 *
 * IDEMPOTENT: the ten always land on 1..10; the rest are re-based to 100.. by
 * their current relative order, which is already 100.. after the first run — so
 * repeated runs converge to the same layout.
 *
 * NOT registered in DatabaseSeeder (like DemoEarningsScenariosSeeder): run
 * standalone when the owner wants this ordering applied —
 *   php artisan db:seed --class=Database\\Seeders\\AdminMenuOrderSeeder
 * so a routine deploy never silently reshuffles a client's customized sidebar.
 */
class AdminMenuOrderSeeder extends Seeder
{
    /**
     * Owner order, top -> bottom. Each entry lists the candidate English title(s)
     * and/or uri(s) that identify the root section in this codebase. A root row
     * (parent_id = 0) matching ANY candidate title OR uri is selected.
     *
     * NOTE on VIP (rank 3): the owner's original ask read "IP" — a voice-dictation
     * drop of the leading V. Confirmed 2026-08-11 to mean the VIP section, which
     * exists as a live root row (title="VIP", parent_id=0, id=159) sitting right
     * before CP, exactly as the owner intended. Matched by title here.
     */
    private const ORDER = [
        1  => ['label' => 'Dashboard',    'titles' => ['Dashboard'],           'uris' => ['/']],
        2  => ['label' => 'Users',        'titles' => ['Users'],               'uris' => []],
        3  => ['label' => 'VIP',          'titles' => ['VIP'],                 'uris' => []],
        4  => ['label' => 'CP',           'titles' => ['CP'],                  'uris' => []],
        5  => ['label' => 'Events',       'titles' => ['Events'],              'uris' => []],
        6  => ['label' => 'Levels',       'titles' => ['Levels'],              'uris' => []],
        7  => ['label' => 'Agencies',     'titles' => ['Agencies'],            'uris' => []],
        8  => ['label' => 'Rooms & Live', 'titles' => ['Rooms & Live'],        'uris' => []],
        9  => ['label' => 'Gifts',        'titles' => ['Gifts'],               'uris' => []],
        10 => ['label' => 'Store',        'titles' => ['Store'],               'uris' => []],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $matchedIds = [];

            foreach (self::ORDER as $order => $spec) {
                $row = DB::table('admin_menu')
                    ->where('parent_id', 0)
                    ->where(function ($q) use ($spec) {
                        if (!empty($spec['titles'])) {
                            $q->whereIn('title', $spec['titles']);
                        }
                        if (!empty($spec['uris'])) {
                            $q->orWhereIn('uri', $spec['uris']);
                        }
                    })
                    ->orderBy('id')
                    ->first();

                if (!$row) {
                    Log::warning('AdminMenuOrderSeeder: root section not found, skipped', [
                        'rank'   => $order,
                        'label'  => $spec['label'],
                        'titles' => $spec['titles'],
                        'uris'   => $spec['uris'],
                    ]);
                    continue;
                }

                DB::table('admin_menu')
                    ->where('id', $row->id)
                    ->update(['order' => $order, 'updated_at' => now()]);

                $matchedIds[] = $row->id;
            }

            // Everything else at root level: keep current relative order, push
            // below the ten starting at 100.
            $rest = DB::table('admin_menu')
                ->where('parent_id', 0)
                ->when(!empty($matchedIds), fn ($q) => $q->whereNotIn('id', $matchedIds))
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            $next = 100;
            foreach ($rest as $row) {
                DB::table('admin_menu')
                    ->where('id', $row->id)
                    ->update(['order' => $next, 'updated_at' => now()]);
                $next++;
            }
        });
    }
}
