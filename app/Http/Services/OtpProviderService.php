<?php

namespace App\Http\Services;

use App\Helpers\Common;
use App\Jobs\WhatsAppJob;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Phone-OTP provider switch (settings key: phone_otp_provider).
 *
 *  - firebase (default): verification happens at Google via firebase_id_token;
 *    no server-side code exists. Every consumer keeps its original path.
 *  - twilio / whatsapp: the code is generated SERVER-SIDE through the same
 *    WhatsappOtp logic (codes table + 10/day + 2-minutes-between limits), so
 *    it always shows on the admin codes page — even when delivery fails or no
 *    credentials are configured (the owner can hand the code out manually).
 */
class OtpProviderService
{
    public const PROVIDER_FIREBASE = 'firebase';
    public const PROVIDER_TWILIO   = 'twilio';
    public const PROVIDER_WHATSAPP = 'whatsapp';

    private WhatsappOtp $whatsappOtp;

    public function __construct()
    {
        $this->whatsappOtp = new WhatsappOtp();
    }

    public function provider(): string
    {
        $provider = strtolower(trim((string) (Common::getSettingValue('phone_otp_provider') ?? '')));

        return in_array($provider, [self::PROVIDER_TWILIO, self::PROVIDER_WHATSAPP], true)
            ? $provider
            : self::PROVIDER_FIREBASE;
    }

    /**
     * Whether the active provider verifies against a server-generated code in
     * the codes table (twilio/whatsapp) instead of a Firebase id token.
     */
    public function usesServerCode(): bool
    {
        return $this->provider() !== self::PROVIDER_FIREBASE;
    }

    /**
     * Generate + persist the OTP FIRST (so the admin codes page always has it),
     * THEN attempt delivery. Delivery failure only logs a warning — it must
     * never block code generation. Same rate limits as WhatsappOtp.
     *
     * @throws Exception on rate limit (10/day, 2 minutes between attempts)
     */
    public function sendOtp(string $phone): void
    {
        $phone = $this->normalizePhone($phone);

        if (!self::isValidE164($phone)) {
            throw new Exception(__('api_responses.invalid_code'));
        }

        $this->whatsappOtp->assertCanSend($phone);

        $otp = $this->whatsappOtp->generateOtp($phone);

        if ($this->provider() === self::PROVIDER_TWILIO) {
            (new TwilioSms())->send($phone, __('Your verification code is: :code', ['code' => $otp->code]));
        } else if ($this->provider() === self::PROVIDER_WHATSAPP) {
            dispatch(new WhatsAppJob($phone, (string) $otp->code));
        } else {
            Log::warning('OtpProviderService: code generated while provider is firebase — no delivery channel, read it from the admin codes page', [
                'phone' => $phone,
            ]);
        }
    }

    public function verify(string $phone, string $code): bool
    {
        return $this->whatsappOtp->isValidate(self::normalizePhone($phone), $code);
    }

    /**
     * Invalidate the phone's codes after they served their purpose (successful
     * register / password reset / phone change) so a used code cannot be
     * replayed within its 1-hour validity window.
     */
    public function consume(string $phone): void
    {
        $this->whatsappOtp->resetCodes(self::normalizePhone($phone));
    }

    /**
     * Same separator stripping AuthService::registration applies, so the code
     * row written at send time always matches the phone checked at verify time.
     */
    public static function normalizePhone(string $phone): string
    {
        return str_replace([' ', '-', '/', '{', '}', '_', '(', ')'], '', $phone);
    }

    /**
     * Strict E.164 on an already-normalized number (optional leading +, first
     * digit 1-9, 8..15 digits total). Rejects garbage before any SMS is billed
     * so send-otp cannot be abused to spray arbitrary global numbers (MEDIUM-1).
     */
    public static function isValidE164(string $phone): bool
    {
        return (bool) preg_match('/^\+?[1-9]\d{7,14}$/', $phone);
    }
}