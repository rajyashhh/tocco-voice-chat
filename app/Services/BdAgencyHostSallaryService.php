<?php
namespace App\Services;

use App\Models\Bd;
use App\Models\BdAgencyHostSallary;
use App\Models\BdSalary;

class BdAgencyHostSallaryService
{
    public static function storeOrUpdate(array $data): void
    {

        self::storeSallaryLine($data);
        $total = self::calculateTotalSallary($data['bd_id'], $data['month'], $data['year']);
        self::storeOrUpdateBdSalary($data['bd_id'], $data['month'], $data['year'], $total);
    }


    protected static function calculateTotalSallary(int $bdId, int $month, int $year): float
    {
        $subQuery = BdAgencyHostSallary::where('bd_id', $bdId)
                                        ->where('month', $month)
                                        ->where('year', $year)
                                        ->sum('amount');
        return  $subQuery;
          
    }

    protected static function storeOrUpdateBdSalary(int $bdId, int $month, int $year, float $salary): void
    {
        $bdSalary = BdSalary::query()
            ->where([
                'bd_id' => $bdId,
                'month' => $month,
                'year'  => $year,
            ])
            ->lock()
            ->first();

        if ($bdSalary) {
            $bdSalary->update([
                'salary' => $salary,
            ]);
        } else {
            BdSalary::create([
                'bd_id'  => $bdId,
                'salary' => $salary,
                'month'  => $month,
                'year'   => $year,
            ]);
        }
    }



    protected static function storeSallaryLine(array $data): void
    {
        $bdUserId = self::getBdAppId($data['bd_id']);
    
        $latestRecord = self::getLatestSallaryRecord($data);
    
        if (self::shouldSkipInsert($latestRecord, $data)) {
            return;
        }
    
        $amount = floatval($data['amount']);
        $oldDbValue = floatval($data['oldDbValue'] ?? 0);
    
        $difference = $amount - $oldDbValue;
    
        // if ($difference <= 0.00001) {
        //     return;
        // }
    
        $attributes = self::buildAttributes($data, $bdUserId);
        $newSalary = Self::getOldSalary($data['bd_id'],$data['user_id']);
        $values = self::buildValues($data, $difference,$newSalary);
    
        BdAgencyHostSallary::create(array_merge($attributes, $values));
    }
    


    protected static function getLatestSallaryRecord(array $data): ?BdAgencyHostSallary
    {
        return BdAgencyHostSallary::where('user_id', $data['user_id'])
            ->where('agency_id', $data['agency_id'])
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->orderByDesc('id')
            ->first();
    }

    protected static function shouldSkipInsert(?BdAgencyHostSallary $latestRecord, array $data): bool
    {
        if (!$latestRecord) {
            return false;
        }
    
        $newAmount = floatval($data['amount']);
        $oldAmount = floatval($data['oldDbValue'] ?? 0);
    
        $sameAmount = $latestRecord->amount == ($newAmount - $oldAmount);
        $sameBd = intval($latestRecord->bd_id) === intval($data['bd_id']);
    
        return $sameAmount && $sameBd;
    }

    protected static function buildAttributes(array $data, int $bdUserId): array
    {
        return [
            'bd_id'       => $data['bd_id'],
            'bd_user_id'  => $bdUserId,
            'user_id'     => $data['user_id'],
            'agency_id'   => $data['agency_id'],
            'month'       => $data['month'],
            'year'        => $data['year'],
        ];
    }
    protected static function buildValues(array $data, float $difference,$newSalary): array
    {
        return [
            'amount' => $difference,
            'salary' => $newSalary + $difference,

        ];
    }


    protected static function getBdAppId(int $bdId): int
    {
        return Bd::find($bdId)?->app_id ?? 0;
    }

    
    
    protected static function getOldSalary(int $bdId ,$userId): float
    {
        return BdAgencyHostSallary::
             where('bd_id', $bdId)
             ->where('user_id', $userId)
            ->orderByDesc('id')
            ->value('salary') ?? 0;
    }
    





}
