<?php

namespace Modules\AgencyApp\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class HostDailyDataExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return [
            'Date', 'Agency ID', 'UUID', 'Join Date', 'Name', 
            'Days', 'Hours', 'Visitors', 'Follows', 'Friends'
        ];
    }
}
