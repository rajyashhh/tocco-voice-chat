<?php
namespace App\Traits;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

Trait RequestTrait{
    public function failedValidation ( Validator $validator )
    {
        throw new HttpResponseException(response()->json(
            [
                'success'   => false,

                'message'   => implode(',',$validator->errors()->all()),

                'data'      => $validator->errors()
            ],
            400
        ));
    }

    public function messages ()
    {
        return [
            'required'=>__ ('required'),
            'unique'=>__ ('exists')
        ];
    }
}
