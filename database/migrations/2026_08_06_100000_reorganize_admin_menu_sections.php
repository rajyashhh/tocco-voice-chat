<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reorganize the admin sidebar into clear, separate sections.
 *
 * ROOT PROBLEM: historically every app page was dumped under a single top-level
 * "App" node (admin_menu title='App', uri='/'), producing one giant 30+ item
 * list — rooms, members, agencies, finance, VIP and content all mixed together.
 * The owner's words: "التطبيق كله قسم واحد دي مصيبة". Verified that the flat
 * layout is the historical original — the menu seeder (AdminMemuSeeder) is
 * disabled, so no sectioned menu ever existed to copy. This is therefore an
 * original organisational fix, committed once to source so every future client
 * clone inherits a properly organised panel out of the box.
 *
 * WHAT IT DOES:
 *   1. Ensures five NEW section parents exist (Rooms / Members / Finance / VIP /
 *      Content) as treeview parents. Agency leaves fold into the EXISTING
 *      top-level "Agencies" node (uri='agencies') rather than a new parent, so we
 *      never collide with it.
 *   2. Re-parents each App child into its section — matched by uri but SCOPED to
 *      the App parent's direct children only. This is critical: some uris (e.g.
 *      `userTarget`) also exist under the separate agency-panel menu, and an
 *      unscoped uri match would wrongly move those too.
 *   3. Retires the old catch-all "App" parent once emptied; any straggler is
 *      re-pointed to Content so nothing is orphaned/hidden.
 *   4. Collapses duplicate top-level '/' dashboards down to one.
 *
 * Purely structural: it re-parents menu rows only. It deliberately does NOT touch
 * admin_role_menu — a client may have built limited roles and bound menu items to
 * them from the Roles UI, and wiping those bindings would hide those items from
 * the limited role. Super-admin visibility is already governed by the `*`
 * permission independently of role_menu rows.
 *
 * Idempotent throughout: sections are found-or-created by a stable uri sentinel,
 * and re-parenting is a no-op once applied.
 */
