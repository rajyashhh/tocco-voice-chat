<?php

namespace App\Admin\Extensions;

use App\Models\User;
use App\Models\Agency;
use App\Models\AgencySallary;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\FromCollection;


class AgencyExporter  implements FromCollection,WithHeadings
{



    public $agency_id;
    protected $fileName = 'agencies_list.csv';
    protected $headings = [
        "id",
        "name",
        'salary',
        'expenses',
        'net salary',
        'agent',
        'month',
        'year',
        'hosts'
    ];
    public $month;
    public $year;


    public function __construct($id = null, $month = null, $year = null)
    {
        $this->agency_id = $id;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
            $query = Agency::withCount('users');
        
            if ($this->agency_id) {
                $query->where('id', $this->agency_id);
            }
        
            $agencies = $query->get();
        
            $arr = [];
        
            foreach ($agencies as $agency) {
                $target = $agency->target($this->month, $this->year);
        
                $agencySalarys = AgencySallary::query()
                    ->where('agency_id', $agency->id)
                    ->where('is_paid', 0)
                    ->when($this->month, fn($q) => $q->where('month', '<=', $this->month))
                    ->when($this->year, fn($q) => $q->where('year', '<=', $this->year))
                    ->select(
                        DB::raw('SUM(`sallary`) AS target'),
                        DB::raw('SUM(`cut_amount`) AS expenses'),
                        DB::raw('SUM(sallary) - SUM(cut_amount) AS salary')
                    )
                    ->groupBy('agency_id')
                    ->first();
        
                $arr[] = [
                    'id' => $agency->id,
                    'name' => $agency->name,
                    'salary' => round($agencySalarys->target ?? 0, 2),
                    'expenses' => round($agencySalarys->expenses ?? 0, 2),
                    'net_salary' => round($agencySalarys->salary ?? 0, 2),
                    'agent' => $agency->owner->name ?? $agency->dashOwner->name ?? '-',
                    'month' => $target->month ?? $this->month,
                    'year' => $target->year ?? $this->year,
                    'hosts' => $agency->users_count ?? 0
                ];
            }
        
            return collect($arr);
        
        
    }


    public function headings(): array
    {
        return [
            __("id", [], 'ar'),
            __('name', [], 'ar'),
            __('salary', [], 'ar'),
            __('expenses', [], 'ar'),
            __('Net Salary', [], 'ar'),
            __('agent', [], 'ar'),
            __('month', [], 'ar'),
            __('year', [], 'ar'),
            __('dashboard.hosts', [], 'ar'),
            
        ];
    }
}
