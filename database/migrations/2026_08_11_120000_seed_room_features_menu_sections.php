<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Owner 2026-08-11: Room Boom / Host Level / Room Cup are shipped, enabled
 * modules (Modules/RoomBoom, Modules/HostLevel, Modules/RoomCup — all true in
 * modules_statuses.json, controllers + web routes present), but on the live
 * this install may have NO admin_menu rows at all. The prior restructure
 * migration (2026_08_11_100000) only PROMOTED pre-existing hand-made rows via
 * UPDATE, so with nothing to promote it silently skipped them. This migration
 * SEEDS the three sections as independent root nodes (parent_id = 0) with their
 * real leaves, then binds every new row to the super-admin roles so it renders.
 *
 * Only routable index pages are seeded. The reward/theme sub-pages
 * (room_boom_rewards/{id}, room_boom-theme/{id}, host-level-reward/{id}) are
 * PARAMETERISED routes with no bare URL — a sidebar leaf pointing at them would
 * 404 (the trap that produced the dead "Joined Users" leaf), so they are opened
 * from inside a level, never from the sidebar, and are deliberately excluded.
 *
 * Idempotent: sections matched by title, leaves matched by uri, role bindings
 * matched by (role_id, menu_id). Re-running is a no-op.
 */
return new class extends Migration
{
    /**
     * uri => title. URIs verified against the module web route files:
     *   Modules/RoomBoom/Routes/web.php, Modules/HostLevel/Routes/web.php,
     *   Modules/RoomCup/Routes/web.php.
     */
    private const SECTIONS = [
        [
            'title'    => 'Room Boom',
            'icon'     => 'fa-bomb',
            'children' => [
                'room_boom_levels'   => 'Room Boom Levels',
                'super-boom-rules'   => 'Super Boom Rules',
                'room_boom_winners'  => 'Room Boom Winners',
                'room-boom-settings' => 'Room Boom Settings',
            ],
        ],
        [
            'title'    => 'Host Level',
            'icon'     => 'fa-signal',
            'children' => [
                'host-levels'         => 'Host Levels',
                'host-level-settings' => 'Host Level Settings',
            ],
        ],
        [
            'title'    => 'Room Cup',
            'icon'     => 'fa-trophy',
            'children' => [
                'room-cup-target'   => 'Room Cup Targets',
                'room-cup-settings' => 'Room Cup Settings',
                'room-cup-reports'  => 'Room Cup Reports',
                // cup-targets-view removed 2026-08-12: that route lives OUTSIDE
                // the admin prefix (Modules/RoomCup/Routes/web.php tail — the
                // app-facing webview of the targets table), so the sidebar leaf
                // admin/cup-targets-view could only 404. Same trap documented
                // above for parameterised routes. AdminMenuRebuildSeeder drops
                // the leaf from installs that already seeded it.
            ],
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        $superAdminRoleIds = $this->superAdminRoleIds();

        foreach (self::SECTIONS as $section) {
            $this->seedSection($section, $superAdminRoleIds);
        }
    }

    /**
     * Roles that effectively act as super admin: whoever holds the '*'
     * permission (dynamic — never hardcode ids). Falls back to the canonical
     * developer/admin slugs if the permission table is unavailable.
     */
    private function superAdminRoleIds(): array
    {
        $ids = [];

        if (Schema::hasTable('admin_role_permissions') && Schema::hasTable('admin_permissions')) {
            $ids = DB::table('admin_role_permissions as rp')
                ->join('admin_permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('p.slug', '*')
                ->pluck('rp.role_id')
                ->all();
        }

        if (empty($ids) && Schema::hasTable('admin_roles')) {
            $ids = DB::table('admin_roles')
                ->whereIn('slug', ['developer', 'admin', 'administrator'])
                ->pluck('id')
                ->all();
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function seedSection(array $section, array $roleIds): void
    {
        $root = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', $section['title'])
            ->whereNull('uri')
            ->first();

        if ($root) {
            $rootId = $root->id;
        } else {
            $rootId = DB::table('admin_menu')->insertGetId([
                'parent_id'  => 0,
                'order'      => (int) DB::table('admin_menu')->max('order') + 1,
                'title'      => $section['title'],
                'icon'       => $section['icon'],
                'uri'        => null,
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->bindRoles($rootId, $roleIds);

        $childOrder = (int) DB::table('admin_menu')->where('parent_id', $rootId)->max('order');

        foreach ($section['children'] as $uri => $title) {
            $existing = DB::table('admin_menu')->where('uri', $uri)->first();

            if ($existing) {
                // A row for this feature already exists (e.g. hand-made under
                // another parent). Do not duplicate; just make sure the
                // super-admin roles can see it.
                $this->bindRoles($existing->id, $roleIds);
                continue;
            }

            $childId = DB::table('admin_menu')->insertGetId([
                'parent_id'  => $rootId,
                'order'      => ++$childOrder,
                'title'      => $title,
                'icon'       => '•',
                'uri'        => $uri,
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->bindRoles($childId, $roleIds);
        }
    }

    private function bindRoles(int $menuId, array $roleIds): void
    {
        if (empty($roleIds) || !Schema::hasTable('admin_role_menu')) {
            if (empty($roleIds)) {
                Log::warning("[seed-room-features] No super-admin role resolved; menu {$menuId} left unbound.");
            }
            return;
        }

        foreach ($roleIds as $roleId) {
            $exists = DB::table('admin_role_menu')
                ->where('role_id', $roleId)
                ->where('menu_id', $menuId)
                ->exists();

            if (!$exists) {
                DB::table('admin_role_menu')->insert([
                    'role_id'    => $roleId,
                    'menu_id'    => $menuId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        foreach (self::SECTIONS as $section) {
            $childUris = array_keys($section['children']);

            $childIds = DB::table('admin_menu')->whereIn('uri', $childUris)->pluck('id');

            $rootId = DB::table('admin_menu')
                ->where('parent_id', 0)
                ->where('title', $section['title'])
                ->whereNull('uri')
                ->value('id');

            $menuIds = $childIds->all();
            if ($rootId) {
                $menuIds[] = $rootId;
            }

            if (!empty($menuIds)) {
                if (Schema::hasTable('admin_role_menu')) {
                    DB::table('admin_role_menu')->whereIn('menu_id', $menuIds)->delete();
                }
                DB::table('admin_menu')->whereIn('id', $menuIds)->delete();
            }
        }
    }
};
