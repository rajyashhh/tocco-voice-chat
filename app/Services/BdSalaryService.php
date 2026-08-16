<?php
namespace App\Services;

use App\Models\BdSalary;

class BdSalaryService
{
    public static function storeOrUpdate(array $data): BdSalary
    {
        $attributes = [
            'bd_id' => $data['bd_id'],
            'month' => $data['month'],
            'year'  => $data['year'],
        ];

        $values = [
            'salary'          => $data['salary'] ?? 0,
            'cut_amount' => $data['deducted_salary'] ?? 0,
        ];

        return BdSalary::updateOrCreate($attributes, $values);
    }
}
