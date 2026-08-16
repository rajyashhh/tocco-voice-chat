<?php

namespace Modules\UsersWallet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class WalletTemplate extends Model
{
    use HasTranslations;

    public $translatable = ['title'];
    protected $fillable = ['title', 'type', 'minimum', 'transfer_fee'];

    public function fields(): HasMany
    {
        return $this->hasMany(WalletField::class, 'wallet_template_id');
    }
}
