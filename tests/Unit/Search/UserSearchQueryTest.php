<?php

namespace Tests\Unit\Search;

use PHPUnit\Framework\TestCase;

/**
 * Lane1 — search-user-agency / SQL Injection: pins the filterUserNew fix.
 *
 * The old filterUserNew interpolated the raw user-supplied term directly into a
 * DB::raw CASE expression (`WHEN uuid = '{$userUuId}' ...`) — a real SQL
 * injection — and searched with a leading wildcard `LIKE '%term%'` plus an
 * unbounded `get()` with a computed matching_score ORDER BY (filesort). That
 * combination was both the vulnerability and the 504 (full table scan).
 *
 * The fix:
 *   - removes the DB::raw injection sink entirely (parameterized, prefix-only),
 *   - searches `LIKE 'term%'` (sargable prefix, no leading wildcard),
 *   - caps results with limit(30),
 *   - eager-loads the relations the resource needs (kills the N+1).
 *
 * These assertions guard those invariants at the source level so a future edit
 * cannot silently reintroduce the injection or the full scan.
 */
class UserSearchQueryTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = $this->methodBody();
    }

    /**
     * Extract the body of filterUserNew so assertions target only that method.
     */
    private function methodBody(): string
    {
        $file = file_get_contents(
            __DIR__ . '/../../../app/Tik/Repositories/UserRepository.php'
        );

        $start = strpos($file, 'function filterUserNew(');
        $this->assertNotFalse($start, 'filterUserNew must exist.');

        // capture from the method signature up to the next method definition
        $rest = substr($file, $start);
        $end = strpos($rest, 'function searchUserById(');

        return $end !== false ? substr($rest, 0, $end) : $rest;
    }

    public function test_no_raw_interpolation_of_user_input(): void
    {
        $this->assertStringNotContainsString(
            "'{\$userUuId}'",
            $this->source,
            'The raw interpolation of user input into SQL (injection sink) must be gone.'
        );
        $this->assertStringNotContainsString(
            'matching_score',
            $this->source,
            'The interpolated CASE...matching_score filesort expression must be removed.'
        );
        $this->assertStringNotContainsString(
            'DB::raw(',
            $this->source,
            'filterUserNew must not use DB::raw with user input anymore.'
        );
    }

    public function test_uses_prefix_search_not_leading_wildcard(): void
    {
        // prefix variable form `$userUuId . '%'`
        $this->assertMatchesRegularExpression(
            '/\$prefix\s*=\s*\$userUuId\s*\.\s*\'%\';/',
            $this->source,
            'Search must build a prefix term (term%), not a substring term.'
        );

        $this->assertStringNotContainsString(
            "'%' . \$userUuId . '%'",
            $this->source,
            'The leading-wildcard substring search (%term%) must be removed.'
        );
    }

    public function test_results_are_capped_with_limit(): void
    {
        $this->assertStringContainsString(
            '->limit(30)',
            $this->source,
            'Interactive search must be bounded by a strict LIMIT.'
        );
    }

    public function test_eager_loads_resource_relations_to_avoid_n_plus_one(): void
    {
        foreach (['profile', 'specialId.ware', 'receiverLevel', 'senderLevel', 'UserVip', 'packs'] as $relation) {
            $this->assertStringContainsString(
                $relation,
                $this->source,
                "filterUserNew must eager-load '{$relation}' so GeneralUserResource does not query per row.",
            );
        }
    }
}
