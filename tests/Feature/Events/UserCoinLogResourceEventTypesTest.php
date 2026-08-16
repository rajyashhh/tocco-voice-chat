<?php

namespace Tests\Feature\Events;

use App\Models\UserCoinLog;
use Illuminate\Http\Request;
use Modules\UsersWallet\Http\Resources\UserCoinLogResource;
use Tests\TestCase;

/**
 * UserCoinLogResource — new wallet-history cases for pk_event / charge_event
 * (shared section of the build). Pure in-memory unit: no DB writes.
 *
 * Acceptance criteria: both types render a non-empty, type-specific
 * title/description (not the generic fallback), amount passes through, and
 * negative_sign is false for payouts.
 */
class UserCoinLogResourceEventTypesTest extends TestCase
{
    private function render(string $type, int $amount = 500): array
    {
        $log = new UserCoinLog([
            'user_id' => 1, 'type' => $type, 'sub_type' => 'x', 'amount' => $amount,
            'amount_before' => 0, 'item_name' => 'rewards',
            'from_date' => now(), 'to_date' => now(),
        ]);
        $log->created_at = now();

        return (new UserCoinLogResource($log))->toArray(Request::create('/'));
    }

    public function test_pk_event_type_renders_dedicated_copy(): void
    {
        $row = $this->render('pk_event');

        $this->assertSame('pk_event', $row['type']);
        $this->assertNotSame('', (string) $row['title']);
        $this->assertNotSame((string) $this->render('some_unknown_type')['title'], (string) $row['title'], 'pk_event must not fall back to the generic branch');
        $this->assertFalse($row['negative_sign']);
        $this->assertSame(500, (int) $row['amount']);
    }

    public function test_charge_event_type_renders_dedicated_copy(): void
    {
        $row = $this->render('charge_event');

        $this->assertSame('charge_event', $row['type']);
        $this->assertNotSame('', (string) $row['title']);
        $this->assertNotSame((string) $this->render('some_unknown_type')['title'], (string) $row['title'], 'charge_event must not fall back to the generic branch');
        $this->assertFalse($row['negative_sign']);
    }
}