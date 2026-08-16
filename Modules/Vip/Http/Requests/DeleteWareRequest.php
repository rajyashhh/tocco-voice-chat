<?php
namespace Modules\Vip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteWareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ware_id' => 'required|integer|exists:wares,id',
        ];
    }
}
