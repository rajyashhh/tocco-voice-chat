<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic if necessary
    }

    public function rules()
    {
        return [
            'message_id' => 'required|exists:chat_messages,id',
            'react' => 'required|string',
        ];
    }
}
