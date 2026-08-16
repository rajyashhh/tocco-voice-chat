<?php

namespace Modules\Vip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWareVipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'name_en'          => 'required|string|max:255',
            'title'            => 'nullable|string|max:255',
            'title_en'         => 'nullable|string|max:255',
            'image'            => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'img2'             => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'img2_type'        => 'nullable|string',
            'vipPrivilege_id'  => 'required|integer|exists:vip_privileges,id',
            'ovip_id'          => 'required|integer|exists:o_vips,id',
            'ware_id'          => 'nullable|integer|exists:wares,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => __('validation.required', ['attribute' => 'name']),
            'name_en.required'         => __('validation.required', ['attribute' => 'name_en']),
            'image.required'           => __('validation.required', ['attribute' => 'image']),
            'img2.required'            => __('validation.required', ['attribute' => 'img2']),
            'vipPrivilege_id.required' => __('validation.required', ['attribute' => 'vipPrivilege_id']),
            'ovip_id.required'         => __('validation.required', ['attribute' => 'ovip_id']),
        ];
    }
}
