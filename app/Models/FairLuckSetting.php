<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FairLuckSetting extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['key', 'value', 'description'];

    protected static function booted()
    {
        static::updated(function () {
            Cache::forget('fair_luck:settings');
        });
    }

    public static function getByKey(string $key, $default = null)
    {
        $settings = Cache::remember('fair_luck:settings', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });

        if (!isset($settings[$key])) {
            return $default;
        }

        $value = $settings[$key];

        // Basic JSON detection
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            return json_decode($value, true);
        }

        return $value;
    }

    public static function getReceiverFeeRate(): float
    {
        return (float) static::getByKey('fair_luck_receiver_fee_rate', 0.10);
    }

    /**
     * The vault negative limit used by EVERY solvency gate/settle — the single
     * source of truth. Owner-entered from the panel (key V7_negative_limit); it
     * is an absolute coin cap on how far the vault may go negative, NOT an RTP
     * knob. It is deliberately NOT scaled off turnover: a turnover-proportional
     * floor (the old 5%-of-24h-intake derivation) let the vault sink to tens of
     * millions and pin there, latching realised RTP at 0%.
     */
    public static function getVaultNegativeLimit(): int
    {
        return (int) static::getByKey('V7_negative_limit', 30_000);
    }

    /**
     * Owner cut rate (admin-configurable). Goes to the dedicated owner_wallet,
     * guaranteed and taken first on every bet. Default 1% per the product spec.
     */
    public static function getOwnerFeeRate(): float
    {
        return (float) static::getByKey('fair_luck_owner_fee_rate', 0.01);
    }

    /**
     * Panel Target RTP (default = the sustainable ceiling for 1% + 10% rates).
     * The engine never draws with this raw value — it uses
     * MultiplierTable::effectiveTargetRtp() (clamped + pity-adjusted).
     */
    public static function getTargetRTP(): float
    {
        return (float) static::getByKey('V7_target_rtp', 0.89);
    }
}
