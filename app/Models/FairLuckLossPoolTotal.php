<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FairLuckLossPoolTotal extends Model
{
    use HasFactory;

    protected $table = 'fairluck_loss_pool_totals';

    protected $fillable = [
        'balance',
        'lifetime_contributed',
        'lifetime_paid_out',
    ];
}
