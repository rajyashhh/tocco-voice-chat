<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V2\Auth\ForgotPasswordController;
use App\Http\Middleware\AuthRateLimiter;
use App\Http\Services\OtpProviderService;
use App\Http\Services\WhatsappOtp;
use App\Models\Code;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA regression guards for the three OTP findings Adam raised and Karim fixed:
 *
 *   HIGH-1  brute-force: an unlimited number of wrong-code guesses could be made
 *           against a live OTP; IP rotation dodged any IP-based throttle. Fixed
 *           with a SERVER-SIDE per-code `attempts` counter that burns the code
 *           after MAX_VERIFY_ATTEMPTS (WhatsappOtp::isValidate).
 *
 *   HIGH-2  10/day cap was unenforceable because generateOtp HARD-DELETED the
 *           phone's previous rows, so getCodeInfo could never count more than the
 *           current row. Fixed by LOGICAL invalidation (used=true) — the day's
 *           rows survive to be counted.
 *
 *   MEDIUM-1 send-otp accepted arbitrary garbage and would generate/deliver a
 *           code for it. Fixed with strict E.164 validation (isValidE164) BEFORE
 *           any resource is consumed.
 *
 * DB strategy: these behaviours depend on real column defaults, aggregate
 * COUNT/MAX and row persistence, so the suite runs against the real
 * `meow_qa_test` MySQL schema (same as the UtdQa guards). Each test rolls back
 * on teardown. If the driver is not MySQL or the new columns are missing, the
 * test skips with a clear reason rather than a false green.
 */
class OtpSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private WhatsappOtp $otp;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'OTP security guards need the real MySQL test DB (column defaults, '
                . 'COUNT/MAX, row persistence). Current driver: ' . DB::connection()->getDriverName()
            );
        }

        foreach (['attempts', 'used'] as $col) {
            if (!Schema::hasColumn('codes', $col)) {
                $this->markTestSkipped(
                    "codes.`{$col}` missing — run the "
                    . '2026_08_14_000000_add_attempts_and_used_to_codes_table migration on the test DB first.'
                );
            }
        }

        $this->otp = new WhatsappOtp();
    }

    /** Force the server-code provider so verify() runs the bounded isValidate path. */
    private function useServerCodeProvider(): void
    {
        Cache::forever('phone_otp_provider', OtpProviderService::PROVIDER_WHATSAPP);
    }

    private function useFirebaseProvider(): void
    {
        Cache::forever('phone_otp_provider', OtpProviderService::PROVIDER_FIREBASE);
    }

    /** A random 6-digit uniqueish phone so parallel/leftover rows never collide. */
    private function freshPhone(): string
    {
        return '+1555' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------
    // HIGH-1 — brute-force is bounded per phone (survives IP rotation)
    // ------------------------------------------------------------------

    /**
     * 5 wrong guesses burn the code; the 6th guess WITH THE CORRECT CODE fails.
     *
     * OLD behaviour this would have caught: isValidate had no attempts counter
     * and never set used=true on a wrong guess, so a correct guess (or the
     * 1000001st brute-force guess) always succeeded within the validity window.
     * On the old code the final assertTrue-on-correct-code would pass, i.e. the
     * "burned" assertion below would FAIL — proving the vulnerability.
     */
    public function test_high1_five_wrong_attempts_burn_the_code_and_correct_code_then_fails(): void
    {
        $phone = $this->freshPhone();
        $code  = (string) $this->otp->generateOtp($phone)->code;
        $wrong = '000000'; // rand(100000,900000) can never equal this

        for ($i = 1; $i <= WhatsappOtp::MAX_VERIFY_ATTEMPTS; $i++) {
            $this->assertFalse(
                $this->otp->isValidate($phone, $wrong),
                "wrong guess #{$i} must be rejected"
            );
        }

        $row = Code::where('phone', $phone)->latest('id')->first();
        $this->assertSame(WhatsappOtp::MAX_VERIFY_ATTEMPTS, (int) $row->attempts, 'attempts must reach the cap');
        $this->assertTrue((bool) $row->used, 'code must be burned (used=true) after the cap');

        // The whole point: even the CORRECT code no longer works — user must resend.
        $this->assertFalse(
            $this->otp->isValidate($phone, $code),
            'HIGH-1: correct code must fail after the code is burned'
        );
    }

    /** Regression: the correct code on the very first attempt still succeeds. */
    public function test_high1_correct_code_first_attempt_succeeds(): void
    {
        $phone = $this->freshPhone();
        $code  = (string) $this->otp->generateOtp($phone)->code;

        $this->assertTrue($this->otp->isValidate($phone, $code), 'happy path must still verify');

        $row = Code::where('phone', $phone)->latest('id')->first();
        $this->assertSame(0, (int) $row->attempts, 'a correct guess must not increment attempts');
        $this->assertFalse((bool) $row->used, 'a correct verify must not, by itself, burn the code');
    }

    /**
     * The counter lives on the phone's code row, NOT on an IP. isValidate has no
     * IP parameter at all, and one phone's wrong guesses never touch another
     * phone's code — so an attacker rotating IPs cannot reset the barrier, and a
     * victim's number is isolated.
     */
    public function test_high1_counter_is_per_phone_not_per_ip(): void
    {
        $phoneA = $this->freshPhone();
        $phoneB = $this->freshPhone();

        $codeB = (string) $this->otp->generateOtp($phoneB)->code;
        $this->otp->generateOtp($phoneA);

        // Hammer phone A from "many IPs" — isValidate takes only (phone, code):
        // there is no IP dimension to rotate. All 5 land on the same phone row.
        for ($i = 0; $i < WhatsappOtp::MAX_VERIFY_ATTEMPTS; $i++) {
            $this->otp->isValidate($phoneA, '000000');
        }

        $this->assertTrue((bool) Code::where('phone', $phoneA)->latest('id')->first()->used, 'phone A burned');

        // Phone B untouched by phone A's attempts, and still verifiable.
        $rowB = Code::where('phone', $phoneB)->latest('id')->first();
        $this->assertSame(0, (int) $rowB->attempts, 'phone B attempts must stay 0');
        $this->assertFalse((bool) $rowB->used, 'phone B must not be burned by phone A');
        $this->assertTrue($this->otp->isValidate($phoneB, $codeB), 'phone B code still valid');
    }

    /**
     * ForgotPasswordController::verifyCode goes through the SAME bounded counter:
     * once the code is burned by 5 wrong guesses, verifyCode with the correct
     * code returns success=false. (Called directly to isolate the OTP counter
     * from the IP-based auth.rate.limit middleware on the route.)
     */
    public function test_high1_forgot_password_verifyCode_is_subject_to_the_same_counter(): void
    {
        $this->useServerCodeProvider();

        $phone = $this->freshPhone();
        $code  = (string) $this->otp->generateOtp($phone)->code;

        $controller = new ForgotPasswordController();

        for ($i = 1; $i <= WhatsappOtp::MAX_VERIFY_ATTEMPTS; $i++) {
            $resp = $controller->verifyCode(Request::create('/verify-code', 'POST', [
                'phone' => $phone,
                'code'  => '000000',
            ]))->getData(true);
            $this->assertFalse($resp['success'], "verifyCode wrong guess #{$i} must fail");
        }

        $resp = $controller->verifyCode(Request::create('/verify-code', 'POST', [
            'phone' => $phone,
            'code'  => $code,
        ]))->getData(true);

        $this->assertFalse(
            $resp['success'],
            'HIGH-1: verifyCode with the correct code must fail after the code is burned'
        );
    }

    // ------------------------------------------------------------------
    // HIGH-2 — 10/day cap is enforceable because rows are kept
    // ------------------------------------------------------------------

    /**
     * 10 codes generated in a day → the 11th send is refused, and the 10 rows
     * are STILL PRESENT (logically invalidated, not deleted).
     *
     * OLD behaviour this catches: generateOtp did
     * `Code::where('phone',$phone)->delete()` first, so at most 1 row per phone
     * ever existed and getCodeInfo->count was always 1 → assertCanSend could
     * never hit the 10 cap. On the old code the count assertion below would read
     * 1, not 10, and assertCanSend would NOT throw — proving the cap was dead.
     */
    public function test_high2_ten_per_day_cap_enforced_and_rows_are_not_deleted(): void
    {
        $phone = $this->freshPhone();

        for ($i = 0; $i < 10; $i++) {
            $this->otp->generateOtp($phone);
            // Slide the whole day's window back past the 2-minute gap so the next
            // generateOtp's assertCanSend (done in sendOtpMessage) isn't blocked
            // by the rate-of-attempts rule — we are isolating the 10/day cap.
            DB::table('codes')->where('phone', $phone)
                ->update(['created_at' => now()->subMinutes(10)->toDateTimeString()]);
        }

        $this->assertSame(10, Code::where('phone', $phone)->count(), 'all 10 rows must survive for counting');
        $this->assertSame(9, Code::where('phone', $phone)->where('used', true)->count(), '9 older codes logically invalidated');
        $this->assertSame(1, Code::where('phone', $phone)->where('used', false)->count(), 'only the latest stays live');

        $info = $this->otp->getCodeInfo($phone);
        $this->assertSame(10, (int) $info->count, 'getCodeInfo must count the kept rows');

        $threw = false;
        try {
            $this->otp->assertCanSend($phone); // the 11th attempt
        } catch (\Throwable $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'HIGH-2: the 11th send in a day must be refused');
    }

    /**
     * Generating a new code logically invalidates the previous one: the old code
     * fails verification, only the newest verifies. (Complements HIGH-2's "rows
     * survive" — surviving rows must still be unusable.)
     */
    public function test_high2_old_code_invalidated_only_latest_verifies(): void
    {
        $phone = $this->freshPhone();

        $old = (string) $this->otp->generateOtp($phone)->code;
        $new = (string) $this->otp->generateOtp($phone)->code;

        $this->assertFalse($this->otp->isValidate($phone, $old), 'old (used=true) code must fail');
        $this->assertTrue($this->otp->isValidate($phone, $new), 'newest code must verify');
        // Both rows still exist for the daily count.
        $this->assertSame(2, Code::where('phone', $phone)->count(), 'no hard delete on regenerate');
    }

    // ------------------------------------------------------------------
    // MEDIUM-1 — strict E.164 validation before any resource is spent
    // ------------------------------------------------------------------

    public function test_medium1_sendOtp_rejects_invalid_numbers_before_generating_a_code(): void
    {
        $this->useServerCodeProvider();
        Queue::fake(); // never touch the real WhatsApp delivery endpoint

        $service = new OtpProviderService();

        $badInputs = [
            'letters'    => 'abcdefgh',
            'too_short'  => '123',           // 3 digits < 8-digit floor
            'empty'      => '',
            'leading_0'  => '0123456789',    // E.164 first digit must be 1-9
            'too_long'   => '+1234567890123456', // 16 digits > 15 cap
        ];

        foreach ($badInputs as $label => $bad) {
            $threw = false;
            try {
                $service->sendOtp($bad);
            } catch (\Throwable $e) {
                $threw = true;
            }
            $this->assertTrue($threw, "sendOtp must reject invalid input: {$label}");
            $this->assertSame(
                0,
                Code::where('phone', OtpProviderService::normalizePhone($bad))->count(),
                "no code row may be created for invalid input: {$label}"
            );
        }
    }

    public function test_medium1_isValidE164_matrix(): void
    {
        // Valid (already normalized, no separators)
        $this->assertTrue(OtpProviderService::isValidE164('+15551234567'));
        $this->assertTrue(OtpProviderService::isValidE164('15551234'));      // 8 digits, floor
        $this->assertTrue(OtpProviderService::isValidE164('+123456789012345')); // 15 digits, ceiling

        // Invalid
        $this->assertFalse(OtpProviderService::isValidE164('abc'));
        $this->assertFalse(OtpProviderService::isValidE164('1234567'));      // 7 digits < floor
        $this->assertFalse(OtpProviderService::isValidE164('0123456789'));   // leading 0
        $this->assertFalse(OtpProviderService::isValidE164(''));
        $this->assertFalse(OtpProviderService::isValidE164('+1234567890123456')); // 16 digits
    }

    public function test_medium1_valid_e164_passes_and_generates_a_code(): void
    {
        $this->useServerCodeProvider();
        Queue::fake();

        $phone   = $this->freshPhone();
        $service = new OtpProviderService();

        $service->sendOtp($phone); // must not throw

        $this->assertSame(1, Code::where('phone', $phone)->count(), 'a valid number must generate exactly one code');
    }

    // ------------------------------------------------------------------
    // Firebase regression — the default path is untouched
    // ------------------------------------------------------------------

    /**
     * With the default (firebase) provider, usesServerCode() is false, so every
     * consumer takes the FirebaseValidate branch and never calls
     * isValidate/generateOtp/assertCanSend. This asserts the switch value that
     * gates all of those calls.
     */
    public function test_firebase_provider_does_not_use_server_code(): void
    {
        $this->useFirebaseProvider();
        $this->assertFalse((new OtpProviderService())->usesServerCode());

        // Also: an unknown/blank setting falls back to firebase (never server-code).
        Cache::forever('phone_otp_provider', 'something-unknown');
        $this->assertFalse((new OtpProviderService())->usesServerCode());
        Cache::forever('phone_otp_provider', '');
        $this->assertFalse((new OtpProviderService())->usesServerCode());
    }

    /**
     * The AuthRateLimiter phone normalization must be a no-op for clean E.164
     * numbers, so a firebase-path request lands in exactly the same rate-limit
     * bucket as before the fix (no behavioural drift for legitimate clients).
     */
    public function test_firebase_clean_e164_normalization_is_a_noop(): void
    {
        $clean = '+15551234567';
        $this->assertSame($clean, OtpProviderService::normalizePhone($clean));

        $limiter = new AuthRateLimiter();
        $ref = new \ReflectionMethod($limiter, 'getIdentifier');
        $ref->setAccessible(true);

        $id = $ref->invoke($limiter, Request::create('/register', 'POST', ['phone' => $clean]));
        $this->assertSame($clean, $id, 'clean E.164 phone bucket must be unchanged');

        // Separator-padded variant of the same number collapses to the same bucket.
        $padded = $ref->invoke($limiter, Request::create('/register', 'POST', ['phone' => '+1 (555) 123-4567']));
        $this->assertSame($clean, $padded, 'padded variant must share the clean bucket');
    }
}
