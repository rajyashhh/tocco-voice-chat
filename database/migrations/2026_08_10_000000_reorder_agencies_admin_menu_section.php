<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds/repairs the full Agencies section of the admin sidebar as tracked
 * source, so every fresh client clone inherits it from `php artisan migrate`
 * instead of the untracked AdminMenuRebuildSeeder.
 *
 * ROOT PROBLEM (owner 2026-08-10): the Agencies section as it existed only
 * exposed a flat, badly-grouped list of pages named "managers" five times
 * over, while missing several real concepts the owner actually thinks in:
 * Charge Agencies (وكالات الشحن) had no entry here at all (it only lived
 * buried inside the Charging section), Hosts (المضيفون) had NO entry
 * anywhere in this menu — the live roster page exists and routes
 * (`ag/users`, App\Admin\Controllers\AgencyControllers\UserController,
 * `permission_name = 'hosts'`, grid filtered `is_host = 1`) but was never
 * wired into admin_menu at all — and BD had no Settings entry next to its
 * Users list. This migration re-groups the section into the owner's actual
 * mental model:
 *   1. Agencies              -> the host-agencies list (AgencyController)
 *   2. Charge Agencies       -> the shipping/charge-agencies list
 *                               (AppearChargerAgencyController) — MOVED here
 *                               from the Charging section, its natural home
 *   2b. Shipping Super Admin -> the coin-only actor between Country Manager and
 *                               Shipping Agencies (ShippingSuperAdminController,
 *                               `shipping-super-admins`); beside Charge Agencies
 *   3. Hosts                 -> `ag/users` (AgencyControllers\UserController),
 *                               the live current-hosts roster. As of 2026-08-10
 *                               it also carries the join/leave history log as a
 *                               "Join History" tab: the old standalone "Joined
 *                               Users" leaf (users-joined-agencies) was merged in
 *                               and RETIRED from the sidebar (see
 *                               RETIRED_MANAGER_URIS) — same data, one page, no
 *                               duplicate empty screen.
 *   4. BD Super Admin        -> retitled from "BD Users" (BdController)
 *      Super Admin Settings -> BdSelectController, retitled 2026-08-10 from
 *                               "BD Super Admin Settings": it now hosts the
 *                               "Freeze Wallet" switch for BOTH the BD (dollar)
 *                               and Shipping Super Admin (coin) layers. Same uri
 *                               `usersBd-settings` so role bindings survive.
 *   5. Agency Settings       -> AgencySettingsController
 *   6. Targets / User Targets -> catalog (TargetController) + per-host
 *                               achievement report (UserTargetController)
 *   7. Join Requests         -> AgencyJoinRequestController
 *   8. Manager Types (manger-types) — a central user-rank system, kept. The
 *      "Agency Manager" family (Agency Managers, Change Agency Manager, Manager
 *      Agencies, Manager Targets, Manager Users) was RETIRED from the sidebar
 *      (owner 2026-08-10): surface only — routes/controllers/columns stay live
 *      for the AreaManager/SuperAdmin modules. See RETIRED_MANAGER_URIS.
 *
 * NOTE — "Charge Super Admin": the owner's candidate concept has no
 * corresponding admin_menu page. Every "manager" tool listed above
 * (ChangeAgencyMangerController, AgencyMangerAgencyesController,
 * AgencyMangerTaregetController) operates on the `Agency` model, which
 * carries a global `HostAgencyScope` (type=1) — they only ever manage HOST
 * agencies. `AppearChargerAgencyController` (the Charge Agencies page) does
 * not expose `agency_manger_id` for admin editing at all. There is
 * currently no charge/shipping-agency equivalent of a manager-reassignment
 * screen anywhere in the codebase, so none is added here — inventing one
 * would violate "never add a menu entry that doesn't point to a real page."
 *
 * WHAT IT DOES (idempotent, additive — never truncates, never touches other
 * sections' unrelated rows):
 *   1. Find-or-create ONE top-level Agencies section container (uri = NULL,
 *      icon fa-building). If the legacy top-level `agencies` PAGE node
 *      exists, it is re-purposed into the container by reusing its row id
 *      (so any admin_role_menu bindings on it survive), demoting the
 *      clickable page to the section's first child.
 *   2. Ensure every LEAVES entry exists under that container in the owner's
 *      order, and retire the RETIRED_MANAGER_URIS leaves from this section.
 *      Every leaf is matched GLOBALLY by uri (any current parent) and
 *      re-parented in place — ids preserved, so admin_role_menu bindings
 *      survive even for `charge-agencies`, which is being relocated out of
 *      the Charging section. The single exception is `userTarget`, which
 *      ALSO exists under the separate agency-panel menu (parent_id = the
 *      'agency' treeview, a distinct system entirely) and must stay scoped
 *      to rows already inside the agency section — the same gotcha the
 *      2026_08_06 migration documented.
 *   3. Existing rows are re-parented + re-ordered + re-titled in place (ids
 *      preserved); missing rows are created. Re-running is a no-op.
 *
 * Deliberately does NOT touch admin_role_menu: a client may have bound
 * agency items to limited roles from the Roles UI, and every id we reuse
 * stays stable, so those bindings keep working. Super-admin visibility is
 * governed by the `*` permission independently.
 */
return new class extends Migration
{
    /** icon for the section container. */
    private const SECTION_ICON = 'fa-building';

    /**
     * Ordered leaves: title => uri. Order is the array order (1..15). Titles
     * are the English keys; Arabic/English labels come from
     * resources/lang/{ar,en}.json. Mirrors AdminMenuRebuildSeeder::tree()
     * Agencies — that array is the single source of truth this must match.
     */
    private const LEAVES = [
        'Agencies'                  => 'agencies',
        'Charge Agencies'           => 'charge-agencies',
        'Shipping Super Admin'      => 'shipping-super-admins',
        'Hosts'                     => 'ag/users',
        'BD Super Admin'            => 'usersBd',
        'Super Admin Settings'      => 'usersBd-settings',
        'Agency Settings'           => 'agency-settings',
        'Targets'                   => 'targets',
        'User Targets'              => 'userTarget',
        'Join Requests'             => 'agency_join_requests',
        'Manager Types'             => 'manger-types',
    ];

    /**
     * Manager-family leaves retired from the sidebar (owner 2026-08-10). Surface
     * only — their routes/controllers/columns stay live for the AreaManager and
     * SuperAdmin modules. `manger-types` is a separate central user-rank system
     * and is intentionally NOT in this list. Deleting the admin_menu row leaves
     * any admin_role_menu binding orphaned, which is harmless (the sidebar renders
     * only existing rows) and matches the seeder's removeFromSection behavior.
     */
    private const RETIRED_MANAGER_URIS = [
        'agencies/managers',
        'change_agencies_manger',
        'agencies-agency-manger',
        'agencies-tareget-manger',
        'agency-manger-users',
        // 2026-08-10 owner: the standalone "Joined Users" (join/leave audit log)
        // page was merged into the Hosts page as a "Join History" tab. Surface
        // retired here; the controller/route stay live only so the tab's grid can
        // render — the sidebar leaf is dropped to kill the duplicate empty page.
        'users-joined-agencies',
    ];

    /**
     * uri => true for leaves whose match MUST stay scoped to the agency
     * section (they collide with a same-uri row in an unrelated menu tree).
     * Every other uri is matched globally, which is what lets
     * `charge-agencies` migrate out of the Charging section instead of
     * spawning a duplicate row there.
     */
    private const SCOPED_URIS = [
        'userTarget' => true,
    ];

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        // Legacy 08-06 state: a top-level clickable Agencies PAGE node.
        $legacy = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Agencies')
            ->where('uri', 'agencies')
            ->first();
        $legacyId = $legacy->id ?? null;

        // Prefer an already-built section container (uri IS NULL). Else re-purpose
        // the legacy page node into the container (reuse id -> role_menu safe).
        $section = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Agencies')
            ->whereNull('uri')
            ->first();

        if ($section) {
            $sectionId = $section->id;
        } elseif ($legacyId) {
            DB::table('admin_menu')->where('id', $legacyId)->update([
                'uri'        => null,
                'icon'       => self::SECTION_ICON,
                'updated_at' => now(),
            ]);
            $sectionId = $legacyId;
            // The container no longer doubles as the clickable page; a dedicated
            // `agencies` child is (re)created in the leaf loop below.
            $legacyId = null;
        } else {
            // Fresh install: no agency menu at all. Sit the section where 08-06
            // placed Agencies (order 22) so it lands among the app sections.
            $sectionId = DB::table('admin_menu')->insertGetId([
                'parent_id'  => 0,
                'order'      => 22,
                'title'      => 'Agencies',
                'icon'       => self::SECTION_ICON,
                'uri'        => null,
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Candidate parents whose children legitimately belong to this section,
        // used only for uris in SCOPED_URIS (currently just `userTarget`, which
        // also lives under the separate agency-panel menu and must not be moved).
        $scopedParents = array_values(array_filter([$sectionId, $legacyId], fn ($v) => $v !== null));

        $order = 1;
        foreach (self::LEAVES as $title => $uri) {
            $query = DB::table('admin_menu')->where('uri', $uri);

            if (isset(self::SCOPED_URIS[$uri])) {
                $query->where(function ($q) use ($scopedParents, $legacyId) {
                    $q->whereIn('parent_id', $scopedParents);
                    if ($legacyId !== null) {
                        $q->orWhere('id', $legacyId);
                    }
                });
            }

            $row = $query->orderBy('id')->first();

            if ($row) {
                DB::table('admin_menu')->where('id', $row->id)->update([
                    'parent_id'  => $sectionId,
                    'order'      => $order,
                    'title'      => $title,
                    'icon'       => '•',
                    'updated_at' => now(),
                ]);
                // Drop any duplicate rows for this uri already inside the section
                // (keeps a single canonical leaf; ids of others were never the
                // role-bound page node).
                DB::table('admin_menu')
                    ->where('uri', $uri)
                    ->where('parent_id', $sectionId)
                    ->where('id', '!=', $row->id)
                    ->delete();
            } else {
                DB::table('admin_menu')->insert([
                    'parent_id'  => $sectionId,
                    'order'      => $order,
                    'title'      => $title,
                    'icon'       => '•',
                    'uri'        => $uri,
                    'permission' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $order++;
        }

        // Retire the manager-family leaves from THIS section only (scoped by
        // parent_id, so an identically-named leaf in an unrelated tree is never
        // touched). Deleting the menu row hides the surface; the underlying
        // routes/controllers stay live for the AreaManager/SuperAdmin modules.
        DB::table('admin_menu')
            ->where('parent_id', $sectionId)
            ->whereIn('uri', self::RETIRED_MANAGER_URIS)
            ->delete();
    }

    public function down(): void
    {
        // Structural, non-destructive: detach the section's leaves to the root
        // rather than deleting agency pages the admin may rely on.
        if (!Schema::hasTable('admin_menu')) {
            return;
        }
        $section = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where('title', 'Agencies')
            ->whereNull('uri')
            ->first();
        if (!$section) {
            return;
        }
        DB::table('admin_menu')->where('parent_id', $section->id)
            ->update(['parent_id' => 0, 'updated_at' => now()]);
        DB::table('admin_menu')->where('id', $section->id)->delete();
    }
};
