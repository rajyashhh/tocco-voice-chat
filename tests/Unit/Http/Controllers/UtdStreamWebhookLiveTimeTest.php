<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Api\V1\UtdStreamWebhookController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the live_times writer for the UTD Stream room flow.
 *
 * Root cause being pinned: the legacy writers (POST rooms/up-microphone /
 * leave-microphone / liveTime) died with the engine-room rollout on
 * 2026-06-04 — the table that feeds host salaries went silent (last row
 * 2026-06-08 13:35, the final legacy-client call). In the engine flow,
 * publishing an AUDIO track IS being on a mic seat, so the webhook handlers
 * are the successor writers:
 *
 *  - track_published (AUDIO)   -> open a timer (mirrors MicService::upMic2)
 *  - track_unpublished (AUDIO) -> close it (UserHandling::calcTime math)
 *  - participant_left          -> close it too (covers crash-exits)
 *
 * Source-reflection style (no DB), matching the project's pure unit tests.
 */
class UtdStreamWebhookLiveTimeTest extends TestCase
{
    private function methodSource(string $method): string
    {
        $ref = new ReflectionMethod(UtdStreamWebhookController::class, $method);
        $file = file($ref->getFileName());

        return implode('', array_slice(
            $file,
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        ));
    }

    public function test_track_published_opens_a_live_timer_for_audio_only(): void
    {
        $src = $this->methodSource('handleTrackPublished');

        // AUDIO tracks only (mic seats); video must not open salary timers.
        // Track type is normalized via trackType() (handles the engine's numeric
        // protobuf enum) then gated on the string form.
        $this->assertStringContainsString("\$trackType = \$this->trackType(\$data)", $src);
        $this->assertStringContainsString("\$trackType !== 'AUDIO'", $src);

        // Identity is the users.id (UtdStreamController::token contract).
        $this->assertStringContainsString("\$data['participant']['identity']", $src);

        // Stale open rows from previous days are dropped (legacy parity with
        // RoomController::up_microphone), then a timer opens only when none
        // is active today.
        $this->assertStringContainsString("whereDate('created_at', '!=', today())", $src);
        $this->assertMatchesRegularExpression(
            "/if \(! \\\$hasOpenTimer\) \{\s*LiveTime::query\(\)->create\(/s",
            $src
        );
        $this->assertStringContainsString("'start_time' => time()", $src);
    }

    public function test_track_unpublished_and_participant_left_close_the_timer(): void
    {
        $unpublished = $this->methodSource('handleTrackUnpublished');
        $this->assertStringContainsString("\$trackType !== 'AUDIO'", $unpublished);
        $this->assertStringContainsString('closeLiveTimer($userId)', $unpublished);

        $left = $this->methodSource('handleParticipantLeft');
        $this->assertStringContainsString(
            'closeLiveTimer($userId)',
            $left,
            'participant_left must close the timer (crash-exits never send track_unpublished)'
        );

        // Closing delegates to the legacy close math (UserHandling::calcTime)
        // and never 500s the webhook for a since-deleted user.
        $close = $this->methodSource('closeLiveTimer');
        $this->assertStringContainsString('UserHandling::calcTime($userId)', $close);
        $this->assertStringContainsString('whereKey($userId)->exists()', $close);
    }
}
