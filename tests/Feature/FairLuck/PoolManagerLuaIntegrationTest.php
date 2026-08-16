<?php

namespace Tests\Feature\FairLuck;

use App\Models\FairLuckWallet;
use App\Services\FairLuck\V7\PoolManager;
use Tests\TestCase;

/**
 * Integration tests for the atomic Lua money core against a REAL `fairluck` Redis
 * connection. Skipped automatically when no Redis is reachable, so it is a no-op
 * locally and runs for real on CI/staging (where the durable vault Redis exists).
 *
 * These verify the two hot-path scripts behave exactly as the pure simulation
 * assumes: atomic credit/accrual/intake, guarded debit, RTP bookkeeping, and the
 * reconciliation invariant vault == seed + intake - payout.
 */
class PoolManagerLuaIntegrationTest extends TestCase
{
    private PoolManager $pool;
    private int $userId = 999_000_111;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            FairLuckWallet::vaultRedis()->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No fairluck Redis available: ' . $e->getMessage());
        }

        $this->pool = app(PoolManager::class);
        $this->resetKeys();
    }

    protected function tearDown(): void
    {
        if (isset($this->pool)) {
            $this->resetKeys();
        }
        parent::tearDown();
    }

    private function resetKeys(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->del(
            PoolManager::KEY_VAULT,
            PoolManager::KEY_OWNER_ACCRUAL,
            PoolManager::KEY_STAT_INTAKE,
            PoolManager::KEY_STAT_PAYOUT,
            PoolManager::userKey($this->userId)
        );
    }

    public function test_credit_and_read_is_atomic_and_returns_state(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->set(PoolManager::KEY_VAULT, 100_000);

        $state = $this->pool->creditAndRead(89, 1, $this->userId);

        $this->assertSame(100_089, $state->vault);
        $this->assertSame(0, $state->totalSpent);
        $this->assertSame(0, $state->totalReceived);
        $this->assertSame(0, $state->consecutiveLosses);

        $this->assertSame(1, (int) $r->get(PoolManager::KEY_OWNER_ACCRUAL));
        $this->assertSame(89, (int) $r->get(PoolManager::KEY_STAT_INTAKE));
    }

    public function test_settle_debits_a_winner_and_updates_rtp(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->set(PoolManager::KEY_VAULT, 100_000);

        // bet 100, win x5 → payout 500
        $res = $this->pool->settle($this->userId, 100, 500, 30_000, 0);

        $this->assertSame(500, $res->applied);
        $this->assertSame(99_500, $res->vault);
        $this->assertSame(500, (int) $r->get(PoolManager::KEY_STAT_PAYOUT));

        $h = $r->hgetall(PoolManager::userKey($this->userId));
        $this->assertSame(100, (int) $h['total_spent']);
        $this->assertSame(500, (int) $h['total_received']);
        $this->assertSame(1, (int) $h['bet_count']);
        $this->assertSame(1, (int) $h['win_count']);
        $this->assertSame(0, (int) $h['consecutive_losses']);
    }

    public function test_settle_refuses_payout_that_would_breach_negative_limit(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->set(PoolManager::KEY_VAULT, 1_000);

        // payout 50_000 with limit 30_000: 1000 - 50000 = -49000 < -30000 → refuse
        $res = $this->pool->settle($this->userId, 100, 50_000, 30_000, 0);

        $this->assertSame(0, $res->applied);
        $this->assertSame(1_000, $res->vault); // unchanged
        $this->assertSame(0, (int) $r->get(PoolManager::KEY_STAT_PAYOUT));

        $h = $r->hgetall(PoolManager::userKey($this->userId));
        $this->assertSame(1, (int) $h['consecutive_losses']); // counted as a loss
    }

    public function test_full_bet_roundtrip_conserves_vault(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $seed = 200_000;
        $r->set(PoolManager::KEY_VAULT, $seed);

        // Simulate 50 bets of 100 with deterministic alternating win/loss.
        $intakeExpected = 0;
        $payoutExpected = 0;
        for ($i = 0; $i < 50; $i++) {
            $this->pool->creditAndRead(89, 1, $this->userId);
            $intakeExpected += 89;

            $payout = ($i % 5 === 0) ? 500 : 0; // win x5 every 5th bet
            $res = $this->pool->settle($this->userId, 100, $payout, 30_000, 0);
            $payoutExpected += $res->applied;
        }

        $vault  = (int) $r->get(PoolManager::KEY_VAULT);
        $intake = (int) $r->get(PoolManager::KEY_STAT_INTAKE);
        $payout = (int) $r->get(PoolManager::KEY_STAT_PAYOUT);

        $this->assertSame($intakeExpected, $intake);
        $this->assertSame($payoutExpected, $payout);
        $this->assertSame($seed + $intake - $payout, $vault, 'reconciliation invariant must hold');
    }

    public function test_batch_settle_stamps_intent_commit_proof_with_total_paid(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->set(PoolManager::KEY_VAULT, 200_000);
        $nonce = 'test-intent-' . uniqid();
        $proofKey = PoolManager::intentProofKey($nonce);
        $r->del($proofKey);

        $bets = [
            ['netToVault' => 89, 'ownerCut' => 1, 'requestedPayout' => 0,   'betAmount' => 100],
            ['netToVault' => 89, 'ownerCut' => 1, 'requestedPayout' => 500, 'betAmount' => 100],
            ['netToVault' => 89, 'ownerCut' => 1, 'requestedPayout' => 0,   'betAmount' => 100],
        ];

        $res = $this->pool->batchSettle($this->userId, $bets, 30_000, 0, $nonce);

        $this->assertSame(500, $res->totalPaid);
        $this->assertSame([0, 500, 0], $res->applied);
        // Proof key stamped ATOMICALLY inside the same EVAL, value = totalPaid.
        $this->assertSame(500, (int) $r->get($proofKey));
        $this->assertGreaterThan(0, (int) $r->ttl($proofKey));
        // Vault math identical to the serial chain: +3×89 −500.
        $this->assertSame(200_000 + 3 * 89 - 500, $res->vault);

        $r->del($proofKey);
    }

    public function test_batch_settle_without_nonce_writes_no_proof_key(): void
    {
        $r = FairLuckWallet::vaultRedis();
        $r->set(PoolManager::KEY_VAULT, 200_000);
        $noneKey = PoolManager::intentProofKey('none');
        $r->del($noneKey);

        $bets = [
            ['netToVault' => 89, 'ownerCut' => 1, 'requestedPayout' => 0, 'betAmount' => 100],
        ];

        $res = $this->pool->batchSettle($this->userId, $bets, 30_000, 0);

        $this->assertSame(0, $res->totalPaid);
        $this->assertSame(200_089, $res->vault);
        $this->assertFalse((bool) $r->exists($noneKey), 'placeholder proof key must never be written');
    }
}
