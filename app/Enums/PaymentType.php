<?php

namespace App\Enums;

enum PaymentType: string
{
    case FAWRY = 'fawry';
        // case GOOGLE_PAY = 'google_pay';
    case SKY_PAY = 'sky_pay';
    case STRIP = 'stripe';
    case OPAY = 'opay';
    case GOOGLE_PAY  = 'google_pay';




    public static function getOptions(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Method to get translated options
    public static function getTranslatedOptions(): array
    {
        // Preserve original values as keys
        $formattedOptions = [];

        foreach (self::getOptions() as $value) {
            $formattedOptions[$value] = str_replace('_', ' ', $value);
        }

        return translateCategory($formattedOptions);
    }
}
