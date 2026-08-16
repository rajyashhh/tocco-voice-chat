<?php
namespace Modules\Vip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyVipPercentageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vip_id' => 'required|integer|exists:vips,id',
        ];
    }
}
