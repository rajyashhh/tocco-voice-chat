<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Owner 2026-08-11 admin-menu cleanup + restructure. Runs on the LIVE menu,
 * where several rows were hand-created through the auth/menu panel and sit
 * OUTSIDE the Agencies section — so the 2026_08_10 reorder migration (which
 * scopes deletes to parent_id = agencies section) could not reach them. This
 * migration finishes the job with GLOBAL, uri-based operations.
 *
 * Idempotent + defensive: every step matches by uri, no-ops when a row is
 * already in its target state, and logs+skips (never throws) when the live
 * structure differs from what is expected.
 *
 * WHAT IT DOES:
 *   1. GLOBAL delete (no parent constraint) of orphaned/retired menu leaves,
 *      plus their admin_role_menu bindings (menu_id), to avoid orphan rows:
 *        - users-joined-agencies : merged into Hosts as a "Join History" tab;
 *                                  route disabled, sidebar leaf must die.
 *        - shippingAdmin/wallet  : a wrong hand-made leaf (no such route); the
 *                                  real page is `shipping-super-admins`.
 *        - the 5 "Agency Manager" family leaves (pages deleted in this same PR).
 *   2. Seed/relocate "Shipping Super Admin" (uri shipping-super-admins) INSIDE
 *      the Agencies section, ordered directly AFTER "BD Super Admin" (usersBd) —
 *      owner's explicit placement.
 *   3. Promote three room/host features to their own top-level sections
 *      (Room Boom, Host Level, Room Cup). Their menu rows were hand-made and
 *      have no seeder/migration source, so we reuse existing ids where possible
 *      (role_menu-safe) and only UPDATE parent_id/order — never drop+recreate.
 */
