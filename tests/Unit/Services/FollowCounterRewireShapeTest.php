<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\V1\BlackListController;
use App\Jobs\FollowJob;
use App\Services\UserService;
use App\Traits\FollowTrait;
use Modules\Chat\Http\Services\ChatRoomService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Pins the C1 follow-counter rewiring: the legacy users.following/follower/friend
 * columns are frozen (no writers, no logic readers); all logic reads the
 * number_of_* family which is recalculated from the follows table.
 * Pure source-reflection, no DB — runs deterministically anywhere.
 */
class FollowCounterRewireShapeTest extends TestCase
{
    private function methodSource(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $lines = array_slice(
            file($ref->getFileName()),
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        );

        return implode('', $lines);
    }

    public function test_follow_job_no_longer_writes_legacy_counters(): void
    {
        $source = $this->methodSource(FollowJob::class, 'handle');

        $this->assertDoesNotMatchRegularExpression('/->friend\b/', $source);
        $this->assertDoesNotMatchRegularExpression('/->follower\b/', $source);
        $this->assertDoesNotMatchRegularExpression('/->following\b/', $source);
        $this->assertStringNotContainsString('->save()', $source);
        $this->assertStringContainsString('CustomNotification::followBack', $source);
        $this->assertStringContainsString('CustomNotification::follow', $source);
        $this->assertStringContainsString('UserCounterServices', $source);
    }

    public function test_unfollow_recalculates_after_deleting_the_edge(): void
    {
        $source = $this->methodSource(UserService::class, 'unFollowUser');

        $this->assertDoesNotMatchRegularExpression('/->friend\b|->follower\b|->following\b/', $source);

        $deletePos = strpos($source, 'deleteFollow');
        $recalcPos = strpos($source, 'UserFollowHelper::updateCounts');
        $this->assertNotFalse($deletePos);
        $this->assertNotFalse($recalcPos);
        $this->assertLessThan($recalcPos, $deletePos, 'updateCounts must run AFTER the follow edge is deleted, otherwise the recalc reads the stale edge.');
    }

    public function test_dead_handle_follow_back_is_removed(): void
    {
        $this->assertFalse(
            (new ReflectionClass(UserService::class))->hasMethod('handleFollowBack'),
            'handleFollowBack was dead code (call commented out) and a legacy-counter writer; it must stay deleted.'
        );
    }

    public function test_count_reel_reads_number_of_friends(): void
    {
        $source = $this->methodSource(ChatRoomService::class, 'countReel');

        $this->assertStringContainsString('number_of_friends', $source);
        $this->assertDoesNotMatchRegularExpression('/->friend\b/', $source);
    }

    public function test_blacklist_add_recalculates_both_parties(): void
    {
        $source = $this->methodSource(BlackListController::class, 'add');

        $this->assertSame(
            2,
            substr_count($source, 'UserFollowHelper::updateCounts'),
            'Blocking deletes both follow edges, so both parties must be recalculated.'
        );
    }

    public function test_friends_relation_requires_status_one_on_both_edges(): void
    {
        $traitRef = new ReflectionClass(FollowTrait::class);
        $source = $this->methodSource($traitRef->getName(), 'friends');

        $this->assertStringContainsString("wherePivot('status', 1)", $source);
        $this->assertMatchesRegularExpression(
            "/where\('followed_user_id', \\\$this->id\)\s*->where\('status', 1\)/",
            $source,
            "friends() must filter status=1 on the incoming edge too, matching friendRelations() used by the bulk recalc job — otherwise number_of_friends oscillates between the two definitions."
        );
    }

    public function test_admin_form_no_longer_exposes_legacy_counters(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/app/Admin/Controllers/UserSettingController.php'
        );

        $this->assertDoesNotMatchRegularExpression("/\\\$form->number\('(following|follower|friend)'/", $source);
        $this->assertStringContainsString("field('number_of_followings'", $source);
        $this->assertStringContainsString("field('number_of_fans'", $source);
        $this->assertStringContainsString("field('number_of_friends'", $source);
    }
}
