<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Api\V1\UtdStreamWebhookController;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Services\RoomOccupancyReconciler;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the "early broadcast" fix: a live room appears in the lives/trending list
 * ONLY once the host has actually started broadcasting (first VIDEO
 * track_published on the UTD-Stream engine), not the moment the host opens the
 * room over HTTP.
 *
 * Source-reflection style (no DB), matching the project's pure unit tests.
 */
class LiveBroadcastGateTest extends TestCase
{
    private function methodSource(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = file($ref->getFileName());

        return implode('', array_slice(
            $file,
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        ));
    }

    public function test_lives_list_gates_on_is_broadcasting_not_is_afk(): void
    {
        $base = $this->methodSource(RoomRepository::class, 'baseRoomQuery');

        // The list is gated on the real broadcasting flag, in addition to is_live.
        $this->assertStringContainsString("->where('is_broadcasting', true)", $base);

        // The old too-early gate must be gone from the lives query.
        $live = $this->methodSource(RoomRepository::class, 'liveRooms');
        $this->assertStringNotContainsString("->where('is_afk', 1)", $live);
    }

    public function test_owner_video_publish_sets_broadcasting(): void
    {
        $src = $this->methodSource(UtdStreamWebhookController::class, 'handleTrackPublished');

        // Only a VIDEO track from the room OWNER opens the broadcast.
        $this->assertStringContainsString("\$trackType === 'VIDEO'", $src);
        $this->assertStringContainsString('(int) $room->uid === $userId', $src);
        $this->assertStringContainsString('setBroadcasting($room, true)', $src);
    }

    public function test_owner_stop_paths_clear_broadcasting(): void
    {
        // Owner unpublishes video.
        $unpub = $this->methodSource(UtdStreamWebhookController::class, 'handleTrackUnpublished');
        $this->assertStringContainsString('setBroadcasting($room, false)', $unpub);

        // Owner leaves (crash / clean exit).
        $left = $this->methodSource(UtdStreamWebhookController::class, 'handleParticipantLeft');
        $this->assertStringContainsString('setBroadcasting($room, false)', $left);

        // Room finished on the engine delegates to deactivateRoom, which clears it.
        $deactivate = $this->methodSource(RoomOccupancyReconciler::class, 'deactivateRoom');
        $this->assertStringContainsString('setBroadcasting($room, false)', $deactivate);
    }

    public function test_track_type_helper_normalizes_numeric_enum(): void
    {
        // The engine forwards the raw numeric protobuf enum under the default v1
        // schema; the helper must map 0/1/2 -> AUDIO/VIDEO/DATA and pass strings.
        $src = $this->methodSource(UtdStreamWebhookController::class, 'trackType');
        $this->assertStringContainsString('ctype_digit', $src);
        $this->assertStringContainsString('self::TRACK_TYPE_NAMES', $src);
    }
}
