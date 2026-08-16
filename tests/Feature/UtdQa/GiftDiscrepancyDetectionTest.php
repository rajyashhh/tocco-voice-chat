<?php

namespace Tests\Feature\UtdQa;

use App\Http\Controllers\TestsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GDD - Gift diamond discrepancy detector correctness.
 *
 * Target:
 *   app/Http/Controllers/TestsController.php::discrepancyView  (route
 *   GET /diamond-discrepancy, name diamond.discrepancy)
 *
 * Data contract (identical to GiftAccountingConsistencyTest, proven at the
 * write site Modules/Moment/Http/Controllers/MomentUserGiftsController.php::
 * sendGift): gift_logs.giftPrice is ALREADY the row TOTAL (unit price x qty):
 *     $totalPrice = $gift->price * $number;  $info['giftPrice'] = $totalPrice;
 *
 * The recorded side, monthly_diamond_receives.monthly_diamond_received, is the
 * sum of those per-send totals. Therefore the ONLY correct "actual" aggregate
 * is SUM(giftPrice). The previous detector computed SUM(giftNum * giftPrice),
 * which squares the quantity (N x N x P) for every multi-quantity send and
 * flagged a PHANTOM discrepancy on perfectly consistent accounts. The fix uses
 * SUM(giftPrice).
 *
 * What we prove:
 *  (1) No false positive: a user whose recorded total EXACTLY equals
 *      SUM(giftPrice) — with multi-quantity sends (giftNum > 1) that would blow
 *      up under the squared aggregation — is NOT reported as discrepant.
 *  (2) Detection still bites: a user whose recorded total genuinely differs
 *      from SUM(giftPrice) IS still reported. The fix narrows the aggregate, it
 *      does not blind the detector.
 */
class GiftDiscrepancyDetectionTest extends UtdQaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['gift_logs', 'monthly_diamond_receives'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->markTestSkipped("required table `{$t}` missing in the test DB; run migrate --force first.");
            }
        }
    }

    /**
     * gift_logs.created_at is a MySQL TIMESTAMP (2038 cap), so a far-future
     * sentinel month is impossible. We use the CURRENT month/year and rely on
     * DatabaseTransactions isolation (our rows are rolled back on teardown) plus
     * per-user-id assertions, so any pre-existing rows in the shared schema
     * cannot affect the specific users we assert about.
     */
    private function year(): int
    {
        return (int) now()->year;
    }

    private function month(): int
    {
        return (int) now()->month;
    }

    /** Insert one gift_logs row. giftPrice is the row total (unit x qty). */
    private function insertGift(array $o): int
    {
        return DB::table('gift_logs')->insertGetId(array_merge([
            'type'         => 2,
            'giftId'       => 1,
            'giftName'     => 'QA-GDD',
            'giftNum'      => 1,
            'giftPrice'    => 0,
            'roomowner_id' => 0,
            'sender_id'    => 0,
            'receiver_id'  => 0,
            'is_play'      => 2,
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $o));
    }

    private function recordMonthly(int $userId, int $received): void
    {
        DB::table('monthly_diamond_receives')->insert([
            'user_id'                  => $userId,
            'monthly_diamond_received' => $received,
            'month'                    => (string) $this->month(),
            'year'                     => (string) $this->year(),
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    /**
     * Drive the real controller for the current month and return its
     * 'results' array (the discrepancy rows the view renders).
     */
    private function discrepancies(): array
    {
        $request = Request::create('/diamond-discrepancy', 'GET', [
            'month' => $this->month(),
            'year'  => $this->year(),
        ]);
        app()->instance('request', $request);

        $view = (new TestsController())->discrepancyView($request);

        return $view->getData()['results'];
    }

    /**
     * (1) Consistent multi-quantity account must NOT be flagged.
     *
     * Two sends: qty 5 @ row total 50, qty 3 @ row total 30. SUM(giftPrice)=80.
     * Recorded total = 80 -> perfectly consistent. The squared aggregation would
     * compute 5*50 + 3*30 = 340 and raise a phantom diff of 260.
     */
    public function test_consistent_multiquantity_user_is_not_a_false_positive(): void
    {
        $user = $this->makeUser();

        $this->insertGift(['receiver_id' => $user->id, 'giftNum' => 5, 'giftPrice' => 50]);
        $this->insertGift(['receiver_id' => $user->id, 'giftNum' => 3, 'giftPrice' => 30]);
        $this->recordMonthly((int) $user->id, 80);

        $flagged = array_column($this->discrepancies(), 'user_id');

        $this->assertNotContains(
            (int) $user->id,
            array_map('intval', $flagged),
            'A user whose recorded total (80) equals SUM(giftPrice)=80 must NOT be '
            . 'flagged. SUM(giftNum*giftPrice)=340 would raise a phantom discrepancy.'
        );
    }

    /**
     * (2) A genuine mismatch must still be detected, and reported with the
     * correct actual = SUM(giftPrice).
     *
     * Sends total SUM(giftPrice) = 50 + 30 = 80, but the recorded ledger says
     * 200. That is a real 120 gap and must surface, with actual reported as 80
     * (not the squared 340).
     */
    public function test_genuine_mismatch_is_still_detected(): void
    {
        $user = $this->makeUser();

        $this->insertGift(['receiver_id' => $user->id, 'giftNum' => 5, 'giftPrice' => 50]);
        $this->insertGift(['receiver_id' => $user->id, 'giftNum' => 3, 'giftPrice' => 30]);
        $this->recordMonthly((int) $user->id, 200);

        $rows = collect($this->discrepancies())
            ->firstWhere('user_id', (int) $user->id);

        $this->assertNotNull(
            $rows,
            'A user whose recorded total (200) differs from SUM(giftPrice)=80 must be detected.'
        );
        $this->assertSame(200.0, (float) $rows['registered'], 'registered must echo the ledger value.');
        $this->assertSame(
            80.0,
            (float) $rows['actual'],
            'actual must be SUM(giftPrice)=80, never SUM(giftNum*giftPrice)=340.'
        );
    }

    /**
     * (1)+(2) combined in one population: with both users present in the same
     * month, only the genuinely-mismatched one is reported. Proves the detector
     * discriminates rather than flagging everyone.
     */
    public function test_only_the_real_mismatch_is_reported_in_a_mixed_population(): void
    {
        $consistent = $this->makeUser();
        $this->insertGift(['receiver_id' => $consistent->id, 'giftNum' => 4, 'giftPrice' => 40]);
        $this->insertGift(['receiver_id' => $consistent->id, 'giftNum' => 2, 'giftPrice' => 20]);
        $this->recordMonthly((int) $consistent->id, 60); // == SUM(giftPrice)

        $mismatch = $this->makeUser();
        $this->insertGift(['receiver_id' => $mismatch->id, 'giftNum' => 6, 'giftPrice' => 90]);
        $this->recordMonthly((int) $mismatch->id, 500); // != SUM(giftPrice)=90

        $flagged = array_map('intval', array_column($this->discrepancies(), 'user_id'));

        $this->assertNotContains((int) $consistent->id, $flagged, 'Consistent user must not be flagged.');
        $this->assertContains((int) $mismatch->id, $flagged, 'Genuinely mismatched user must be flagged.');
    }
}
