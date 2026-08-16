<?php

namespace App\Observers;

use App\Models\Agency;
use App\Models\BdSalary;
use App\Models\UserSallary;
use App\Models\AgencySallary;
use App\Classes\Enums\NotificationType;
use App\Jobs\SendCustomOfficialMessageToUser;
use App\Services\BdAgencyHostSallaryService;
use Modules\UsersWallet\Helpers\WalletHelper;
use App\Jobs\UpdateUserWalletBalances;
use Illuminate\Support\Str;

class UserSallaryObserver
{
    public function updated(UserSallary $userSalary)
    {
        
        $this->updateOrCreateAgencySallary($userSalary);
    }

    public function saved(UserSallary $userSalary)
    {   
        $this->updateOrCreateAgencySallary($userSalary);
    }

    public function creating(UserSallary $userSalary)
    {
        $originalDbValue = $userSalary->getOriginal('dB');


        if (!$userSalary->extras) $userSalary->extras = '';
        $this->updateOrCreateAgencySallary($userSalary);
        $this->updateBdHostSallary($userSalary,$originalDbValue);

        $newData = [
            'sallary' => $userSalary->sallary,
            'agency_sallary' => $userSalary->agency_sallary,
            'dB' => $userSalary->dB,
        ];
        
        $oldData = [
            'sallary' => $userSalary->getOriginal('sallary') ?? 0,
            'agency_sallary' => $userSalary->getOriginal('agency_sallary') ?? 0,
            'dB' => $originalDbValue ?? 0,
        ];
        
        UpdateUserWalletBalances::dispatch(
            $userSalary->user_id,
            $newData,
            $oldData,
            $userSalary->user_agency_id,
            'sallary_update',
            $userSalary->target_id,
            (string) Str::uuid(),
        )->onQueue('wallet');
    }

    public function updating(UserSallary $userSalary)
    {
        $originalDbValue = $userSalary->getOriginal('dB');

        if (!$userSalary->extras) $userSalary->extras = '';

        if ($userSalary->isDirty('sallary') && $userSalary->sallary > 0) {
            dispatch(new SendCustomOfficialMessageToUser($userSalary->user_id, NotificationType::TARGET))->onQueue('notification');
        }
        
        $this->updateBdHostSallary($userSalary, $originalDbValue);


        $newData = [
            'sallary' => $userSalary->sallary,
            'agency_sallary' => $userSalary->agency_sallary,
            'dB' => $userSalary->dB,
        ];
        
        $oldData = [
            'sallary' => $userSalary->getOriginal('sallary') ?? 0,
            'agency_sallary' => $userSalary->getOriginal('agency_sallary') ?? 0,
            'dB' => $originalDbValue ?? 0,
        ];
        UpdateUserWalletBalances::dispatch(
            $userSalary->user_id,
            $newData,
            $oldData,
            $userSalary->user_agency_id,
            'sallary_update',
            $userSalary->target_id,
            (string) Str::uuid(),
        )->onQueue('wallet');


    }

    public function updateOrCreateAgencySallary(UserSallary $userSalary): void
    {

        $app_feature = \Cache::get('host_agency');
        if ($app_feature) {
            $agency = Agency::find($userSalary->user_agency_id);
            if ($userSalary->user_agency_id != 0 && $agency && $agency->status == 1) {
                $agency_id    = $userSalary->user_agency_id;
                $month        = $userSalary->month;
                $year         = $userSalary->year;
                $agencySalary = AgencySallary::query()
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('agency_id', $agency_id)
                    ->first();

                $salary = UserSallary::query()
                    ->where('user_agency_id', $agency_id)
                    ->where('month', $month )
                    ->where('year', $year)
                    ->sum('agency_sallary');

                if ($agencySalary) {
                    $agencySalary->update(['sallary' => $salary]);
                } else {
                    AgencySallary::query()->create([
                        'sallary' => $salary,
                        'agency_id' => $agency_id,
                        'month' => $month,
                        'year'    => $year
                    ]);
                }
            }
        }
    }



    private function updateBdHostSallary(UserSallary $userSallary ,$originalDbValue = null): void
    {
        $agency = Agency::find($userSallary->user_agency_id);

        if ($agency && $agency->bd_id && $agency->status == 1) {

            BdAgencyHostSallaryService::storeOrUpdate([
                'bd_id'     => $agency->bd_id,
                'user_id'   => $userSallary->user_id,
                'agency_id' => $agency->id,
                'amount'    => $userSallary->dB ?? 0,
                'oldDbValue'   => $originalDbValue,
                'month'     => $userSallary->month,
                'year'      => $userSallary->year,
            ]);
        }
    }
}
