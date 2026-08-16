<?php

namespace Modules\TribeReward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendUserRewardRequest extends FormRequest
{
    public function rules()
    {
        return [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'integer', 'exists:users,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
