<?php

namespace App\Admin\Extensions;

use App\Facades\ManagerHelper;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\Agency;
use App\Models\AgencySallary;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\FromCollection;

class AgencyMangerExporter implements FromCollection, WithHeadings
{
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function collection()
    {
        $managers = AdminUser::where('app_id', '!=', 0)
            ->when($this->userId, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('id', $this->userId);
                });
            })
            ->get();

        $arr = [];

        foreach ($managers as $manager) {
            $user = $manager->user;
            $uuid = $user?->uuid;
            $name = $user?->name;
            $app = $manager->app?->name ?? '---';
            $agencies = $manager->managerAgenciesWithoutScope()->get();

            $totalSalary = \App\Facades\ManagerHelper::getTotalAgenciesSalary($agencies, $manager->app_id);
            $agencyCount = $agencies->count();

            $arr[] = [
                'id' => $manager->id,
                'name' => $name,
                'uuid' => $uuid,
                'agency_count' => $agencyCount,
                'total_salary' => round($totalSalary, 2),
            ];
        }

        return collect($arr);
    }

    public function headings(): array
    {
        return [
            __('Id', [], 'ar'),
            __('Name', [], 'ar'),
            __('UUID', [], 'ar'),
            __('Number of Agencies', [], 'ar'),
            __('Total Due Salary', [], 'ar'),
        ];
    }
}

