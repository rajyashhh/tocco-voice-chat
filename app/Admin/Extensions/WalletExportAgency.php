<?php

namespace App\Admin\Extensions;

use App\Models\User;
use App\Models\Agency;
use App\Models\AgencySallary;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\FromCollection;


class WalletExportAgency  implements FromCollection, WithHeadings
{




    protected $fileName = 'agencies_list.csv';
    protected $headings = [
        "id",
        "name",
        'balance',
        'withdrawal',
        'alary',

    ];

    public $id;
    protected $month;
    protected $year;


    public function __construct($id = null, $month = null, $year = null)
    {
        $this->id = $id;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $id = $this->id;
        $month = $this->month;
        $year = $this->year;
        $query = Agency::query();
        if ($this->id) {

            $query->where('id', $id) // Match directly on agency_id
                ->orWhereHas('owner', function ($subQuery) use ($id) {
                    $subQuery->where('uuid', $id); // Match on related owner UUID
                });
        }

        $agencies = $query->get();

        $arr = [];

        foreach ($agencies as $agency) {



            $arr[] = [
                'id' => $agency->id,
                'name' => $agency->name,
                'balance' => round($agency->sumNetSalary($month, $year) ?? 0, 2) . '💲',
                'withdrawal' => round($agency->sumCutAmount($month, $year) ?? 0, 2) . '💲',
                'salary' => round($agency->sumSalary($month, $year) ?? 0, 2) . '💲',
            ];
        }

        return collect($arr);
    }


    public function headings(): array
    {
        return [
            __("id", [], 'ar'),
            __('name', [], 'ar'),
            __('net salary', [], 'ar'),
            __('withdrawal', [], 'ar'),
            __('salary', [], 'ar'),

        ];
    }
}
