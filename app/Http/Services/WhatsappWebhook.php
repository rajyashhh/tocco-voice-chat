<?php

namespace App\Http\Services;

use App\Models\WhatsappWebhookValidate;
use http\Exception\RuntimeException;

class WhatsappWebhook
{

    public function isValidated(?string $phone)
    {
        if ($phone == null) throw new RuntimeException('Please provide a valid phone');
        return WhatsappWebhookValidate::validated()->where('phone_number', $phone)->exists();
    }


    public function getLastValidatedPhone(?string $phone) : null | WhatsappWebhookValidate
    {
        if ($phone == null) throw new RuntimeException('Please provide a valid phone');
        $whatsappWebhookValidate =
            WhatsappWebhookValidate::validated()->where('phone_number', $phone)->latest()->first();
        $this->resetValidations($phone);
        return $whatsappWebhookValidate;
    }

    public function resetValidations(?string $phone)
    {
        if ($phone == null) throw new RuntimeException('Please provide a valid phone');
        WhatsappWebhookValidate::validated()->where('phone_number', $phone)->delete();
    }
}
