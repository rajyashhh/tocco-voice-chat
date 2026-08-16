<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrective migration: the earlier add-games-access-settings migration looked for a
 * sibling uri "game-settings" which does not exist, so the row landed at parent_id=0
 * with a null permission and the custom RTL menu renderer hid it (empty permission +
 * check_menu_roles). This re-parents it under the real Games section (sibling uri
 * "all-games"), copies that sibling's permission, and clones its role bindings so it
 * shows for the same admins. Idempotent.
 */
return new class extends Migration
{
    private const URI = 'games-access-settings';
    private const SIBLING_URI = 'all-games';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        $item    = DB::table('admin_menu')->where('uri', self::URI)->first();
        $sibling = DB::table('admin_menu')->where('uri', self::SIBLING_URI)->first();

        if (!$item || !$sibling) {
            return;
        }

        // Only fix if it is mis-parented / has no permission (don't clobber a good state).
        if ((int) $item->parent_id !== (int) $sibling->parent_id || empty($item->permission)) {
            DB::table('admin_menu')->where('id', $item->id)->update([
                'parent_id'  => $sibling->parent_id,
                'order'      => ($sibling->order ?? 0) + 1,
                'permission' => $sibling->permission,
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('admin_role_menu')) {
            $roleIds = DB::table('admin_role_menu')->where('menu_id', $sibling->id)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                $exists = DB::table('admin_role_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $item->id)
                    ->exists();
                if (!$exists) {
                    DB::table('admin_role_menu')->insert([
                        'role_id'    => $roleId,
                        'menu_id'    => $item->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: re-parenting fix is not reverted.
    }
};
