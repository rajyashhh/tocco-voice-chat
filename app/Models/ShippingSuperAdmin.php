<?php

namespace App\Models;

use App\Traits\CreatedByTrait;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Country\Entities\SuperAdmin;

/**
 * Shipping Super Admin — a coin-only layer that sits between the Country Manager
 * (App\Models\...SuperAdmin, type=superadmin) and the Shipping Agency
 * (App\Models\ShippingAgency). It is a single-table-inheritance model over
 * admin_users distinguished by type=shipping_super_admin.
 *
 * Coin-only by design: its balance is admin_users.di (integer coins). It has NO
 * dollar wallet, NO salary rows and NO users_wallets record. It is funded
 * exclusively by its parent Country Manager and is the sole charger of shipping
 * agencies within its country.
 */
class ShippingSuperAdmin extends Model
{
    use TimestampsWithTimezone, CreatedByTrait;

    public const TYPE = 'shipping_super_admin';

    protected $table = 'admin_users';

    protected $guarded = [];

    protected $attributes = [
        'type' => self::TYPE,
    ];

    protected static function booted(): void
    {
        self::addGlobalScope('shippingSuperAdminOnly', function (Builder $builder) {
            $builder->where('type', self::TYPE);
        });

        self::creating(function (self $model) {
            $model->type = self::TYPE;
        });
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'app_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The Country Manager that owns (and funds) this shipping super admin.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'parent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Shipping agencies (agencies.type = 2) inside this actor's country.
     */
    public function agencies()
    {
        return $this->hasMany(ShippingAgency::class, 'country_id', 'country_id');
    }
}