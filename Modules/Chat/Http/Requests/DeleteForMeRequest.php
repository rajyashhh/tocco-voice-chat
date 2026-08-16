<?php
namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteForMeRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'id' => 'required|array',
            'id.*' => 'integer|exists:chat_messages,id',
        ];
    }
}
