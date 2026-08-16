<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner 2026-08-13: the sidebar showed duplicated / non-opening sections
 * (VIP twice, Content twice, plus empty Rooms/Members/Finance nodes).
 *
 * ROOT CAUSE: migration 2026_08_06_100000_reorganize_admin_menu_sections was
 * written for a layout whose pages all lived under one catch-all "App" parent
 * (title='App', uri='/'). It creates five section sentinels
 * (#section-rooms/members/finance/vip/content) and only fills them INSIDE
 * `if ($appId)`. On installs that never had an "App" parent (this install /
 * sale layout, where pages already sit under real parents like VIP, Rooms &
 * Live, Content), $appId is null, the whole re-parent block is skipped, and the
 * five sentinels are left as empty root nodes — duplicating the existing VIP /
 * Content sections and rendering as dead, non-expanding items.
 *
 * This migration removes any of those section sentinels that ended up EMPTY
 * (no children), and drops a legacy empty 'Content' root (uri null, no
 * children) left beside its populated counterpart. It also relocates the lone
 * 'Dev Dashboard' (/dev) leaf into the existing 'Other' section and retires the
 * now-empty standalone 'Developer' parent, per the owner's requested ordering.
 *
 * Strictly conservative: only nodes with ZERO children are touched, so a layout
 * where the reorg DID populate the sentinels is left completely intact. Role
 * bindings for the removed rows are cleaned up alongside them.
 *
 * Idempotent: re-running finds nothing empty to delete and the dev leaf already
 * moved, so it is a no-op.
 */
return new class extends Migration
{
    /** Section sentinels seeded by the reorg migration. */
    private const SECTION_SENTINELS = [
        '#section-rooms',
        '#section-members',
        '#section-finance',
        '#section-vip',
        '#section-content',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        // 1. Delete section sentinels that were left empty (reorg skipped them).
        foreach (self::SECTION_SENTINELS as $uri) {
            $this->deleteIfEmpty(
                DB::table('admin_menu')->where('uri', $uri)->where('parent_id', 0)->pluck('id')->all()
            );
        }

        // 2. Drop a legacy empty 'Content' root (uri null) sitting beside the
        //    populated Content — but only if it truly has no children.
        $emptyContent = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Content')
            ->whereNull('uri')
            ->pluck('id')
            ->filter(fn ($id) => DB::table('admin_menu')->where('parent_id', $id)->count() === 0)
            ->all();
        $this->deleteIfEmpty($emptyContent);

        // 3. Move the lone 'Dev Dashboard' (/dev) leaf into 'Other', then retire
        //    the now-empty standalone 'Developer' parent.
        $other = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Other')
            ->whereNull('uri')
            ->first();

        $developer = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Developer')
            ->whereNull('uri')
            ->first();

        if ($other && $developer) {
            $devLeaf = DB::table('admin_menu')
                ->where('parent_id', $developer->id)
                ->where('uri', '/dev')
                ->first();

            if ($devLeaf) {
                $lastOrder = (int) DB::table('admin_menu')->where('parent_id', $other->id)->max('order');
                DB::table('admin_menu')->where('id', $devLeaf->id)->update([
                    'parent_id'  => $other->id,
                    'order'      => $lastOrder + 1,
                    'updated_at' => now(),
                ]);
            }

            // Retire the Developer parent only if it is now empty.
            $this->deleteIfEmpty([$developer->id]);
        }
    }

    /**
     * Delete the given menu ids together with their role bindings, but ONLY the
     * ones that currently have no children (fail-safe against wiping a populated
     * node).
     */
    private function deleteIfEmpty(array $ids): void
    {
        foreach ($ids as $id) {
            if (DB::table('admin_menu')->where('parent_id', $id)->count() > 0) {
                continue;
            }
            if (Schema::hasTable('admin_role_menu')) {
                DB::table('admin_role_menu')->where('menu_id', $id)->delete();
            }
            DB::table('admin_menu')->where('id', $id)->delete();
        }
    }

    public function down(): void
    {
        // Non-reversible: the removed nodes were empty duplicates with no data to
        // restore, and the dev leaf relocation is a harmless reordering.
    }
};
