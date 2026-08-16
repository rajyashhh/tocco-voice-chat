<?php
namespace App\Traits;


use App\Models\PaymentGateway;

Trait PaymentGetWayTrait{
    
    public function paymentGateways()
    {
        return $this->belongsToMany(PaymentGateway::class, 'user_payment_gateways', 'user_id', 'payment_gateway_id')->withTimestamps();
    }

    public function AgencypaymentGateways()
    {
        return $this->belongsToMany(PaymentGateway::class, 'user_payment_gateways', 'agency_id', 'payment_gateway_id')->withTimestamps();
    }
}