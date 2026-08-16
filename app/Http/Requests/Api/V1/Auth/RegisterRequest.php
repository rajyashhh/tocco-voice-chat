<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            $rules['email'] = ['required','unique:users','email'];
            $rules['password'] = ['required'];
            $rules['uuid'] = ['nullable'];
        }elseif ($this->get ('type') == 'phone_pass'){
            $rules['phone'] = ['required'];
            // Server-code providers (twilio/whatsapp) verify a code generated
            // in the codes table; firebase (default) keeps the id-token flow.
            if ((new \App\Http\Services\OtpProviderService())->usesServerCode()) {
                $rules['code'] = ['required'];
            } else {
                $rules['firebase_id_token'] = ['required'];
            }
            $rules['password'] = ['required'];
            $rules['uuid'] = ['nullable'];
//            $rules['device_token']=['unique:users'];
        }elseif ($this->get ('type') == 'google'){
            $rules['google_id'] = ['required','unique:users'];
            $rules['uuid'] = ['nullable'];
        }elseif ($this->get ('type') == 'facebook'){
            $rules['facebook_id'] = ['required','unique:users'];
        }else{
            $rules['phone'] = ['required'];
            $rules['password'] = ['required'];
            $rules['uuid'] = ['nullable'];
        }

        $rules['lat'] = ['sometimes', 'numeric', 'between:-90,90'];
        $rules['long'] = ['sometimes', 'numeric', 'between:-180,180'];
        $rules['iso'] = ['sometimes', 'string', 'size:2'];

        return $rules;
    }


}
