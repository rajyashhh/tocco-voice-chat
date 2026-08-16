<?php

namespace App\Admin\Extensions;

use App\Models\User;
use App\Models\UserSallary;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExporter implements FromCollection, WithColumnWidths, WithHeadings
{
    protected string $fileName = 'users_list.csv';

    public function collection(): \Illuminate\Support\Collection
    {
        $month = request('month', now()->month);
        $year = request('year', now()->year);
        $id = request('id');
     

        // Step 1: Query users and eager load agency
        $users = User::query()
            ->with('agency')
            ->whereNotNull('agency_id')
            ->where('agency_id', '!=', 0)
            ->when(request('agency_id'), fn($q) => $q->where('agency_id', request('agency_id')))
            ->when(request('id'), fn($q) => $q->where('id', request('id')))
            ->get();
        // Step 2: Query salaries in one shot and map by user_id
        $salaries = UserSallary::query()
            ->select([
                'user_id',
                DB::raw('MAX(target_id) AS target'),
                DB::raw('SUM(cut_amount) AS expenses'),
                DB::raw('SUM(achieved_diamond) AS achieved_diamond'),
                DB::raw('SUM(sallary) - SUM(cut_amount) AS salary'),
                DB::raw('MAX(days) AS achieved_days'),
                DB::raw('MAX(hours) AS achieved_hours'),
                DB::raw('MAX(extras) AS extras'),
                DB::raw('MAX(user_agency_id) AS user_agency_id'),
            ])
            ->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month)
            ->where('is_paid', 0)
            ->whereNotNull('user_agency_id')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $data = [];

        foreach ($users as $user) {
            $salary = $salaries[$user->id] ?? null;

            $extras = json_decode($salary->extras ?? '{}', true);
            $moment = $extras['moment'] ?? [];
            $reel = $extras['reel'] ?? [];

            $data[] = [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'diamonds' => (string) ($user->getTotalDiamond($month, $year) ?? 0) . ' 💎',
                'days' => $salary->achieved_days ?? '0/0',
                'hours' => $salary->achieved_hours ?? '0/0',
                'target' => ($salary->target ?? 0) ,
                'withdrawn' => $salary->expenses ?? 0,
                'salary' => round($salary?->salary ?? 0, 2). '💲',
                'year' => $year,
                'month' => $month,
                'moment' => $this->formatExtras($moment),
                'reel' => $this->formatExtras($reel),
                'agency' => @$user?->agency?->name ?? '-',
                'agency_id' => $user->agency?->id ?? '-',
           
            ];
        }

        // sort this data with diamond
        usort($data, function ($a, $b) {
            return (int) str_replace(',', '', $b['diamonds']) <=> (int) str_replace(',', '', $a['diamonds']);
        });

        return collect($data);
    }

    public function headings(): array
    {
        return [
            __('uuid', [], 'ar'),
            __('name', [], 'ar'),
            __('diamonds', [], 'ar'),
            __('days', [], 'ar'),
            __('hours', [], 'ar'),
            __('target', [], 'ar'),
            __('expenses', [], 'ar'),
            __('salary', [], 'ar'),
            __('year', [], 'ar'),
            __('month', [], 'ar'),
            __('moment', [], 'ar'),
            __('reels', [], 'ar'),
            __('agency', [], 'ar'),
            __('agency_id', [], 'ar'),
           
        ];
    }

    public function columnWidths(): array
    {
        return [
            'F' => 40,
            'G' => 100,
        ];
    }

    // protected function formatExtras(array $data): string
    // {
    //     return sprintf(
    //         "رفع: %s\nإعجاب: %s\nتعليق: %s",
    //         number_format((int) ($data['upload'] ?? 0)),
    //         number_format((int) ($data['likes'] ?? 0)),
    //         number_format((int) ($data['comments'] ?? 0))
    //     );
    // }

    protected function formatExtras(array $data): string
{
    return sprintf(
        "رفع: %s\nإعجاب: %s\nتعليق: %s",
        $data['upload'] ?? '0/0',
        $data['likes'] ?? '0/0',
        $data['comments'] ?? '0/0'
    );
}
}
