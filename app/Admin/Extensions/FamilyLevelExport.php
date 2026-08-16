<?php

namespace App\Admin\Extensions;

use App\Models\FamilyLevel;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\FromCollection;


class FamilyLevelExport  implements FromCollection, WithHeadings
{

    protected $fileName = 'family_levels_list.csv';
    protected $headings = [
        "id",
        "name",
        'members',
        "admins",
        'exp',
    ];

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $familyLevels = FamilyLevel::get();
        $arr = [];

        foreach ($familyLevels as $familyLevel) {


            $arr[] = [
                'id' => $familyLevel->id,
                'name' => $familyLevel->name,
                'exp' => $familyLevel->exp,
                'members' => $familyLevel->members,
                'admins' => $familyLevel->admins,
            ];
        }

        return collect($arr);
    }


    public function headings(): array
    {
        return [
            __("id", [], 'ar'),
            __('name', [], 'ar'),
            __('exp', [], 'ar'),
            __('members', [], 'ar'),
            __('admins', [], 'ar'),
        ];
    }
}
