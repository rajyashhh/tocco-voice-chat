<?php

namespace App\Http\Services;

use App\Helpers\Common;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio SMS delivery client. Credentials live in the SETTINGS table
 * (twilio_account_sid / twilio_auth_token / twilio_from_number — saved from
 * the third-party panel tab, secrets follow the blank-keeps-current pattern).
 * Delivery is best-effort by design: the OTP row is persisted BEFORE this is
 * called, so a missing credential or Twilio failure only logs a warning and
 * the admin can still read the code from the codes page.
 */
class TwilioSms
{
    public function send(string $phone, string $message): bool
    {
        $sid   = (string) (Common::getSettingValue('twilio_account_sid') ?? '');
        $token = (string) (Common::getSettingValue('twilio_auth_token') ?? '');
        $from  = (string) (Common::getSettingValue('twilio_from_number') ?? '');

        if (trim($sid) === '' || trim($token) === '' || trim($from) === '') {
            Log::warning('TwilioSms: credentials missing — SMS not sent, code remains readable on the admin codes page', [
                'phone' => $phone,
            ]);
            return false;
        }

        // Twilio requires E.164 (+<dialcode><number>); phones are stored with
        // the dial code but usually without the plus.
        $to = str_starts_with($phone, '+') ? $phone : '+' . $phone;

        try {
            $response = Http::timeout(10)
                ->withBasicAuth(trim($sid), trim($token))
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode(trim($sid)) . '/Messages.json', [
                    'To'   => $to,
                    'From' => trim($from),
                    'Body' => $message,
                ]);

            if (!$response->successful()) {
                Log::warning('TwilioSms: send failed', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'error'  => $response->json('message') ?? $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('TwilioSms: send exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
