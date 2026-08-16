<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        $rules = [];
        if ($this->get('type') == 'email_pass') {
            $rules['email']    = ['required', 'email'];
            $rules['password'] = ['required'];
            $rules['uuid'] = ['nullable'];
        } elseif ($this->get('type') == 'phone_pass') {
            $rules['phone']    = ['required'];
            $rules['password'] = ['required'];
            $rules['device_token'] = ['sometimes'];
            $rules['uuid'] = ['nullable'];
        } elseif ($this->get('type') == 'google') {
            $rules['google_id'] = ['required'];
            $rules['device_token'] = ['sometimes'];
            $rules['lat'] = ['sometimes', 'numeric', 'between:-90,90'];
            $rules['long'] = ['sometimes', 'numeric', 'between:-180,180'];
            $rules['iso'] = ['sometimes', 'string', 'size:2'];
            $rules['uuid'] = ['nullable'];
        } elseif ($this->get('type') == 'huawei') {
            $rules['huawei_id'] = ['required'];
            $rules['lat'] = ['sometimes', 'numeric', 'between:-90,90'];
            $rules['long'] = ['sometimes', 'numeric', 'between:-180,180'];
            $rules['iso'] = ['sometimes', 'string', 'size:2'];
            $rules['uuid'] = ['nullable'];
        } elseif ($this->get('type') == 'facebook') {
            $rules['facebook_id'] = ['required'];
        } elseif ($this->get('type') == 'phone_code') {
            $rules['phone'] = ['required'];
            $rules['code']  = ['required'];
            $rules['uuid'] = ['nullable'];
        } elseif ($this->get('type') == 'apple') {
            $rules['id_token']     = ['required'];
            $rules['device_token'] = ['sometimes'];
            $rules['email']        = ['sometimes'];
            $rules['name']         = ['sometimes'];
            $rules['lat'] = ['sometimes', 'numeric', 'between:-90,90'];
            $rules['long'] = ['sometimes', 'numeric', 'between:-180,180'];
            $rules['iso'] = ['sometimes', 'string', 'size:2'];
        } else {
            $rules['phone']    = ['required'];
            $rules['password'] = ['required'];
            $rules['uuid'] = ['nullable'];

        }
        return $rules;

    }

}
