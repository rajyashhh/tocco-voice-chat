<?php

namespace Tests\Unit\Http\Controllers;

use Modules\Moment\Http\Controllers\MomentUserGiftsController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the fix for the 500 on GET api/moments/users/{id}/gifts:
 * "Attempt to read property receiverLevel on null" at CalcsTrait:496.
 *
 * MomentGiftUserResource calls Common::level_center($this->user); when the gifting
 * user was deleted from `users`, the relation is null and level_center crashes at
 * its first un-suppressed property read. The fix is structural (no value fallback):
 *
 *  (1) whereHas('user') — rows whose user is gone are EXCLUDED from the result.
 *  (2) The level relations level_center dereferences (receiverLevel/senderLevel)
 *      and the avatar's profile are eager-loaded, removing the per-row lazy loads.
 *
 * Source-reflection style (no DB), matching the project's pure unit tests.
 */
class MomentUserGiftsQueryShapeTest extends TestCase
{
    private function userGiftSource(): string
    {
        $ref = new ReflectionMethod(MomentUserGiftsController::class, 'userGift');
        $file = file($ref->getFileName());
        $lines = array_slice(
            $file,
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        );

        return implode('', $lines);
    }

    public function test_rows_with_deleted_users_are_excluded(): void
    {
        $this->assertStringContainsString(
            "whereHas('user')",
            $this->userGiftSource(),
            'Gift rows whose sender was deleted must be excluded, not defaulted.'
        );
    }

    public function test_level_relations_are_eager_loaded(): void
    {
        $src = $this->userGiftSource();

        $this->assertStringContainsString('user.receiverLevel', $src);
        $this->assertStringContainsString('user.senderLevel', $src);
        $this->assertStringContainsString('user.profile', $src);
    }

    public function test_no_silent_value_fallback_was_introduced(): void
    {
        // The owner's rule: fix at the source, no default that hides the problem.
        $this->assertStringNotContainsString('?? 0', $this->userGiftSource());
        $this->assertStringNotContainsString('withDefault', $this->userGiftSource());
    }
}
