<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
    public function rules()
    {
        return [
            // 'email'=>[Rule::unique('users')->ignore($this->user()->id),'email'],
            'phone'=>[Rule::unique('users')->ignore($this->user()->id)],
            'uuid' => ['nullable', Rule::unique('users')->ignore($this->user()?->id)],

        ];
    }
}
