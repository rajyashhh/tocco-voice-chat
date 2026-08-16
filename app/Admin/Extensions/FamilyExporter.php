<?php

namespace App\Admin\Extensions;

use App\Models\Family;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;


class FamilyExporter implements FromCollection, WithHeadings
{
    protected $fileName = 'families.csv';
    protected $headings = [
        "id",
        "name",
        'owner',
        'num',
        'num_admins',
        'level',
        'exp',
        'created_at',
    ];
    public $date;
    public $id;
    public $uuid;


    public function __construct($date = null, $id = null,  $uuid = null)
    {

        $this->id = $id;
        $this->date = $date;
        $this->uuid = $uuid;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $id = $this->id;
        $date =  $this->date;
        $uuid = $this->uuid;


        $families = Family::query()->with('owner')
            ->when($id, fn($q) => $q->where('id', $id))
            ->when($uuid, fn($q) => $q->whereHas('owner', fn($sub) => $sub->searchByUuid($uuid)))
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->get();
        $arr = [];

        foreach ($families as $family) {

            $arr[] = [
                'id' => $family->id,
                'name' => $family->name ?? '',
                'owner' => $family->owner->name ?? '',
                'num' => $family->members_count . '/' . ($family->num_admins ?? 0),
                'num_admins' => $family->admins_num . '/' . ($family->num_admins ?? 0),
                'max_level' => $family->max_level ?? '',
                'max_exp' => $family->max_exp,
                'created_at' => $family->created_at,


            ];
        }


        return collect($arr);
    }


    public function headings(): array
    {
        $headings = [
            __("id", [], 'ar'),
            __('name', [], 'ar'),
            __('owner', [], 'ar'),
            __('number of people', [], 'ar'),
            __('number of admins', [], 'ar'),
            __('level', [], 'ar'),
            __('exp', [], 'ar'),
            __('created_at', [], 'ar'),
        ];

        return $headings;
    }
}
