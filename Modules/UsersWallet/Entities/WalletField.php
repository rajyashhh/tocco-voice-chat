<?php

namespace Modules\UsersWallet\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class WalletField extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'placeholder'];
    protected $fillable = ['wallet_template_id', 'title', 'type', 'is_required', 'order', 'placeholder'];
}
