<?php

namespace App\Jobs;


use App\Models\Agency;
use App\Models\Bd;
use App\Models\BDSallary;
use App\Models\BdAgencyHostSallary;
use App\Models\BdSalary;
use App\Models\Charge;
use App\Models\UserSallary;
use App\Services\BdAgencyHostSallaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class MigrateOldBdSalariesJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue, Queueable, SerializesModels;




    public function handle(): void
    {
        $bdSalaries = BDSallary::all();


        DB::beginTransaction();

        try {
            foreach ($bdSalaries as $bdSalary) {
                $bdAppId = $bdSalary->bd_id;
                $bdId    = self::getBdUserId($bdAppId);
                $agencyId = $bdSalary->agency_id;
                $month    = $bdSalary->month;
                $year     = $bdSalary->year;
    
                if ($bdId == 51) {
                }
                // 1) حفظ الرواتب الخاصة بالمستخدمين (زي ما هو)
                $userSalaries = UserSallary::where('user_agency_id', $agencyId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->get();
    
                foreach ($userSalaries as $userSallary) {
                    BdAgencyHostSallary::updateOrCreate(
                        [
                            'bd_id'     => $bdId,
                            'agency_id' => $agencyId,
                            'user_id'   => $userSallary->user_id,
                            'month'     => $month,
                            'year'      => $year,
                        ],
                        [
                            'amount'        => $userSallary?->dB ?? 0,
                            'user_sallary'  => $userSallary->sallary,
                            'agency_sallary'=> $userSallary->agency_sallary,
                            'bd_user_id'    => $bdAppId,
                            'created_at'    => $userSallary->created_at,
                        ]
                    );
                }
    
                if ($bdId == 51) {
                }
                // 2) اجمع كل الرواتب لنفس الـ bd_id + الشهر + السنة
                $totals = BDSallary::where('bd_id', $bdAppId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->selectRaw('SUM(sallary) as total_salary, SUM(cut_amount) as total_cut')
                    ->first();
    
                // 3) استبدال مباشر (بدون مضاعفة)
                BdSalary::updateOrCreate(
                    [
                        'bd_id' => $bdId,
                        'month' => $month,
                        'year'  => $year,
                    ],
                    [
                        'salary'     => $totals->total_salary ?? 0,
                        'cut_amount' => $totals->total_cut ?? 0,
                    ]
                );

                if ($bdId == 51) {
                }
            }
    
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('MigrateBdSalariesJob error: ' . $e->getMessage());
        }
    }

    protected static function getBdUserId($bdAppId)
    {
        return Bd::where('app_id', $bdAppId)->value('id') ?? 0;
    }
    // public function handle(): void
    // {
    
    //     DB::beginTransaction();
    //     try {
    //         $this->migrateOldSalaries();
    //         $this->migrateAugustSalaries();
    //         DB::commit();
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         logger()->error('MigrateBdSalariesJob error: ' . $e->getMessage());
    //     }
    // }
    
    // /**
    //  * دالة لترحيل الشهور القديمة فقط (تستثني شهر 8)
    //  */
    // private function migrateOldSalaries(): void
    // {
    //     $bdSalaries = BDSallary::where('month', '!=', 8)->get();
    
    //     foreach ($bdSalaries as $bdSalary) {
    //         $this->processBdSalary($bdSalary);
    //     }
    // }
    
    // /**
    //  * دالة لترحيل شهر 8 فقط
    //  */
    // private function migrateAugustSalaries(): void
    // {
    //     $bds   = BD::all();
    //     $month = 8;
    //     $year  = now()->year;
    
    //     foreach ($bds as $bd) {
    //         $bdId    = $bd->id;
    //         $bdAppId = $bd->app_id;
    
    //         $agencies = Agency::where(function ($q) use ($bdId, $bdAppId) {
    //                 $q->where('bd_id', $bdId)
    //                   ->orWhere('bd_id', $bdAppId);
    //             })->get();
    
    //         $totalSalary = 0;
    
    //         foreach ($agencies as $agency) {
    //             $userSalaries = UserSallary::where('user_agency_id', $agency->id)
    //                 ->where('month', $month)
    //                 ->where('year', $year)
    //                 ->get();
    
    //             foreach ($userSalaries as $userSallary) {
    //                 $expectedAmount = $userSallary?->dB ?? 0;
    
    //                 $oldTotal = BdAgencyHostSallary::where([
    //                         'bd_id'     => $bdId,
    //                         'agency_id' => $agency->id,
    //                         'user_id'   => $userSallary->user_id,
    //                         'month'     => $month,
    //                         'year'      => $year,
    //                     ])->sum('amount');
    
    //                 $diff = $expectedAmount - $oldTotal;
    
    //                 if ($oldTotal == 0) {
    //                     BdAgencyHostSallary::create([
    //                         'bd_id'      => $bdId,
    //                         'agency_id'  => $agency->id,
    //                         'user_id'    => $userSallary->user_id,
    //                         'month'      => $month,
    //                         'year'       => $year,
    //                         'amount'     => $expectedAmount,
    //                         'oldDbValue' => 0,
    //                     ]);
    //                 } elseif ($diff != 0) {
    //                     BdAgencyHostSallary::create([
    //                         'bd_id'      => $bdId,
    //                         'agency_id'  => $agency->id,
    //                         'user_id'    => $userSallary->user_id,
    //                         'month'      => $month,
    //                         'year'       => $year,
    //                         'amount'     => $diff,
    //                         'oldDbValue' => $oldTotal, 
    //                     ]);
    //                 }
    
    //                 $totalSalary += $expectedAmount; 
    //             }
    //         }
    
    //         $totalCut = Charge::where('charger_type', 'bd')
    //             ->where(function ($q) use ($bdId, $bdAppId) {
    //                 $q->where('charger_id', $bdId)
    //                   ->orWhere('charger_id', $bdAppId);
    //             })
    //             // ->whereMonth('created_at', $month)
    //             ->whereYear('created_at', $year)
    //             ->sum('usd');
    
    //         BdSalary::updateOrCreate(
    //             [
    //                 'bd_id' => $bdId,
    //                 'month' => $month,
    //                 'year'  => $year,
    //             ],
    //             [
    //                 'salary'     => $totalSalary,
    //                 'cut_amount' => $totalCut,
    //             ]
    //         );
    //     }
    // }
    
    
  
    // private function processBdSalary($bdSalary): void
    // {
    //     $bdAppId = $bdSalary->bd_id;
    //     $bdId    = self::getBdUserId($bdAppId);
    //     $agencyId = $bdSalary->agency_id;
    //     $month    = $bdSalary->month;
    //     $year     = $bdSalary->year;
    
    //     $userSalaries = UserSallary::where('user_agency_id', $agencyId)
    //         ->where('month', $month)
    //         ->where('year', $year)
    //         ->get();
    
    //     foreach ($userSalaries as $userSallary) {
    //         BdAgencyHostSallary::updateOrCreate(
    //             [
    //                 'bd_id'     => $bdId,
    //                 'agency_id' => $agencyId,
    //                 'user_id'   => $userSallary->user_id,
    //                 'month'     => $month,
    //                 'year'      => $year,
    //             ],
    //             [
    //                 'amount'        => $userSallary?->dB ?? 0,
    //                 'bd_user_id'    => $bdAppId,
    //                 'created_at'    => $userSallary->created_at,
    //             ]
    //         );
    //     }
    
    //     $totals = BDSallary::where('bd_id', $bdAppId)
    //         ->where('month', $month)
    //         ->where('year', $year)
    //         ->selectRaw('SUM(sallary) as total_salary, SUM(cut_amount) as total_cut')
    //         ->first();
    
    //     BdSalary::updateOrCreate(
    //         [
    //             'bd_id' => $bdId,
    //             'month' => $month,
    //             'year'  => $year,
    //         ],
    //         [
    //             'salary'     => $totals->total_salary ?? 0,
    //             'cut_amount' => $totals->total_cut ?? 0,
    //         ]
    //     );
    // }

    // protected static function getBdUserId($bdAppId)
    // {
    //     return Bd::where('app_id', $bdAppId)->value('id') ?? 0;

    // }
    
}







