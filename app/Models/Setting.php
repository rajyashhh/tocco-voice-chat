<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, TimestampsWithTimezone;

    public $table = 'settings';

    protected $guarded = [];

    /**
     * Central choke-point for the money kill-switches. Every write to the
     * settings table flows through this model (updateOrCreate / firstOrCreate /
     * save), so blocking a protected-key save here shuts down EVERY generic
     * settings writer at once — present or future — with no per-controller
     * patching. The dedicated guarded writers open the gate via
     * Common::withMoneyKeyWrite().
     *
     * The key is normalized (trim + lowercase) before comparison so a smuggled
     * variant like " Bd_Stop_Charge " cannot slip past a case/whitespace-
     * insensitive column collation.
     */
    protected static function booted(): void
    {
        static::saving(function (Setting $setting): void {
            $normalized = mb_strtolower(trim((string) $setting->key));

            if (
                in_array($normalized, \App\Helpers\Common::PROTECTED_MONEY_KEYS, true)
                && !\App\Helpers\Common::$moneyKeyWriteAllowed
            ) {
                throw new \RuntimeException(
                    'Protected money key write blocked outside sanctioned path: ' . $setting->key
                );
            }
        });
    }
}
