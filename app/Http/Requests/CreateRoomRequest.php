<?php

namespace App\Http\Requests;

use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateRoomRequest extends FormRequest
{

    use RequestTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;//$this->user ()->can('create-room');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'numid'=>[
                'unique:rooms'
            ],
            'room_name'=>[
                'required'
            ]
        ];
    }


}
