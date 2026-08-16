<?php
namespace Modules\Vip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetWareVipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vipPrivilege_id' => 'required|integer|exists:vip_privileges,id',
            'ovip_id' => 'required|integer|exists:o_vips,id',
        ];
    }
}
