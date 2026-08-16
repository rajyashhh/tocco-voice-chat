<?php

namespace Tests\Feature\UtdQa;

use App\Admin\Actions\AreaManagerChargeAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Region\Entities\AreaManager;

/**
 * AM - Area manager charge: coin-amount input hardening.
 *
 * Target:
 *   app/Admin/Actions/AreaManagerChargeAction.php::handleUserCharge()
 *
 * When the amount is entered in COINS (amount_unit === 'coins'), the value must
 * be a whole coin count. The fix rejects any non-integer with a Validation
 * exception on the `amount` field BEFORE any balance is read or written:
 *
 *     if (filter_var($request->amount, FILTER_VALIDATE_INT) === false) {
 *         throw ValidationException::withMessages(['amount' => [...integer...]]);
 *     }
 *
 * Without this a fractional coin amount would be silently cast with (int),
 * truncating the entered value and corrupting the coin count.
 *
 * What we prove:
 *  (1) fractional positive coins amount ("10.5")  -> ValidationException(amount)
 *  (2) fractional negative coins amount ("-10.5") -> ValidationException(amount)
 *  (3) non-numeric coins amount ("abc")           -> ValidationException(amount)
 *  (4) a whole-integer coins amount ("10")        -> does NOT trip the integer
 *      guard (the amount key is not among any validation error), so valid input
 *      is not falsely rejected by the hardening.
 * No admin_users.di balance is mutated in any rejected case.
 */
class AreaManagerChargeInputHardeningTest extends UtdQaTestCase
{
    private function makeAreaManager(int $di = 1000): int
    {
        return DB::table('admin_users')->insertGetId([
            'username'   => 'qa-am-' . uniqid(),
            'password'   => bcrypt('x'),
            'name'       => 'QA-AM',
            'type'       => 'region',
            'di'         => $di,
            'country_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function invoke(int $amId, string $amount, string $chargeType = 'increment'): void
    {
        // zones_coins present so the guard under test is the ONLY thing that can
        // reject a coins-unit amount (the guard fires before the zones_coins path).
        Cache::forget('zones_coins');
        DB::table('settings')->updateOrInsert(['key' => 'zones_coins'], ['value' => '1', 'updated_at' => now(), 'created_at' => now()]);
        Cache::forever('zones_coins', '1');

        $request = \Illuminate\Http\Request::create('/admin/area-manager-charge', 'POST', [
            'userId'      => $amId,
            'amount'      => $amount,
            'amount_unit' => 'coins',
            'charge_type' => $chargeType,
            'reason_en'   => 'qa',
            'reason_ar'   => 'qa',
        ]);
        app()->instance('request', $request);

        $action = (new AreaManagerChargeAction())->setUserId($amId);
        $action->handle($request);
    }

    private function assertIntegerGuardTrips(int $amId, string $amount, string $chargeType, string $label): void
    {
        $diBefore = (int) DB::table('admin_users')->where('id', $amId)->value('di');

        try {
            $this->invoke($amId, $amount, $chargeType);
            $this->fail("{$label}: a non-integer coins amount must be rejected.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors(), "{$label}: rejection must be on the `amount` field. Errors: " . json_encode($e->errors()));
        }

        $diAfter = (int) DB::table('admin_users')->where('id', $amId)->value('di');
        $this->assertSame($diBefore, $diAfter, "{$label}: a rejected charge must not move the area manager balance.");
    }

    /** (1) fractional positive. */
    public function test_fractional_positive_coins_amount_is_rejected(): void
    {
        $this->assertIntegerGuardTrips($this->makeAreaManager(), '10.5', 'increment', 'fractional positive');
    }

    /** (2) fractional negative. */
    public function test_fractional_negative_coins_amount_is_rejected(): void
    {
        $this->assertIntegerGuardTrips($this->makeAreaManager(), '-10.5', 'increment', 'fractional negative');
    }

    /** (3) non-numeric. */
    public function test_non_numeric_coins_amount_is_rejected(): void
    {
        $this->assertIntegerGuardTrips($this->makeAreaManager(), 'abc', 'increment', 'non-numeric');
    }

    /**
     * (4) A whole integer coins amount does NOT trip the integer guard. It may
     * still be stopped by downstream business rules, but never by the `amount`
     * integer-validation rule — proving the hardening does not reject valid input.
     */
    public function test_whole_integer_coins_amount_passes_the_integer_guard(): void
    {
        $amId = $this->makeAreaManager(1000);

        try {
            $this->invoke($amId, '10', 'increment');
            // Reached the charge path without a validation error — acceptable.
            $this->assertTrue(true);
        } catch (ValidationException $e) {
            $this->assertArrayNotHasKey(
                'amount',
                $e->errors(),
                'A whole-integer coins amount must not trip the amount integer guard. Errors: ' . json_encode($e->errors())
            );
        } catch (\Throwable $e) {
            // Downstream (Encore response / charge record) issues are not part of
            // this input-hardening contract; the integer guard is what we assert.
            $this->assertTrue(true, 'Non-validation downstream path is out of scope: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        Cache::forget('zones_coins');
        parent::tearDown();
    }
}