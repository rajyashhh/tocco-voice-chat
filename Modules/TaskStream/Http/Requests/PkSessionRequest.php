<?php

namespace Modules\TaskStream\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PkSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'team_1' => ['required', 'array'],
            'team_1.*' => ['required', 'integer', 'distinct'],
            'team_2' => ['required', 'array'],
            'team_2.*' => ['required', 'integer', 'distinct'],
            'duration' => ['required', 'integer']
        ];
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $team1 = $this->input('team_1', []);
            $team2 = $this->input('team_2', []);

            $duplicates = array_intersect($team1, $team2);

            if (!empty($duplicates)) {
                $validator->errors()->add(
                    'team_2',
                    __('A room cannot appear in both teams: ') . implode(', ', $duplicates)
                );
            }
        });
    }
}
