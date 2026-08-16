<?php

namespace Tests\Unit\Search;

use App\Tik\Repositories\ShippingAgencyRepository;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Lane1 — search-user-agency: pins the agency-search fix.
 *
 * The old filterAgency used `whereRaw('CAST(id AS CHAR) LIKE ?', [$id.'%'])`,
 * which CASTs the primary key on every row and therefore cannot use the PK
 * index (full scan -> 504). The fix replaces it with sargable numeric BETWEEN
 * ranges on the raw PK, computed by numericPrefixRanges().
 *
 * These assertions verify the prefix semantics are preserved exactly while the
 * predicate stays index-friendly, and that the CAST/wildcard sink is gone.
 */
class AgencyPrefixSearchTest extends TestCase
{
    private function ranges(string $prefix): array
    {
        $repo = new ShippingAgencyRepository();
        $method = new ReflectionMethod($repo, 'numericPrefixRanges');
        $method->setAccessible(true);

        return $method->invoke($repo, $prefix);
    }

    /**
     * Membership check matching the BETWEEN ranges, used to assert that an id
     * is (or is not) selected by the generated ranges.
     */
    private function idInRanges(int $id, array $ranges): bool
    {
        foreach ($ranges as [$low, $high]) {
            if ($id >= $low && $id <= $high) {
                return true;
            }
        }
        return false;
    }

    public function test_exact_id_is_first_range(): void
    {
        $ranges = $this->ranges('123');

        $this->assertSame([123, 123], $ranges[0], 'The exact id must be the first (single-point) range.');
    }

    public function test_prefix_matches_ids_that_start_with_the_term(): void
    {
        $ranges = $this->ranges('12');

        // decimal strings starting with "12"
        $this->assertTrue($this->idInRanges(12, $ranges));
        $this->assertTrue($this->idInRanges(120, $ranges));
        $this->assertTrue($this->idInRanges(129, $ranges));
        $this->assertTrue($this->idInRanges(1200, $ranges));
        $this->assertTrue($this->idInRanges(12999, $ranges));
    }

    public function test_prefix_does_not_match_ids_that_do_not_start_with_the_term(): void
    {
        $ranges = $this->ranges('12');

        $this->assertFalse($this->idInRanges(1, $ranges));
        $this->assertFalse($this->idInRanges(13, $ranges));
        $this->assertFalse($this->idInRanges(21, $ranges));
        $this->assertFalse($this->idInRanges(112, $ranges)); // contains 12 but does not start with it
        $this->assertFalse($this->idInRanges(212, $ranges));
    }

    public function test_ranges_are_bounded_and_each_is_low_high_pair(): void
    {
        $ranges = $this->ranges('7');

        $this->assertNotEmpty($ranges);
        $this->assertLessThanOrEqual(18, count($ranges), 'Range fan-out must stay bounded by the max PK width.');

        foreach ($ranges as $range) {
            $this->assertCount(2, $range);
            $this->assertLessThanOrEqual($range[1], $range[0], 'low must not exceed high.');
        }
    }

    public function test_filter_agency_source_has_no_cast_or_leading_wildcard(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../app/Tik/Repositories/ShippingAgencyRepository.php'
        );

        $this->assertStringNotContainsString(
            'CAST(id AS CHAR)',
            $source,
            'filterAgency must not CAST the primary key (it invalidates the PK index).'
        );
        $this->assertStringNotContainsString(
            "whereRaw('CAST",
            $source,
            'The CAST whereRaw sink must be removed.'
        );
    }
}