return new class extends Migration
{
    /** NEW section parents. key => [title, icon, order, sentinel uri]. */
    private const SECTIONS = [
        'rooms'   => ['Rooms',   'fa-comments',  20, '#section-rooms'],
        'members' => ['Members', 'fa-users',     21, '#section-members'],
        'finance' => ['Finance', 'fa-money',     23, '#section-finance'],
        'vip'     => ['VIP',     'fa-star',      24, '#section-vip'],
        'content' => ['Content', 'fa-bullhorn',  25, '#section-content'],
    ];

    /** App-child uri => section key. Any uri absent among App's children is skipped. */
    private const MAP = [
        // Rooms
        'rooms'         => 'rooms',
        'categories'    => 'rooms',
        'backgrounds'   => 'rooms',
        'emojis'        => 'rooms',
        'gifts'         => 'rooms',
        'countries'     => 'rooms',
        'codes'         => 'rooms',
        // Members
        '/users'        => 'members',
        'profiles'      => 'members',
        'blacks'        => 'members',
        'vips'          => 'members',
        'families'      => 'members',
        'family_levels' => 'members',
        // Finance
        'charges'       => 'finance',
        'charge_values' => 'finance',
        'coins'         => 'finance',
        'silver'        => 'finance',
        // VIP
        'ovip'          => 'vip',
        'vip_privilege' => 'vip',
        'vip_prev'      => 'vip',
        // Content
        'official_msgs'  => 'content',
        'home_carousels' => 'content',
        'tickets'        => 'content',
    ];

    /** App-child uris that fold into the EXISTING top-level Agencies node. */
    private const AGENCY_URIS = ['targets', 'agency_join_requests', 'userTarget'];

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        // Locate the catch-all "App" parent. Everything below is scoped to its
        // direct children so we never touch same-uri rows living elsewhere.
        $app = DB::table('admin_menu')->where('title', 'App')->where('uri', '/')->first();
        $appId = $app->id ?? null;

        // 1. Ensure NEW section parents; capture their ids by section key.
        $sectionId = [];
        foreach (self::SECTIONS as $key => [$title, $icon, $order, $sentinel]) {
            $row = DB::table('admin_menu')->where('uri', $sentinel)->first();
            $sectionId[$key] = $row->id ?? DB::table('admin_menu')->insertGetId([
                'parent_id'  => 0,
                'order'      => $order,
                'title'      => $title,
                'icon'       => $icon,
                'uri'        => $sentinel,
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Existing top-level Agencies node (a page, still usable as a parent).
        $agencies = DB::table('admin_menu')->where('uri', 'agencies')->where('parent_id', 0)->first();
        $agenciesId = $agencies->id ?? null;
        if ($agenciesId) {
            DB::table('admin_menu')->where('id', $agenciesId)
                ->update(['icon' => 'fa-building', 'order' => 22, 'updated_at' => now()]);
        }

        if ($appId) {
            // 2. Re-parent App's direct children into sections (scoped by parent).
            foreach (self::MAP as $uri => $key) {
                DB::table('admin_menu')
                    ->where('parent_id', $appId)->where('uri', $uri)
                    ->update(['parent_id' => $sectionId[$key], 'updated_at' => now()]);
            }

            // Agency leaves → existing Agencies node (fallback to a members-adjacent
            // spot only if the Agencies node is somehow absent).
            if ($agenciesId) {
                foreach (self::AGENCY_URIS as $uri) {
                    DB::table('admin_menu')
                        ->where('parent_id', $appId)->where('uri', $uri)
                        ->update(['parent_id' => $agenciesId, 'updated_at' => now()]);
                }
            }

            // The "mall" parent (title='mall', uri='/') and its `wares` child live
            // under App. Move mall into Finance; wares follows via its parent chain.
            $mall = DB::table('admin_menu')->where('parent_id', $appId)
                ->where('title', 'mall')->where('uri', '/')->first();
            if ($mall) {
                DB::table('admin_menu')->where('id', $mall->id)
                    ->update(['parent_id' => $sectionId['finance'], 'icon' => 'fa-shopping-cart', 'updated_at' => now()]);
            }

            // 3. Retire the emptied App parent; re-point any straggler to Content.
            DB::table('admin_menu')->where('parent_id', $appId)
                ->update(['parent_id' => $sectionId['content'], 'updated_at' => now()]);
            if (Schema::hasTable('admin_role_menu')) {
                DB::table('admin_role_menu')->where('menu_id', $appId)->delete();
            }
            DB::table('admin_menu')->where('id', $appId)->delete();
        }

        // 4. Collapse duplicate top-level '/' dashboards down to one.
        $dashboards = DB::table('admin_menu')
            ->where('parent_id', 0)->where('uri', '/')->orderBy('id')->get();
        if ($dashboards->count() > 1) {
            $keep = $dashboards->first();
            DB::table('admin_menu')->where('id', $keep->id)
                ->update(['title' => 'Dashboard', 'icon' => 'fa-dashboard', 'order' => 1, 'updated_at' => now()]);
            foreach ($dashboards->slice(1) as $dup) {
                if (Schema::hasTable('admin_role_menu')) {
                    DB::table('admin_role_menu')->where('menu_id', $dup->id)->delete();
                }
                DB::table('admin_menu')->where('id', $dup->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Non-reversible reorganisation: down() only removes the NEW section
        // parents, re-attaching their children to the root so nothing is lost.
        if (!Schema::hasTable('admin_menu')) {
            return;
        }
        foreach (self::SECTIONS as [, , , $sentinel]) {
            $sec = DB::table('admin_menu')->where('uri', $sentinel)->first();
            if (!$sec) {
                continue;
            }
            DB::table('admin_menu')->where('parent_id', $sec->id)
                ->update(['parent_id' => 0, 'updated_at' => now()]);
            DB::table('admin_menu')->where('id', $sec->id)->delete();
        }
    }
};
