<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the admin sidebar menu as feature-based sections:
 * every feature (Families, Levels, CP, Agencies, ...) is its own
 * section with all of its pages grouped inside it.
 *
 * Replaces the legacy flat menu (everything dumped under one "App"
 * parent) and drops broken entries (percentage-target and ovip have
 * no GET routes; duplicate Dashboard roots).
 *
 * Guarded: skips silently when the new structure is already present
 * (marker = a "Games" section row with no uri), so it is safe inside
 * DatabaseSeeder on re-runs. Run standalone to force a rebuild after
 * deleting the marker row.
 */
class AdminMenuRebuildSeeder extends Seeder
{
    public function run()
    {
        $marker = DB::table('admin_menu')
            ->where('title', 'Games')
            ->whereNull('uri')
            ->exists();

        if ($marker) {
            // Menu already rebuilt on this install/demo: don't truncate or touch
            // anything else, just make sure newer sections (added to tree()
            // after that rebuild already ran) exist too.
            $this->ensureSection(self::eventsSection());
            $this->ensureSection(self::adsSection());
            $this->removeFromSection('Content', array_values(self::adsSection()['children']));
            // Sit Ads right next to Content (same order, later id sorts after)
            // instead of buried at the sidebar tail where ensureSection appends.
            $contentOrder = DB::table('admin_menu')
                ->where('title', 'Content')->whereNull('uri')->value('order');
            if ($contentOrder !== null) {
                DB::table('admin_menu')
                    ->where('title', 'Ads')->whereNull('uri')
                    ->update(['order' => $contentOrder]);
            }

            // 2026-08 reorganization (owner plan, phase B):
            // user-scoped settings live with Users, not buried in Settings.
            $this->moveToSection('Users', ['users-settings']);

            // Owner 2026-08-09, Users section cleanup: dead pages get their code
            // deleted (controllers/routes/views) — here we drop their now-orphan
            // menu rows. Historical table data is kept; only the admin surfaces go.
            //   - profiles: duplicate of the avatar view already on the users page
            //   - special-id-requests: cancelled feature
            //   - country-requests / -history: replaced by immediate country change
            $this->removeFromSection('Users', [
                'profiles',
                'special-id-requests',
                'country-requests',
                'country-request-history',
            ]);
            // Account-deletion REASONS catalog is a policy setting -> Settings.
            $this->moveToSection('Settings', ['delete-accounts']);
            // Invitation/referral program gets its own top-level section; move
            // its settings page out of Users into it.
            $this->ensureSection(self::invitationSection());
            // parent-users (the inviter/invitee log with counts + earnings) was
            // in the legacy menu but fell out of the 08-07 rebuild; ensureSection
            // above back-fills it, moveToSection dedupes any stray legacy row,
            // and the reorder puts Settings after the log (owner 2026-08-12).
            $this->moveToSection('Invitation Code', ['invitation-code/settings', 'parent-users']);
            $this->orderChildAfter('Invitation Code', 'invitation-code/settings', 'parent-users');
            // Offers is an ad surface -> Ads section beside the other banners.
            $this->moveToSection('Ads', ['offers']);
            // FairLuck menu entry is dead weight: the route only redirects to
            // lucky-gift-settings (already in Gifts).
            $this->removeFromSection('Games', ['fairluck']);
            // Games run on the external UTD Games provider: back-fill the player
            // win/loss report (routed but never in the menu) into the Games
            // section on already-rebuilt installs.
            $this->ensureSection(self::gamesSection());
            // 2026-08-12 owner: Winner Rewards (reward-winner-games) and Game
            // Percentages (percentage-games) screens are retired — no in-house
            // interactive games, and per-game tuning lives on the UTD Games
            // provider side. Controllers/routes deleted; drop the leaves here.
            // The reward_winner_games table + rewardMap read stay (provider
            // integration code, separate removal wave).
            $this->removeFromSection('Games', ['reward-winner-games', 'percentage-games']);
            $this->orderChildAfter('Games', 'coin-game-users-reports', 'games-access-settings');
            // 2026-08-12 owner: the play-gating screen lists CONDITIONS (role /
            // level / recharge), not permissions — retitle the leaf on
            // already-rebuilt menus to match gamesSection().
            // 'Games Access' = rebuilt-menu title; 'Games Settings' = the
            // pre-rebuild title from the 2026_06_15 menu migration.
            DB::table('admin_menu')
                ->where('uri', 'games-access-settings')
                ->whereIn('title', ['Games Access', 'Games Settings'])
                ->update(['title' => 'Game Conditions', 'updated_at' => now()]);
            // 2026-08-12: the standalone Game Settings page was merged into the
            // Games Access form (controller/route deleted) — drop its leaf from
            // already-rebuilt menus.
            $this->removeFromSection('Games', ['game-settings']);
            // helpers/* are hard-blocked by StopInProduction on any production
            // install, so they rendered as permanent 403 links. Dev Dashboard
            // (/dev) stays as the single Developer entry.
            $this->removeFromSection('Developer', [
                'helpers/scaffold',
                'helpers/terminal/database',
                'helpers/terminal/artisan',
                'helpers/routes',
            ]);
            // delete-accounts (deletion REASONS catalog) and trashed-users
            // (actual deleted accounts) both translated to the same Arabic
            // label; give the reasons page its own title key.
            DB::table('admin_menu')
                ->where('uri', 'delete-accounts')
                ->where('title', 'Deleted Accounts')
                ->update(['title' => 'Delete Account Reasons', 'updated_at' => now()]);

            // 2026-08-09 Levels cleanup (owner): one page with in-page tabs.
            // The three standalone typed pages + the standalone user-level
            // editor are removed from the menu (routes/controllers deleted in
            // code). Uncertain-but-live pages go to a new "Other" section.
            $this->removeFromSection('Levels', [
                'vips-sender', 'vips-charge', 'charisma-levels', 'levels/users',
            ]);
            $this->ensureSection(self::otherSection());
            // Rename the weekly star entry (owner: it's ONE event — "حدث النجمة
            // الأسبوعية" — not "أحداث النجم الأسبوعي"). Menu stores the English
            // key; the Arabic label comes from ar.json, updated alongside.

            // 2026-08-10 Agencies rework (owner): the section only exposed
            // "managers" duplicated five times over, with Charge Agencies
            // missing entirely (it lived under Charging) and no Hosts entry
            // at all. See the reorder_agencies_admin_menu_section migration
            // docblock for the full concept mapping; this patches an
            // already-rebuilt install to the same end state.
            $this->moveToSection('Agencies', ['charge-agencies']);
            DB::table('admin_menu')
                ->where('uri', 'charge-agencies')
                ->update(['title' => 'Charge Agencies', 'updated_at' => now()]);
            DB::table('admin_menu')
                ->where('uri', 'usersBd')
                ->update(['title' => 'BD Super Admin', 'updated_at' => now()]);
            // 2026-08-10: the settings page now hosts freeze switches for BOTH the
            // BD and Shipping Super Admin layers, so it is retitled from
            // "BD Super Admin Settings" to "Super Admin Settings". Retitle any
            // existing leaf on already-rebuilt menus.
            DB::table('admin_menu')
                ->where('uri', 'usersBd-settings')
                ->update(['title' => 'Super Admin Settings', 'updated_at' => now()]);
            $agencies = DB::table('admin_menu')
                ->where('title', 'Agencies')->whereNull('uri')->first();
            if ($agencies && !DB::table('admin_menu')->where('parent_id', $agencies->id)->where('uri', 'usersBd-settings')->exists()) {
                DB::table('admin_menu')->insert([
                    'parent_id' => $agencies->id,
                    'order' => (int) DB::table('admin_menu')->max('order') + 1,
                    'title' => 'Super Admin Settings',
                    'icon' => '•',
                    'uri' => 'usersBd-settings',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($agencies && !DB::table('admin_menu')->where('parent_id', $agencies->id)->where('uri', 'ag/users')->exists()) {
                DB::table('admin_menu')->insert([
                    'parent_id' => $agencies->id,
                    'order' => (int) DB::table('admin_menu')->max('order') + 1,
                    'title' => 'Hosts',
                    'icon' => '•',
                    'uri' => 'ag/users',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2026-08-10 owner: the shipping super admin layer (coin-only actor
            // between Country Manager and Shipping Agencies) gets a management
            // page beside Charge Agencies. Back-fill it on already-rebuilt menus.
            if ($agencies && !DB::table('admin_menu')->where('parent_id', $agencies->id)->where('uri', 'shipping-super-admins')->exists()) {
                DB::table('admin_menu')->insert([
                    'parent_id' => $agencies->id,
                    'order' => (int) DB::table('admin_menu')->max('order') + 1,
                    'title' => 'Shipping Super Admins',
                    'icon' => '•',
                    'uri' => 'shipping-super-admins',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2026-08-10 owner: retire the "Agency Manager" family from the menu
            // (surface only — routes/controllers/columns stay for the still-live
            // AreaManager/SuperAdmin modules). Manager Types (manger-types) is a
            // separate central user-rank system and is deliberately NOT removed.
            $this->removeFromSection('Agencies', [
                'agencies/managers',
                'change_agencies_manger',
                'agencies-agency-manger',
                'agencies-tareget-manger',
                'agency-manger-users',
                // 2026-08-10 owner: "Joined Users" (join/leave audit log) merged
                // into the Hosts page as a "Join History" tab; drop the duplicate
                // leaf from already-rebuilt menus. Controller/route stay live for
                // the tab grid; only the sidebar surface is retired.
                'users-joined-agencies',
            ]);

            // 2026-08-12 owner: the row seeded as "Home" (uri shippingAdmin,
            // from ShippingSuperAdminRoleSeeder) leaked into the main admin
            // sidebar as a misleading "الصفحة الرئيسية" entry. Its link is dead
            // from every portal (admin_url renders admin/shippingAdmin, which
            // is not a route — the portal lives at /shippingAdmin). The admin
            // surface for this entity is the Agencies leaf shipping-super-admins;
            // drop the stray row (+ role bindings) and retitle that leaf to the
            // plural entity name. Naming per owner: this layer SELLS coins TO
            // the shipping agencies — it is NOT a shipping agency itself.
            $strayIds = DB::table('admin_menu')->whereIn('uri', ['shippingAdmin', 'shippingAdmin/wallet'])->pluck('id');
            if ($strayIds->isNotEmpty()) {
                DB::table('admin_role_menu')->whereIn('menu_id', $strayIds)->delete();
                DB::table('admin_menu')->whereIn('id', $strayIds)->delete();
            }
            DB::table('admin_menu')
                ->where('uri', 'shipping-super-admins')
                ->update(['title' => 'Shipping Super Admins', 'updated_at' => now()]);

            // 2026-08-12 owner: Emojis get their own top-level section right
            // after Content (same trick as Ads above: equal order, later id
            // sorts after). The two pages leave Content.
            $this->ensureSection(self::emojisSection());
            $this->removeFromSection('Content', array_values(self::emojisSection()['children']));
            if ($contentOrder !== null) {
                DB::table('admin_menu')
                    ->where('title', 'Emojis')->whereNull('uri')
                    ->update(['order' => $contentOrder]);
            }

            // 2026-08-12 owner: Notifications section sits beside Settings —
            // the templates page moves out of Content and is joined by the new
            // aggregated notification settings page.
            $this->ensureSection(self::notificationsSection());
            $this->removeFromSection('Content', ['notification-templates']);
            $settingsOrder = DB::table('admin_menu')
                ->where('title', 'Settings')->whereNull('uri')->value('order');
            if ($settingsOrder !== null) {
                DB::table('admin_menu')
                    ->where('title', 'Notifications')->whereNull('uri')
                    ->update(['order' => $settingsOrder]);
            }

            // 2026-08-12 owner: "Admin" becomes "Users & Permissions". The
            // standalone Permissions page leaves the menu (permissions are
            // managed from inside the role create/edit form); Menu moves to
            // Other; Operation Log stays here (owner keeps it in place).
            DB::table('admin_menu')
                ->where('title', 'Admin')->whereNull('uri')
                ->update(['title' => 'Users & Permissions', 'updated_at' => now()]);
            $this->removeFromSection('Users & Permissions', ['auth/permissions']);
            $this->moveToSection('Other', ['auth/menu']);

            // 2026-08-12 owner: Room Boom sits directly under Ads. ensureSection
            // back-fills the section (the 08-11 migration seeded it at the menu
            // tail), moveToSection dedupes any stray rows for its pages, and the
            // order update parks it right after Ads (same order, later id sorts
            // after — same trick as Ads/Content above).
            $this->ensureSection(self::roomBoomSection());
            $this->moveToSection('Room Boom', array_values(self::roomBoomSection()['children']));
            $adsOrder = DB::table('admin_menu')
                ->where('title', 'Ads')->whereNull('uri')->value('order');
            if ($adsOrder !== null) {
                DB::table('admin_menu')
                    ->where('title', 'Room Boom')->whereNull('uri')
                    ->update(['order' => $adsOrder]);
            }

            // 2026-08-12 owner: Room Cup sits directly under Room Boom. Same
            // mechanics as the Room Boom block above (equal order, later id
            // sorts after: Room Boom's row predates Room Cup's on every
            // install, so both at adsOrder land Ads -> Room Boom -> Room Cup).
            // The "Cup Targets View" leaf is dropped: its route
            // (cup-targets-view) is registered OUTSIDE the admin prefix
            // (Modules/RoomCup/Routes/web.php tail) as an app-facing webview,
            // so the sidebar link admin/cup-targets-view always 404s — the
            // same data grid already lives on room-cup-target. Any hand-made
            // duplicate of the reports page is deduped by moveToSection and
            // retitled back to its real name.
            $this->ensureSection(self::roomCupSection());
            $this->moveToSection('Room Cup', array_values(self::roomCupSection()['children']));
            $cupViewIds = DB::table('admin_menu')->where('uri', 'cup-targets-view')->pluck('id');
            if ($cupViewIds->isNotEmpty()) {
                DB::table('admin_role_menu')->whereIn('menu_id', $cupViewIds)->delete();
                DB::table('admin_menu')->whereIn('id', $cupViewIds)->delete();
            }
            DB::table('admin_menu')
                ->where('uri', 'room-cup-reports')
                ->update(['title' => 'Room Cup Reports', 'updated_at' => now()]);
            if ($adsOrder !== null) {
                DB::table('admin_menu')
                    ->where('title', 'Room Cup')->whereNull('uri')
                    ->update(['order' => $adsOrder]);
            }
            return;
        }

        DB::table('admin_menu')->truncate();
        DB::table('admin_role_menu')->truncate();

        $order = 1;
        foreach (self::tree() as $section) {
            $children = $section['children'] ?? [];
            $parentId = DB::table('admin_menu')->insertGetId([
                'parent_id' => 0,
                'order' => $order++,
                'title' => $section['title'],
                'icon' => $section['icon'],
                'uri' => $section['uri'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($children as $title => $uri) {
                DB::table('admin_menu')->insert([
                    'parent_id' => $parentId,
                    'order' => $order++,
                    'title' => $title,
                    'icon' => '•',
                    'uri' => $uri,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Idempotently ensures a single section (parent + children) exists,
     * without touching or reordering anything else in the menu. Used both to
     * patch an already-rebuilt menu and, implicitly, is safe to re-run.
     */
    private function ensureSection(array $section): void
    {
        $parent = DB::table('admin_menu')
            ->where('title', $section['title'])
            ->whereNull('uri')
            ->first();

        if ($parent) {
            $parentId = $parent->id;
        } else {
            $maxOrder = (int) DB::table('admin_menu')->max('order');
            $parentId = DB::table('admin_menu')->insertGetId([
                'parent_id' => 0,
                'order' => $maxOrder + 1,
                'title' => $section['title'],
                'icon' => $section['icon'],
                'uri' => $section['uri'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $maxChildOrder = (int) DB::table('admin_menu')->max('order');
        foreach ($section['children'] ?? [] as $title => $uri) {
            $exists = DB::table('admin_menu')
                ->where('parent_id', $parentId)
                ->where('uri', $uri)
                ->exists();
            if ($exists) {
                continue;
            }
            $maxChildOrder++;
            DB::table('admin_menu')->insert([
                'parent_id' => $parentId,
                'order' => $maxChildOrder,
                'title' => $title,
                'icon' => '•',
                'uri' => $uri,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Re-parents existing menu rows (found by uri, wherever they currently
     * sit) into the named section, keeping their titles. Rows already in the
     * section (or missing entirely) are left untouched. Idempotent.
     */
    private function moveToSection(string $sectionTitle, array $uris): void
    {
        $parent = DB::table('admin_menu')
            ->where('title', $sectionTitle)
            ->whereNull('uri')
            ->first();

        if (!$parent) {
            return;
        }

        foreach ($uris as $uri) {
            $rows = DB::table('admin_menu')->where('uri', $uri)->orderBy('id')->get();
            if ($rows->isEmpty()) {
                continue;
            }
            // Keep a single row for this uri: prefer one already under the
            // target (covers the case where ensureSection() inserted a fresh
            // child there before the old row was moved), drop the rest.
            $keep = $rows->firstWhere('parent_id', $parent->id) ?? $rows->first();
            DB::table('admin_menu')
                ->where('uri', $uri)
                ->where('id', '!=', $keep->id)
                ->delete();
            if ($keep->parent_id != $parent->id) {
                DB::table('admin_menu')
                    ->where('id', $keep->id)
                    ->update([
                        'parent_id' => $parent->id,
                        'order' => (int) DB::table('admin_menu')->max('order') + 1,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Places a child (by uri) directly after a sibling (by uri) inside a named
     * section, so a back-filled entry sits in its intended slot instead of the
     * menu tail where ensureSection() appends it. Idempotent; no-ops if either
     * row is missing. Only touches the moved child's `order`.
     */
    private function orderChildAfter(string $sectionTitle, string $childUri, string $afterUri): void
    {
        $parent = DB::table('admin_menu')
            ->where('title', $sectionTitle)
            ->whereNull('uri')
            ->first();

        if (!$parent) {
            return;
        }

        $after = DB::table('admin_menu')
            ->where('parent_id', $parent->id)
            ->where('uri', $afterUri)
            ->first();
        $child = DB::table('admin_menu')
            ->where('parent_id', $parent->id)
            ->where('uri', $childUri)
            ->first();

        if (!$after || !$child) {
            return;
        }

        $target = (int) $after->order + 1;

        // Open a gap: bump every row at/after the target slot (except the child).
        DB::table('admin_menu')
            ->where('order', '>=', $target)
            ->where('id', '!=', $child->id)
            ->update(['order' => DB::raw('`order` + 1')]);

        DB::table('admin_menu')
            ->where('id', $child->id)
            ->update(['order' => $target, 'updated_at' => now()]);
    }

    /**
     * Removes specific child URIs from a named section (used when items move
     * to their own section — e.g. the ad pages out of Content). Idempotent.
     */
    private function removeFromSection(string $sectionTitle, array $uris): void
    {
        $parent = DB::table('admin_menu')
            ->where('title', $sectionTitle)
            ->whereNull('uri')
            ->first();

        if (!$parent) {
            return;
        }

        DB::table('admin_menu')
            ->where('parent_id', $parent->id)
            ->whereIn('uri', $uris)
            ->delete();
    }

    /**
     * Ads section: every banner surface (in-app carousel banners + their
     * pricing settings + the splash screens + official messages) lives in ONE
     * section, with its settings beside it (owner rule: each section carries
     * its own settings).
     */
    public static function adsSection(): array
    {
        return ['title' => 'Ads', 'icon' => 'fa-bullhorn', 'children' => [
            'Home Carousels' => 'home_carousels',
            'Carousel Settings' => 'home-carousel-settings',
            'Splash' => 'banners',
            'Official Messages' => 'official_msgs',
            'Offers' => 'offers',
        ]];
    }

    /**
     * Room Boom section (owner 2026-08-12): sits directly under Ads. Leaves
     * mirror the 2026_08_11_120000 seed migration — index pages only; the
     * parameterised reward/theme sub-pages (room_boom_rewards/{id},
     * room_boom-theme/{id}) have no bare URL and are opened from inside a
     * level, never from the sidebar. Kept as its own method so it can be
     * added to an already-rebuilt menu via ensureSection().
     */
    public static function roomBoomSection(): array
    {
        return ['title' => 'Room Boom', 'icon' => 'fa-bomb', 'children' => [
            'Room Boom Levels' => 'room_boom_levels',
            'Super Boom Rules' => 'super-boom-rules',
            'Room Boom Winners' => 'room_boom_winners',
            'Room Boom Settings' => 'room-boom-settings',
        ]];
    }

    /**
     * Room Cup section (owner 2026-08-12): sits directly under Room Boom.
     * Index pages only, verified against Modules/RoomCup/Routes/web.php —
     * room-cup-target / room-cup-settings / room-cup-reports are resources
     * inside the admin prefix group. cup-targets-view is deliberately NOT
     * here: it is registered outside that prefix as the app-facing webview
     * of the targets table, so a sidebar leaf (admin/cup-targets-view)
     * can only 404. Kept as its own method so it can be added to an
     * already-rebuilt menu via ensureSection().
     */
    public static function roomCupSection(): array
    {
        return ['title' => 'Room Cup', 'icon' => 'fa-trophy', 'children' => [
            'Room Cup Targets' => 'room-cup-target',
            'Room Cup Settings' => 'room-cup-settings',
            'Room Cup Reports' => 'room-cup-reports',
        ]];
    }

    /**
     * Games section. Model: games run on the external UTD Games provider — the
     * panel only configures the integration (link settings, enable/hide games,
     * play gating, commission % and winner rewards fed to the live provider
     * webhooks) and reads reports. There is NO manual coin charging from here.
     *
     * Kept as its own method (as well as via tree()) so the player report entry
     * can be back-filled into an already-rebuilt menu through ensureSection().
     */
    public static function gamesSection(): array
    {
        // 2026-08-12 cleanup: the standalone "Game Settings" page (single
        // game_map_win_coins field) was absorbed into the Games Access form
        // and its controller/route deleted; the section now lists only the
        // provider-game screens. The built-in chat mini-games (dice/rps/lucky
        // number) are pure client-side visuals with no admin surface at all.
        // 2026-08-12 owner: Winner Rewards + Game Percentages screens retired
        // (no in-house interactive games; per-game tuning lives on the UTD
        // Games provider side) — controllers/routes deleted with them.
        return ['title' => 'Games', 'icon' => 'fa-gamepad', 'children' => [
            'All Games' => 'all-games',
            'Game Conditions' => 'games-access-settings',
            // Owner report: who played / which game / won / lost / when. Was
            // orphaned (routed but absent from the menu); surfaced here.
            'Player Reports' => 'coin-game-users-reports',
            'Game Charge History' => 'game-charge-histories',
        ]];
    }

    /**
     * Emojis section (owner 2026-08-12): the emoji pages leave Content and get
     * their own top-level section right after it. Kept as its own method so it
     * can be added to an already-rebuilt menu via ensureSection().
     */
    public static function emojisSection(): array
    {
        return ['title' => 'Emojis', 'icon' => 'fa-smile-o', 'children' => [
            'Emojis' => 'emojis',
            'Emoji Categories' => 'emoji-categories',
        ]];
    }

    /**
     * Notifications section (owner 2026-08-12): notification admin lives in ONE
     * section beside Settings — the templates page (moved out of Content) plus
     * the aggregated notification settings screen. Kept as its own method so it
     * can be added to an already-rebuilt menu via ensureSection().
     */
    public static function notificationsSection(): array
    {
        return ['title' => 'Notifications', 'icon' => 'fa-bell', 'children' => [
            'Notification Templates' => 'notification-templates',
            'Notification Settings' => 'notification-settings',
        ]];
    }

    /**
     * Invitation Code section (owner 2026-08-09): the invite/referral program
     * gets its own top-level section instead of hanging under Users. Kept as
     * its own method so it can be added to an already-rebuilt menu via
     * ensureSection().
     *
     * Invitations Log (parent-users, ParentUsersController) is the inviter
     * report — inviter card + invited count + total earnings, with drill-downs
     * to the invited users and to each invitee's commission operations. It was
     * a child of this section in the legacy menu (see docs/DASHBOARD_GUIDE.html
     * id:105/pid:249) but was dropped by the 08-07 rebuild; restored 2026-08-12.
     */
    public static function invitationSection(): array
    {
        return ['title' => 'Invitation Code', 'icon' => 'fa-ticket', 'children' => [
            'Invitations Log' => 'parent-users',
            'Invitation Code Settings' => 'invitation-code/settings',
        ]];
    }

    /**
     * "Other" section: parking lot for pages that are NOT confirmed dead but
     * whose correct home is still undecided (owner rule 2026-08-09 — review
     * later, don't delete blindly). Currently holds:
     *  - charisma-levels: a live, separate subsystem (own charisma_levels table
     *    + mobile API, gated by the charisma_badge setting). NOT the "charisma"
     *    tab on the Levels page (that tab is vips.type=1); the owner conflated
     *    the two when he asked to drop it.
     *  - event-period: a live API-backed event page that used to be reachable
     *    only via the (now-removed) events tab bar, and is not part of the
     *    owner's final Events menu.
     */
    public static function otherSection(): array
    {
        return ['title' => 'Other', 'icon' => 'fa-question-circle', 'children' => [
            'Charisma Level' => 'charisma-levels',
            'Event Period' => 'event-period',
            // Owner 2026-08-12: raw menu editor parked here, out of the
            // (renamed) Users & Permissions section. Still routed and working.
            'Menu' => 'auth/menu',
        ]];
    }

    /**
     * Events section definition. Kept as its own method (as well as inline in
     * tree() for the full-rebuild path) so it can be added standalone to an
     * already-rebuilt menu via ensureSection().
     */
    public static function eventsSection(): array
    {
        // Weekly CP moved to the CP section (owner 2026-08-08: all CP admin
        // in one place).
        return ['title' => 'Events', 'icon' => 'fa-star', 'children' => [
            'Weekly Star' => 'weekly-events-new',
            'PK Events' => 'pk-events',
            'Charge Tiers' => 'target-events',
            'Charge King' => 'charge-king',
            'Event Reports' => 'event-reports',
            'Event Rules' => 'general-rols',
        ]];
    }

    /**
     * Section => children (title => uri). URIs verified against
     * app/Admin/routes.php.
     */
    public static function tree(): array
    {
        return [
            ['title' => 'Dashboard', 'icon' => 'fa-bar-chart', 'uri' => '/'],

            ['title' => 'Users', 'icon' => 'fa-users', 'children' => [
                'Users' => 'users',
                'User Statistics' => 'user-statistics',
                'Trashed Users' => 'trashed-users',
                'User Settings' => 'users-settings',
            ]],

            // Levels and VIP are separate products (owner 2026-08-07) — two sections.
            // Owner 2026-08-09: unified onto ONE page. "Level List" (vips) now
            // carries the three level products as in-page tabs (wealth/sender,
            // charisma/receiver, charge). The three standalone typed pages and
            // the standalone "User Levels" editor are gone (levels are edited
            // from the user's profile). Only the list + change history remain.
            ['title' => 'Levels', 'icon' => 'fa-trophy', 'children' => [
                'Level List' => 'vips',
                'Level Change History' => 'change-level-histories',
            ]],

            // vip_privilege (vip_privileges table) is THE privileges page the
            // mobile API reads. The legacy vip_prev page (vip_auth table, dead
            // to the app) was a duplicate menu entry and is deliberately gone.
            // ovip/ovip-settings are routed from Modules/Vip/Routes/web.php
            // (NOT app/Admin/routes.php) — that's why the first rebuild missed
            // them and the "add VIP level 1/2/3" page vanished from the menu.
            ['title' => 'VIP', 'icon' => 'fa-diamond', 'children' => [
                'VIP Levels' => 'ovip',
                'VIP Privileges' => 'vip_privilege',
                'VIP Dedicate' => 'vips_dedicate',
                'VIP Settings' => 'ovip-settings',
            ]],

            // Owner 2026-08-08: full CP control lives here — levels, the four
            // relation cards (cp_relations CRUD), active relations/requests
            // (cps grid), Weekly CP and CP settings.
            ['title' => 'CP', 'icon' => 'fa-diamond', 'children' => [
                'CP Levels' => 'vips-cp',
                'Relation Cards' => 'cp-relations',
                'CP Relations & Requests' => 'cp-reports',
                'Weekly CP' => 'weekly-cp',
                'CP Settings' => 'cp-settings',
            ]],

            // Owner placement: Events sits right after CP, in the heart of the
            // list — buried at the tail it read as "missing" (2026-08-07).
            self::eventsSection(),

            ['title' => 'Families', 'icon' => 'fa-sitemap', 'children' => [
                'Families' => 'families',
                'Family Levels' => 'family_levels',
                'Family Users' => 'family-users',
                'Family Settings' => 'families-settings',
            ]],

            ['title' => 'Agencies', 'icon' => 'fa-building', 'children' => [
                'Agencies' => 'agencies',
                'Charge Agencies' => 'charge-agencies',
                'Hosts' => 'ag/users',
                'BD Super Admin' => 'usersBd',
                // Owner 2026-08-11: sits directly under BD Super Admin
                // (mirrors the live menu built by the 2026_08_11 restructure
                // migration). Owner 2026-08-12: plural entity name — this
                // layer sells coins TO the shipping agencies below it.
                'Shipping Super Admins' => 'shipping-super-admins',
                'Super Admin Settings' => 'usersBd-settings',
                'Agency Settings' => 'agency-settings',
                'Targets' => 'targets',
                'User Targets' => 'userTarget',
                'Join Requests' => 'agency_join_requests',
                'Manager Types' => 'manger-types',
            ]],

            ['title' => 'Salaries', 'icon' => 'fa-money', 'children' => [
                'Salaries' => 'sallaries',
                'Salary History' => 'sallaries_history',
                'Salary Requests' => 'requests-for-get-salary',
                'Salary Requests History' => 'requests-for-get-salary-history',
                'Reset Salary' => 'reset-salary',
            ]],

            ['title' => 'Rooms & Live', 'icon' => 'fa-microphone', 'children' => [
                'Rooms' => 'rooms',
                'Live Rooms' => 'live-rooms',
                'Room Categories' => 'categories',
                'Backgrounds' => 'backgrounds',
                'Background Manager' => 'room-background-manager',
                'Background Requests' => 'request-background-image',
                'Room Gift Targets' => 'room-gift-targets',
                'Room Targets' => 'room-target',
                'Room VIPs' => 'room-vips',
                'Room Settings' => 'room-settings',
                'Banned Rooms' => 'bans-rooms',
            ]],

            ['title' => 'Gifts', 'icon' => 'fa-gift', 'children' => [
                'Gifts' => 'gifts',
                'Gift Categories' => 'gift-categories',
                'Lucky Gift Settings' => 'lucky-gift-settings',
                'Lucky Gifts Reports' => 'lucky-gift-reports',
                'Gift Summary' => 'gift-summary',
            ]],

            ['title' => 'Store', 'icon' => 'fa-shopping-cart', 'children' => [
                'Wares' => 'wares',
                'Ware Management' => 'ware-management',
                'VIP Wares' => 'wares-vips',
                'Ware Dedicate' => 'wares_dedicate',
                'Special ID Dedicate' => 'uuid_dedicate',
            ]],

            self::gamesSection(),

            ['title' => 'Charging', 'icon' => 'fa-credit-card', 'children' => [
                'Recharge' => 'charges',
                'Charge Values' => 'charge_values',
                'Charge Details' => 'charges-details',
                'Charge Settings' => 'charges-settings',
                'User Charges' => 'user-charges',
                'Payment Gateways' => 'payment-gateways',
                'Payment Coins' => 'payment-coins',
                'Payment Methods' => 'payment-with-method',
            ]],

            ['title' => 'Coins & Wallets', 'icon' => 'fa-database', 'children' => [
                'Silver' => 'silver',
                'Exchanges' => 'exchanges',
                'Withdraw Types' => 'withdraw-types',
                'Commissions' => 'commissions',
                'User Wallets' => 'user-wallets',
                'Wallet Transactions' => 'wallet-transactions',
                'Remaining Diamonds' => 'remaining-diamonds',
                'Remaining Diamond Settings' => 'remaining-diamond-settings',
            ]],

            ['title' => 'Rewards', 'icon' => 'fa-star', 'children' => [
                'Admin Rewards' => 'admin-rewards',
                'Admin Rewards History' => 'admin-rewards-histories',
                'Super Package Rewards' => 'super-package-rewards',
            ]],

            ['title' => 'Reports', 'icon' => 'fa-line-chart', 'children' => [
                'App Earnings' => 'app-earned',
                'Coin Reports' => 'coin-reports',
                'Coin Log Reports' => 'coin-logs-reports',
                'Coin Logs' => 'trxs',
                'Charge Reports' => 'charges-reports',
                'Total Statistics' => 'total-statistics',
                'Agency Statistics' => 'agency-statistic',
                'Reports' => 'reports',
                'User Reports' => 'report_users',
            ]],

            ['title' => 'Moderation', 'icon' => 'fa-ban', 'children' => [
                'Bans' => 'bans',
                'Ban Types' => 'ban-types',
                'Black List' => 'blacks',
                'Black List Users' => 'black-lists',
                'Sensitive Words' => 'sensitive-words',
            ]],

            self::adsSection(),

            // Owner 2026-08-12: Room Boom sits directly under Ads.
            self::roomBoomSection(),

            // Owner 2026-08-12: Room Cup sits directly under Room Boom.
            self::roomCupSection(),

            self::emojisSection(),

            ['title' => 'Support', 'icon' => 'fa-life-ring', 'children' => [
                'Complaints' => 'tickets',
            ]],

            self::invitationSection(),

            self::notificationsSection(),

            // Owner 2026-08-13: 'Configs' (raw configs table) hidden from the
            // menu — its keys are duplicated across cleaner dedicated pages
            // (rooms/charges/stream) and it exposed raw secrets. Route stays
            // live for super-admin, just off the sidebar.
            // Owner 2026-08-14: 'Country Categories' and 'Server Countries'
            // deleted entirely (multi-server leftovers, empty tables).
            ['title' => 'Settings', 'icon' => 'fa-cogs', 'children' => [
                'Settings' => 'settings',
                'Countries' => 'countries',
                'Phone Codes' => 'codes',
                'Languages' => 'languages',
                'App Features' => 'app-features',
                'Default Screen Settings' => 'default-app-screen-settings',
                // Owner 2026-08-09: account-deletion REASONS catalog is a policy
                // setting, moved out of Users to sit with the rest of settings.
                'Delete Account Reasons' => 'delete-accounts',
            ]],

            // Owner 2026-08-12: renamed from "Admin". The standalone Permissions
            // page is gone (permissions are managed inside the role form);
            // Menu is parked in Other; Operation Log stays (owner keeps it).
            ['title' => 'Users & Permissions', 'icon' => 'fa-shield', 'children' => [
                'Admin Users' => 'auth/users',
                'Roles' => 'auth/roles',
                'Operation Log' => 'auth/logs',
            ]],

            // helpers/* deliberately absent: StopInProduction 403s them on any
            // production install, so listing them just renders dead links.
            ['title' => 'Developer', 'icon' => 'fa-wrench', 'children' => [
                'Dev Dashboard' => '/dev',
            ]],

            // Parking lot for undecided-but-live pages (owner rule 2026-08-09).
            self::otherSection(),
        ];
    }
}
