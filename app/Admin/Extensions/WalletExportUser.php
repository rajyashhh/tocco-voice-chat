<?php

namespace App\Admin\Extensions;

use App\Models\User;
use App\Models\Agency;
use App\Models\UserSallary;
use App\Models\AgencySallary;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;


class WalletExportUser  implements FromCollection, WithHeadings
{




    protected $fileName = 'users_list.csv';
    protected $headings = [
        "id",
        "name",
        'balance',
        'expenses',
        'salary',

    ];

    public $uuid;
    protected $month;
    protected $year;


    public function __construct($uuid = null, $month = null, $year = null)
    {
        $this->uuid = $uuid;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $uuid = $this->uuid;
        $month = $this->month;
        $year = $this->year;
        $query = User::query();

        if ($this->uuid) {
            $query->where('uuid',  $uuid);
        }

        $users = $query->get();
      

        $arr = [];

        foreach ($users as $user) {

            $arr[] = [
                'id' => $user->id,
                'name' => $user->name,
                'balance' => round($user->sumNetSalary($month, $year) ?? 0, 2) . '💲',
                'withdrawal' => round($user->sumCutAmount($month, $year)?? 0, 2) . '💲',
                'salary' => round($user->sumSalary($month, $year) ?? 0, 2) . '💲',

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
