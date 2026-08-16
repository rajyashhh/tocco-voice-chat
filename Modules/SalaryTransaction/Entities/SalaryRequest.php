<?php

namespace Modules\SalaryTransaction\Entities;

use App\Models\Agency;
use App\Models\Country;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryRequest extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function payment_gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function agencyOwner()
    {
        return $this->belongsTo(User::class, 'agency_owner_id');
    }
}
