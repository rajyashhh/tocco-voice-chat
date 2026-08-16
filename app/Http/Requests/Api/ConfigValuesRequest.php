<?php

namespace App\Http\Requests\Api;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ConfigValuesRequest extends FormRequest
{
    use RequestTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'keys' => ['sometimes', 'array', 'max:11'],
            'enable-special' => ['sometimes', 'bool'],
        ];
    }
}