return new class extends Migration
{
    /** Retired leaves deleted globally (uri, wherever they live). */
    private const RETIRED_URIS = [
        'users-joined-agencies',
        'shippingAdmin/wallet',
        'agencies/managers',
        'change_agencies_manger',
        'agencies-agency-manger',
        'agency-manger-users',
        'agencies-tareget-manger',
    ];

    /**
     * Root sections to build from hand-made rows. `exact` uris anchor the
     * current parent; `like` patterns sweep sub-pages (rewards/theme) into the
     * section too. Order is appended at the menu tail (owner reorders freely).
     */
    private const ROOT_FEATURES = [
        [
            'title' => 'Room Boom',
            'icon'  => 'fa-bomb',
            'exact' => ['room_boom_levels', 'super-boom-rules', 'room_boom_winners', 'room-boom-settings'],
            'like'  => ['room_boom_rewards%', 'room_boom-theme%'],
        ],
        [
            'title' => 'Host Level',
            'icon'  => 'fa-signal',
            'exact' => ['host-levels', 'host-level-settings'],
            'like'  => ['host-level-reward%'],
        ],
        [
            'title' => 'Room Cup',
            'icon'  => 'fa-trophy',
            'exact' => ['room-cup-target', 'room-cup-settings', 'room-cup-reports', 'cup-targets-view'],
            'like'  => [],
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        $this->deleteRetiredLeaves();
        $this->placeShippingSuperAdmin();

        foreach (self::ROOT_FEATURES as $feature) {
            $this->promoteRootSection($feature);
        }
    }

    /** Delete retired leaves globally + drop their role bindings. */
    private function deleteRetiredLeaves(): void
    {
        $ids = DB::table('admin_menu')
            ->whereIn('uri', self::RETIRED_URIS)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->whereIn('menu_id', $ids)->delete();
        }
        DB::table('admin_menu')->whereIn('id', $ids)->delete();
    }

    /**
     * Ensure "Shipping Super Admin" sits inside the Agencies section directly
     * after "BD Super Admin" (usersBd). Idempotent: no-ops once positioned.
     */
    private function placeShippingSuperAdmin(): void
    {
        $section = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Agencies')
            ->whereNull('uri')
            ->first();

        if (!$section) {
            Log::warning('[menu-restructure] Agencies section not found; skipped Shipping Super Admin placement.');
            return;
        }

        $bd = DB::table('admin_menu')
            ->where('parent_id', $section->id)
            ->where('uri', 'usersBd')
            ->first();

        if (!$bd) {
            Log::warning('[menu-restructure] BD Super Admin (usersBd) not in Agencies section; appended Shipping Super Admin at section tail.');
        }

        $target = $bd ? ((int) $bd->order + 1) : ((int) DB::table('admin_menu')->where('parent_id', $section->id)->max('order') + 1);

        // Drop any duplicate shipping rows, keep the lowest id (role-bound one).
        $shippingRows = DB::table('admin_menu')->where('uri', 'shipping-super-admins')->orderBy('id')->get();
        $keep = $shippingRows->first();
        if ($shippingRows->count() > 1) {
            $dupIds = $shippingRows->slice(1)->pluck('id');
            if (Schema::hasTable('admin_role_menu')) {
                DB::table('admin_role_menu')->whereIn('menu_id', $dupIds)->delete();
            }
            DB::table('admin_menu')->whereIn('id', $dupIds)->delete();
        }

        // Already correctly positioned -> idempotent no-op.
        if ($keep && (int) $keep->parent_id === (int) $section->id && (int) $keep->order === $target) {
            return;
        }

        // Open a one-slot gap at $target inside the section (except the row itself).
        DB::table('admin_menu')
            ->where('parent_id', $section->id)
            ->where('order', '>=', $target)
            ->when($keep, fn ($q) => $q->where('id', '!=', $keep->id))
            ->update(['order' => DB::raw('`order` + 1')]);

        if ($keep) {
            DB::table('admin_menu')->where('id', $keep->id)->update([
                'parent_id'  => $section->id,
                'order'      => $target,
                'title'      => 'Shipping Super Admin',
                'icon'       => '•',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('admin_menu')->insert([
                'parent_id'  => $section->id,
                'order'      => $target,
                'title'      => 'Shipping Super Admin',
                'icon'       => '•',
                'uri'        => 'shipping-super-admins',
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Promote one feature to a top-level section, reusing an existing dedicated
     * parent node (id preserved -> role_menu safe) when one exists, else
     * creating a fresh root section. Only the feature's own leaves are moved,
     * so a shared parent (e.g. Rooms & Live) never drags unrelated siblings.
     */
    private function promoteRootSection(array $feature): void
    {
        $childQuery = DB::table('admin_menu')->where(function ($q) use ($feature) {
            $q->whereIn('uri', $feature['exact']);
            foreach ($feature['like'] as $pattern) {
                $q->orWhere('uri', 'like', $pattern);
            }
        });

        $children = $childQuery->get();

        if ($children->isEmpty()) {
            Log::warning("[menu-restructure] No menu rows for '{$feature['title']}'; skipped (feature not present on this install).");
            return;
        }

        // Prefer an existing section node by title (covers re-runs: already root).
        $section = DB::table('admin_menu')
            ->where('title', $feature['title'])
            ->whereNull('uri')
            ->first();

        // Else, if all children share ONE parent that is itself a container
        // (uri IS NULL) holding only these children, reuse it (retitle/promote).
        if (!$section) {
            $parentIds = $children->pluck('parent_id')->unique();
            if ($parentIds->count() === 1 && (int) $parentIds->first() !== 0) {
                $cand = DB::table('admin_menu')->where('id', $parentIds->first())->whereNull('uri')->first();
                if ($cand) {
                    $siblingCount = DB::table('admin_menu')->where('parent_id', $cand->id)->count();
                    if ($siblingCount === $children->count()) {
                        $section = $cand;
                    }
                }
            }
        }

        if ($section) {
            $sectionId = $section->id;
            $update = ['updated_at' => now()];
            if ((int) $section->parent_id !== 0 || $section->uri !== null) {
                $update['parent_id'] = 0;
                $update['uri'] = null;
                $update['title'] = $feature['title'];
                $update['icon'] = $feature['icon'];
                $update['order'] = (int) DB::table('admin_menu')->max('order') + 1;
            }
            DB::table('admin_menu')->where('id', $sectionId)->update($update);
        } else {
            $sectionId = DB::table('admin_menu')->insertGetId([
                'parent_id'  => 0,
                'order'      => (int) DB::table('admin_menu')->max('order') + 1,
                'title'      => $feature['title'],
                'icon'       => $feature['icon'],
                'uri'        => null,
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Re-parent the feature's leaves under the section, ids preserved.
        $order = 1;
        foreach ($children as $child) {
            if ((int) $child->parent_id === (int) $sectionId && (int) $child->id === (int) $sectionId) {
                continue; // never parent the section to itself
            }
            if ((int) $child->id === (int) $sectionId) {
                continue;
            }
            DB::table('admin_menu')->where('id', $child->id)->update([
                'parent_id'  => $sectionId,
                'order'      => $order++,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Structural/data migration: down() is intentionally a no-op. The deleted
     * rows were duplicates/dead leaves and manually-created menu data with no
     * canonical source to restore; re-inserting them would risk corrupting a
     * hand-tuned live sidebar. Menu state is data, not schema.
     */
    public function down(): void
    {
        Log::info('[menu-restructure] down() is a no-op; menu rows are data, not reverted.');
    }
};
