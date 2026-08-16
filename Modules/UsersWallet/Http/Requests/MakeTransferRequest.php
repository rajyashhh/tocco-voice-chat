<?php

namespace Modules\UsersWallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\UsersWallet\Enum\WalletEnum;

class MakeTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(WalletEnum::values())],
            'receiver_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric']
        ];
    }
}
