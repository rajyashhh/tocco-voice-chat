<?php

namespace Modules\AgencyApp\Http\Requests;

use App\Traits\RequestTrait;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgencyRequest extends FormRequest
{

    use RequestTrait;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'phone' => 'required',
            'img' => 'nullable|mimes:jpg,jpeg,png',
            'email' => [
                'nullable',
                'email',
                function ($attribute, $value, $fail) {
                    if ($value && !str_contains($value, '@gmail.com')) {
                        $fail($attribute.' must be a valid Gmail address.');
                    }
                },
            ],
            'face_image' => 'required|mimes:jpg,jpeg,png',
            'back_image' => 'required|mimes:jpg,jpeg,png',
            'country' => 'nullable',
            'apps' => 'required',
            'salary' => 'required|integer',
            'host' => 'required|integer', 
            'uuid'=> 'nullable|exists:users,uuid',
            'video'=>'nullable|file|mimes:mp4,ogx,oga,ogv,ogg,webm'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}