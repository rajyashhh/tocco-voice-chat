<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ImageSizeRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $extension = strtolower($value->getClientOriginalExtension());
        $sizeKB = $value->getSize() / 1024;

        if ($extension === 'gif' && $sizeKB > 8192) {
            $fail(__('api_responses.gif_too_large'));
            return;
        }

        if ($extension !== 'gif' && $sizeKB > 5120) {
            $fail(__('api_responses.image_too_large'));
        }
    }
}