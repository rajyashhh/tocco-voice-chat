<?php

namespace Tests\Feature\UtdQa;

use App\Classes\UserHandling;
use App\Tik\Repositories\GiftLogRepository;
use Illuminate\Support\Facades\DB;

/**
 * GA - Gift accounting consistency (no double-counting).
 *
 * Data contract (proven at the write site,
 *   Modules/Moment/Http/Controllers/MomentUserGiftsController.php::sendGift):
 *     gift_logs.giftPrice is stored ALREADY as the row TOTAL (unit price x qty):
 *         $totalPrice = $gift->price * $number;  $info['giftPrice'] = $totalPrice;
 *
 * Therefore the ONLY correct aggregation of a sender's/receiver's spend is
 * SUM(giftPrice). Multiplying again by the quantity — SUM(giftNum * giftPrice)
 * — squares the quantity (N x N x P) and inflates the displayed total. The fix
 * uses SUM(giftPrice) in every reporting path.
 *
 * Targets:
 *   App\Tik\Repositories\GiftLogRepository::getByDate
 *   App\Tik\Repositories\GiftLogRepository::getFirstRoomByOwnerId
 *   App\Tik\Repositories\GiftLogRepository::userGiftInfo
 *   App\Classes\UserHandling::getTopThreeSupport
 *
 * What we prove: for N gifts of unit price P (so each row giftPrice = N*P
 * because a single send bundles the quantity), across R rows, the reported
 * total equals SUM(giftPrice) exactly — never SUM(giftNum*giftPrice).
 */
class GiftAccountingConsistencyTest extends UtdQaTestCase
{
    /**
     * Insert one gift_logs row. giftPrice is the row total (unit x qty), matching
     * how sendGift persists it.
     */
    private function insertGift(array $o): int
    {
        return DB::table('gift_logs')->insertGetId(array_merge([
            'type'         => 2,
            'giftId'       => 1,
            'giftName'     => 'QA-GIFT',
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

    // ─────────────────── getByDate: receiver daily total ───────────────────

    /**
     * Receiver got two sends today: qty 5 @ unit 10 (row total 50) and qty 3 @
     * unit 10 (row total 30). Correct daily total = 80. The double-count path
     * would report 5*50 + 3*30 = 340.
     */
    public function test_getByDate_sums_gift_price_not_squared(): void
    {
        $receiver = $this->makeUser();

        $this->insertGift(['receiver_id' => $receiver->id, 'giftNum' => 5, 'giftPrice' => 50]);
        $this->insertGift(['receiver_id' => $receiver->id, 'giftNum' => 3, 'giftPrice' => 30]);

        $row = (new GiftLogRepository())->getByDate($receiver->id, now()->toDateString());

        $this->assertNotNull($row, 'getByDate must return the aggregated row.');
        $this->assertSame(
            '80.00',
            (string) $row->total,
            'getByDate total must be SUM(giftPrice)=80, not SUM(giftNum*giftPrice)=340.'
        );
    }

    // ─────────────── getFirstRoomByOwnerId: top sender in a room ───────────────

    /**
     * Two senders in a room. Sender A: qty 4 @ row total 40. Sender B: qty 2 @
     * row total 30. Top-by-SUM(giftPrice) is A (40 > 30) with total 40. The
     * squared path would rank B first (2*30=60 > 4*40=160? no — 160>60, still A)
     * so we assert the exact total value, which is the real discriminator.
     */
    public function test_getFirstRoomByOwnerId_returns_sum_gift_price(): void
    {
        $owner    = $this->makeUser();
        $senderA  = $this->makeUser();
        $senderB  = $this->makeUser();

        $this->insertGift(['roomowner_id' => $owner->id, 'sender_id' => $senderA->id, 'giftNum' => 4, 'giftPrice' => 40]);
        $this->insertGift(['roomowner_id' => $owner->id, 'sender_id' => $senderB->id, 'giftNum' => 2, 'giftPrice' => 30]);

        $row = (new GiftLogRepository())->getFirstRoomByOwnerId($owner->id);

        $this->assertNotNull($row, 'getFirstRoomByOwnerId must return a row.');
        $this->assertSame((int) $senderA->id, (int) $row->sender_id, 'Top sender must be A.');
        $this->assertSame(
            '40.00',
            (string) $row->total,
            'Top-sender total must be SUM(giftPrice)=40, not SUM(giftNum*giftPrice)=160.'
        );
    }

    // ─────────────── userGiftInfo: per-gift aggregate for a sender ───────────────

    /**
     * A sender sent the same gift twice: qty 5 (row total 50) and qty 2 (row
     * total 20). Grouped by giftId the total must be 70, not 5*50+2*20=290.
     */
    public function test_userGiftInfo_sums_gift_price_not_squared(): void
    {
        $sender   = $this->makeUser();
        $receiver = $this->makeUser();

        $this->insertGift(['sender_id' => $sender->id, 'receiver_id' => $receiver->id, 'giftId' => 7, 'giftNum' => 5, 'giftPrice' => 50]);
        $this->insertGift(['sender_id' => $sender->id, 'receiver_id' => $receiver->id, 'giftId' => 7, 'giftNum' => 2, 'giftPrice' => 20]);

        $page = (new GiftLogRepository())->userGiftInfo($sender->id, 'sender', null, null, 50, 1);

        $rows = collect($page->items())->where('giftId', 7);
        $this->assertTrue($rows->isNotEmpty(), 'userGiftInfo must return the giftId=7 aggregate.');

        $total = (string) $rows->first()->total;
        $this->assertSame(
            '70.00',
            $total,
            'userGiftInfo per-gift total must be SUM(giftPrice)=70, not SUM(giftNum*giftPrice)=290.'
        );
    }

    // ─────────────── getTopThreeSupport: ordering by SUM(giftPrice) ───────────────

    /**
     * getTopThreeSupport returns the top 3 senders for a receiver. We seed three
     * senders whose true totals (SUM(giftPrice)) order them A>B>C, but whose
     * quantities are arranged so a squared aggregation (SUM(giftNum*giftPrice))
     * would reorder them. Proving the returned order is A,B,C proves the correct
     * SUM(giftPrice) is used.
     *
     *   A: one row qty=1  total=100  -> giftPrice sum 100 ; squared 1*100 = 100
     *   B: one row qty=1  total=90   -> giftPrice sum 90  ; squared 1*90  = 90
     *   C: one row qty=10 total=80   -> giftPrice sum 80  ; squared 10*80 = 800  <-- would jump to #1
     */
    public function test_getTopThreeSupport_orders_by_sum_gift_price(): void
    {
        $receiver = $this->makeUser();
        $a = $this->makeUser();
        $b = $this->makeUser();
        $c = $this->makeUser();

        $this->insertGift(['receiver_id' => $receiver->id, 'sender_id' => $a->id, 'giftNum' => 1,  'giftPrice' => 100]);
        $this->insertGift(['receiver_id' => $receiver->id, 'sender_id' => $b->id, 'giftNum' => 1,  'giftPrice' => 90]);
        $this->insertGift(['receiver_id' => $receiver->id, 'sender_id' => $c->id, 'giftNum' => 10, 'giftPrice' => 80]);

        $top = (new UserHandling())->getTopThreeSupport($receiver->id);
        $ids = array_map(fn ($r) => (int) $r['id'], $top);

        $this->assertSame(
            [(int) $a->id, (int) $b->id, (int) $c->id],
            $ids,
            'Top supporters must order by SUM(giftPrice) [A,B,C]. A squared sum would push C (10*80=800) to #1.'
        );
    }
}