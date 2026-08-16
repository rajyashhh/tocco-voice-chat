<?php
namespace Modules\Vip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BadgesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|numeric|min:1|max:5',
        ];
    }
}
