<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable coin ledger for the shipping super admin layer. Written only by
 * App\Services\ShippingSuperAdminWalletService inside a DB::transaction. Two rows
 * per money move (an out leg and an in leg) share a single operation_uuid; the
 * unique (operation_uuid, type) index enforces idempotency.
 */
class ShippingAdminTransaction extends Model
{
    protected $table = 'shipping_admin_transactions';

    protected $guarded = [];

    protected $casts = [
        'coins'         => 'integer',
        'before_amount' => 'integer',
        'after_amount'  => 'integer',
        'created_at'    => 'datetime',
    ];

    // Leg types.
    public const FUND_OUT   = 'shipping_fund_out';
    public const FUND_IN    = 'shipping_fund_in';
    public const CHARGE_OUT = 'shipping_charge_out';
    public const CHARGE_IN  = 'shipping_charge_in';
}