<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Apply your policy logic if needed
    }

    public function rules()
    {
        return [
            'message_id' => 'required|exists:chat_messages,id',
            'message' => 'required', // Add message validation rules
        ];
    }
}
