<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Tik\Repositories\AgencyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression guard for B3: AgencyRepository::members() hard-coded paginate(20) and
 * ignored the page argument, so every page returned the SAME first 20 rows. The
 * Flutter list appends pages, so members appeared duplicated as the user scrolled.
 *
 * The fix threads ($perPage, $page) through controller -> service -> repository and
 * adds a stable secondary order (users.id) so pages never overlap.
 *
 * Runs against the existing test DB (no RefreshDatabase) using the same clone-and-
 * cleanup pattern as SearchUserAgencyRegressionTest: clone a real agency + a handful
 * of member user rows, then prove page 1 and page 2 are disjoint.
 */
class AgencyMembersPaginationTest extends TestCase
{
    public function test_members_pages_are_disjoint_and_carry_pagination_metadata(): void
    {
        $baseUser = (array) DB::table('users')->first();
        if (!$baseUser) {
            $this->markTestSkipped('no users to clone in the test DB');
        }

        // A real agency row gives us valid NOT NULL columns to clone.
        $baseAgency = (array) DB::table('agencies')->first();
        if (!$baseAgency) {
            $this->markTestSkipped('no agencies to clone in the test DB');
        }

        unset($baseAgency['id']);
        $baseAgency['name'] = 'TST-AGENCY-' . uniqid();
        if (array_key_exists('app_owner_id', $baseAgency)) {
            // Distinct owner id so members are never filtered out as the owner.
            $baseAgency['app_owner_id'] = 0;
        }

        $agencyId = DB::table('agencies')->insertGetId($baseAgency);

        $perPage = 2;
        $memberCount = 5; // > 2 pages worth at perPage = 2
        $createdUserIds = [];

        try {
            for ($i = 0; $i < $memberCount; $i++) {
                $row = $baseUser;
                unset($row['id']);
                // Every UNIQUE column on `users` must be made unique, otherwise
                // cloning a real row collides on the real test DB (phone/email/etc).
                $uniq = uniqid('', true) . $i;
                $row['uuid'] = 'TSTM' . $uniq;
                $row['email'] = 'tst_' . $uniq . '@example.test';
                $row['phone'] = '+999' . substr(preg_replace('/\D/', '', $uniq), 0, 12) . $i;
                $row['firebase_uuid'] = 'TSTFB' . $uniq;
                $row['google_id'] = null;
                $row['facebook_id'] = null;
                if (array_key_exists('special_id', $row)) {
                    $row['special_id'] = null;
                }
                $row['agency_id'] = $agencyId;
                $createdUserIds[] = DB::table('users')->insertGetId($row);
            }

            $agency = Agency::withoutGlobalScopes()->find($agencyId);
            $repo = new AgencyRepository();

            $page1 = $repo->members($agency, $perPage, 1);
            $page2 = $repo->members($agency, $perPage, 2);

            $this->assertInstanceOf(LengthAwarePaginator::class, $page1);

            // Metadata reflects the requested page, not a hard-coded first page.
            $this->assertEquals(1, $page1->currentPage());
            $this->assertEquals(2, $page2->currentPage());
            $this->assertEquals($perPage, $page1->perPage());

            $ids1 = collect($page1->items())->pluck('id')->all();
            $ids2 = collect($page2->items())->pluck('id')->all();

            // The core B3 assertion: no row appears on both pages.
            $this->assertEmpty(
                array_intersect($ids1, $ids2),
                'members() returned overlapping rows across pages — B3 regression'
            );
            // And no duplicates within a single page either.
            $this->assertSameSize($ids1, array_unique($ids1));
        } finally {
            if (!empty($createdUserIds)) {
                DB::table('users')->whereIn('id', $createdUserIds)->delete();
            }
            DB::table('agencies')->where('id', $agencyId)->delete();
        }
    }
}
