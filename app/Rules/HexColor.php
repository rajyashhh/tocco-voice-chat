<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Rejects any color that is not a canonical hex value (#RRGGBB or #RRGGBBAA).
 * Bad colors entered by admins crash the mobile app at build time, so we
 * block them at the source. Empty values pass — combine with `nullable`
 * for optional fields.
 */
class HexColor implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (bool) preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', (string) $value);
    }

    public function message()
    {
        return 'يجب إدخال لون صحيح بصيغة hex مثل #1A2B3C (٦ أو ٨ خانات مع #).';
    }
}
