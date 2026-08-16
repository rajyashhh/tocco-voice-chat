<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Family;
use App\Models\Pack;
use App\Models\Room;
use App\Models\RoomSalary;
use App\Models\Target;
use App\Models\UserSallary;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyStoreResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
          
        $agency_owner = $this->agency;
        $salary       =round( $this->salary,2);
        $sallary      = $salary; 
        $userSalary   = $sallary;

        if (($this->type_user == 2 || $this->type_user == 4)) {
            $userSalary = $salary; //
            $hostSalary = number_format((float) ($agencySallary ?? $agency_owner?->salary ?? 0), 3, '.', '');
            $sallary = $hostSalary;
        }

        $pendingDollar = $this->totalUserSalary->sum("pending_dollar");
        $paid = $this->totalUserSalary->sum("cut_amount");
        $roomSalary = $this->ownerRoom?->roomSalary->sum(function ($roomSalary) {
            return $roomSalary->salary - $roomSalary->cut_amount;
        });

        $diamonds = (in_array($this->type_user, [0,3])) ? $this->exchange_diamonds : $this->monthly_diamond_received;

        $data = [

            'my_store' => [
                'id' => $this->id,
                'coins_new' => $this->di,
                'coins' => (string)$this->di,
                'diamonds' =>  (string)$diamonds,
                'silver_coins' => (string)$this->gold,
                'usd' => (double)$sallary,
                'user_usd' => (string) truncateAndTrim($userSalary) ?? '',
                'user_usd_new' => (string) (isset($userSalary) ? round($userSalary, 0) : ''),
                'host_usd' => (string) @$hostSalary ?? '',
                'pending_dollar' => (string) $pendingDollar ?? '',
                'room_salary' => (string) $roomSalary ?? '',
                'paid' =>  $paid ?? 0,
                'wallet_balance' =>  $this->salary ?? 0,
                'wallet_user_balance' => $this->user_wallet_balance ?? 0,
            ], 

        ];

        return $data;
    }
}
