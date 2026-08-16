<?php

namespace App\Http\Requests\Api\V2\Auth;

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
        }elseif ($this->get ('type') == 'phone_pass'){
            $rules['phone'] = ['required'];
            $rules['password'] = ['required'];
            $rules['firebase_id_token'] = ['required'];
//            $rules['device_token']=['unique:users'];
        }elseif ($this->get ('type') == 'google'){
            $rules['google_id'] = ['required','unique:users'];
        }elseif ($this->get ('type') == 'facebook'){
            $rules['facebook_id'] = ['required','unique:users'];
        }else{
            $rules['phone'] = ['required','unique:users'];
            $rules['password'] = ['required'];
        }
        return $rules;
    }


}
