<?php

namespace Tests\Unit\Services;

use App\Console\Commands\SyncFairLuckWallets;
use App\Models\FairLuckWallet;
use App\Models\User;
use App\Models\UserLuckProfile;
use App\Services\FairLuck\ProfileManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * DB-backed tests for the Lane6 atomicity + durability fixes:
 *  - ProfileManager::applyStatsAtomically uses lock-free increments (no lost update).
 *  - SyncFairLuckWallets snapshots TYPE_UNIFIED_VAULT (was the deprecated global_vault bug).
 *
 * These require a configured test database (RefreshDatabase) and are intended to
 * be run by the per-phase test team; they document the expected invariants.
 */
class FairLuckProfileAndSyncTest extends TestCase
{
    use RefreshDatabase;

    /** user_luck_profiles.user_id has an FK to users — fixture rows are required. */
    private function makeUser(int $id): void
    {
        User::factory()->create(['id' => $id]);
    }

    /** applyStatsAtomically creates the row then increments it on subsequent calls. */
    public function test_apply_stats_atomically_increments_without_lost_updates()
    {
        $manager = new ProfileManager();
        $userId = 9001;
        $this->makeUser($userId);

        // Ensure the profile row exists first (firstOrCreate path).
        $manager->getProfile($userId);

        // 5 sequential bets of 100 each, 2 winners.
        for ($i = 0; $i < 5; $i++) {
            $manager->applyStatsAtomically($userId, 100, $i < 2 ? 50 : -100, $i < 2, 0.01);
        }

        $profile = UserLuckProfile::where('user_id', $userId)->first();

        $this->assertNotNull($profile);
        $this->assertEquals(5, $profile->bet_count);
        $this->assertEquals(2, $profile->win_count);
        $this->assertEquals(500, (int) $profile->total_bets);
        $this->assertNotNull($profile->first_bet_at);
    }

    /** applyAggregatedStats == N applyStatsAtomically calls collapsed into ONE UPDATE. */
    public function test_apply_aggregated_stats_matches_per_bet_increments()
    {
        $manager = new ProfileManager();
        $perBetUser = 9003;
        $aggregatedUser = 9004;
        $this->makeUser($perBetUser);
        $this->makeUser($aggregatedUser);

        // Reference: 5 per-bet increments (2 winners of +50, 3 losers of -100).
        for ($i = 0; $i < 5; $i++) {
            $manager->applyStatsAtomically($perBetUser, 100, $i < 2 ? 50 : -100, $i < 2, 0.07);
        }

        // Same batch applied as ONE aggregated increment (creates the row too).
        $manager->applyAggregatedStats($aggregatedUser, 500, (2 * 50) + (3 * -100), 5, 2, 0.07);

        $reference = UserLuckProfile::where('user_id', $perBetUser)->first();
        $aggregated = UserLuckProfile::where('user_id', $aggregatedUser)->first();

        $this->assertNotNull($aggregated);
        $this->assertEquals($reference->bet_count, $aggregated->bet_count);
        $this->assertEquals($reference->win_count, $aggregated->win_count);
        $this->assertEquals((int) $reference->total_bets, (int) $aggregated->total_bets);
        $this->assertEquals((int) $reference->total_profit, (int) $aggregated->total_profit);
        $this->assertEquals((float) $reference->current_deviation, (float) $aggregated->current_deviation);
        $this->assertNotNull($aggregated->first_bet_at);

        // A second aggregated batch INCREMENTS (no overwrite).
        $manager->applyAggregatedStats($aggregatedUser, 300, -300, 3, 0, 0.02);
        $aggregated->refresh();
        $this->assertEquals(8, $aggregated->bet_count);
        $this->assertEquals(2, $aggregated->win_count);
        $this->assertEquals(800, (int) $aggregated->total_bets);
    }

    /** applyStatsAtomically creates the profile row if it does not exist yet. */
    public function test_apply_stats_creates_missing_profile()
    {
        $manager = new ProfileManager();
        $userId = 9002;
        $this->makeUser($userId);

        $this->assertNull(UserLuckProfile::where('user_id', $userId)->first());

        $manager->applyStatsAtomically($userId, 200, -200, false, 0.0);

        $profile = UserLuckProfile::where('user_id', $userId)->first();
        $this->assertNotNull($profile);
        $this->assertEquals(1, $profile->bet_count);
        $this->assertEquals(0, $profile->win_count);
        $this->assertEquals(200, (int) $profile->total_bets);
    }

    /** Snapshot must target the unified vault (regression guard for the GLOBAL_VAULT bug). */
    public function test_sync_command_snapshots_unified_vault()
    {
        $liveBalance = 123456;
        // The vault lives on the dedicated durable connection (FairLuckWallet::vaultRedis).
        Redis::connection('fairluck')->set('fairluck:wallet:' . FairLuckWallet::TYPE_UNIFIED_VAULT, $liveBalance);

        $this->artisan(SyncFairLuckWallets::class)->assertExitCode(0);

        $row = FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->first();

        $this->assertNotNull($row, 'unified_vault snapshot row must be written');
        $this->assertEquals($liveBalance, (int) $row->balance);
    }

    /** A negative live balance (negative-limit design) is snapshotted verbatim, not clamped to 0. */
    public function test_sync_preserves_negative_unified_vault_balance()
    {
        Redis::connection('fairluck')->set('fairluck:wallet:' . FairLuckWallet::TYPE_UNIFIED_VAULT, -5000);

        FairLuckWallet::syncToDatabase(FairLuckWallet::TYPE_UNIFIED_VAULT);

        $row = FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->first();

        $this->assertNotNull($row);
        $this->assertEquals(-5000, (int) $row->balance);
    }
}
