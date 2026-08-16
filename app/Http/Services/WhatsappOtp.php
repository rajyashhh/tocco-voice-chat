<?php

namespace App\Http\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Code;
use App\Jobs\WhatsAppJob;
use Nette\Schema\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Log;


class WhatsappOtp
{
    /**
     * Wrong-code attempts allowed against a single OTP before it is burned and
     * the caller is forced to request a new one. Enforced SERVER-SIDE per phone
     * so IP rotation cannot brute-force a code within its validity window.
     */
    public const MAX_VERIFY_ATTEMPTS = 5;

    /**
     * @throws ValidationException
     */
    public function sendOtpMessage(string $phone)
    {
        $this->assertCanSend($phone);

        $otp     = $this->generateOtp($phone);
        //
        $message = $otp->code;
        // $message ='verification code is : '. $otp->code;
        dispatch(new WhatsAppJob($phone, $message));
    }

    /**
     * Shared OTP rate limit (10/day + 2 minutes between attempts) — also used
     * by OtpProviderService so every server-side OTP provider enforces the
     * exact same limits.
     *
     * @throws Exception
     */
    public function assertCanSend(string $phone): void
    {
        $data = $this->getCodeInfo($phone);

        if ($data?->count >= 10) {
            throw new \Exception(__('you spend all chances'));
        } else if (Carbon::createFromTimeString($data?->created_at ?? now()->copy()->subDay()->toDateTimeString())->addMinutes(2) > now()) {
            throw new Exception(__('wait-2-minutes'));
        }
    }

    /**
     * @return Model(['phone', 'created_at', 'count'])
     * */
    public function getCodeInfo(string $phone)
    {
        return Code::query()->selectRaw('phone, max(created_at) as created_at, count(code) as count')->where('phone', $phone)->whereDate('created_at', today())->groupBy('phone')->first();
    }

    public function generateOtp(string $phone)
    {
        // Logically invalidate any still-valid code for this phone instead of
        // hard-deleting the row: the day's rows must survive so getCodeInfo can
        // count them for the 10/day cap (HIGH-2). Only the freshly inserted code
        // stays verifiable, so an older code can never be replayed.
        Code::where('phone', $phone)->where('used', false)->update(['used' => true]);

        $otp = new Code();
        $otp->phone = $phone;
        $otp->code = rand(100000, 900000);
        $otp->save();
        return $otp;
    }

    /**
     * Verify a code against the phone's newest still-valid OTP. Every wrong guess
     * increments a SERVER-SIDE per-code counter; once MAX_VERIFY_ATTEMPTS wrong
     * guesses are made the code is burned (used=true) and the caller must request
     * a new one. This is the only brute-force barrier that survives IP rotation
     * (HIGH-1). Only the latest unused code within the 1-hour window is ever
     * considered valid, so invalidated/older codes always fail.
     */
    public function isValidate(string $phone, string $code): bool
    {
        $otp = Code::query()
            ->where('phone', $phone)
            ->where('used', false)
            ->where('created_at', '>', Carbon::now()->subHours()->toDate())
            ->latest('id')
            ->first();

        if (!$otp) {
            return false;
        }

        if ((string) $otp->code === (string) $code) {
            return true;
        }

        $otp->increment('attempts');
        if ($otp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            $otp->used = true;
            $otp->save();
        }

        return false;
    }

    public function resetCodes(string $phone)
    {
        return Code::where('phone', $phone)->delete();
    }
}
