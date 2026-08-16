<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatStoreRequest extends FormRequest
{
    public function authorize()
    {
        // Adjust authorization logic as needed
        return true;
    }

    public function rules()
    {
        return [
            'user_id'    => 'required|exists:users,id',
            'message'    => 'nullable|string',
            'message_id' => 'nullable|exists:chat_messages,id',
        ];
    }
}
