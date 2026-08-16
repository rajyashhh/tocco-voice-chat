<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class GameProviderSetting extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'webhook_routes' => 'array',
        'extra_settings' => 'array',
    ];

    /**
     * In-request memo of decrypted secret values so a single request that reads
     * app_key/app_secret multiple times (signature build paths) decrypts once.
     */
    protected $decryptedCache = [];

    public function setAppKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['app_key'] = Crypt::encryptString($value);
            unset($this->decryptedCache['app_key']);
        }
    }

    public function getAppKeyAttribute($value)
    {
        return $this->decryptSecret('app_key', $value);
    }

    public function setAppSecretAttribute($value)
    {
        if ($value) {
            $this->attributes['app_secret'] = Crypt::encryptString($value);
            unset($this->decryptedCache['app_secret']);
        }
    }

    public function getAppSecretAttribute($value)
    {
        return $this->decryptSecret('app_secret', $value);
    }

    private function decryptSecret(string $key, $value)
    {
        if (!$value) {
            return null;
        }

        if (array_key_exists($key, $this->decryptedCache)) {
            return $this->decryptedCache[$key];
        }

        try {
            $decrypted = Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Pre-existing plaintext (or value not yet encrypted) — return as-is.
            $decrypted = $value;
        }

        return $this->decryptedCache[$key] = $decrypted;
    }
}
