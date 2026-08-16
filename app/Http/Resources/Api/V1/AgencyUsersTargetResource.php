<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Models\UsersJoinedAgency;
use Carbon\Carbon;
use App\Models\GiftLog;
use App\Models\LiveTime;
use App\Models\UserTarget;
use App\Models\UserSallary;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyUsersTargetResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $year = request('year') ?? Carbon::now()->year;
        $month = request('month') ?? Carbon::now()->month;

        $endOfMonth = Carbon::create($year, $month)->endOfMonth();

        $joinRecord = UsersJoinedAgency::where('user_id', $this->id)
            ->where('agency_id', $this->agency_id)
            ->latest('join_date')
            ->first();
        $timezone = getTimezone();
        [$startOfMonth, $endOfMonth] = Carbon::startAndEndOfMonthUTC($year, $month, $timezone);


        $joinedDate = $joinRecord ? Carbon::parse($joinRecord->join_date)->startOfDay() : $startOfMonth;
        $leaveDate = $joinRecord && $joinRecord->leave_date
            ? Carbon::parse($joinRecord->leave_date)->endOfDay()
            : $endOfMonth;

        $from = $joinedDate->greaterThan($startOfMonth) ? $joinedDate : $startOfMonth;
        $to = $leaveDate->lessThan($endOfMonth) ? $leaveDate : $endOfMonth;


        $agencySallary = UserSallary::query()
        ->where('user_id', $this->id)
        ->where('user_agency_id', $this->agency_id)
        ->where('is_finished', 0)
        ->where(fn($q) => $q->whereBetween('created_at', [$from, $to])->orWhereBetween('updated_at', [$from, $to ]))
        ->latest()
        ->value('agency_sallary');

        $totalSeconds= $this->UserliveTime()
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->reduce(function ($carry, $session) {
                $start = is_numeric($session->start_time)
                    ? Carbon::createFromTimestamp($session->start_time)
                    : Carbon::parse($session->start_time);

                $end = is_numeric($session->end_time)
                    ? Carbon::createFromTimestamp($session->end_time)
                    : Carbon::parse($session->end_time);

                return $carry + $end->diffInSeconds($start);
            }, 0);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);



        $currentDate = Carbon::create($year, $month, 1);

        $monthsWithYears = collect();

        for ($i = 2; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $monthsWithYears->push([
                'year' => $date->year,
                'month' => $date->month,
            ]);
        }


        $giftLog = GiftLog::where('agency_id', $this->agency_id)
            ->where('receiver_id', $this->id)
            ->whereHas('sender')
            ->with('sender')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("sum(giftPrice) as exp, sender_id")
            ->groupBy('sender_id')->orderByRaw("exp desc")->limit(3)
            ->get()
            ->filter(function ($q) {
                return $q->exp > 0;
            });




        $hasColor = Common::hasInPack(@$this->id, 18, true);
        
        return [
            'id' => $this->id ?? 0,
            'name' => $this->name ?? '',
            'uuid' => $this->uuid ?? '',
            'image' => $this->profile?->avatar ?? '',
            'is_host' => $this->is_host,
            'is_admin' => $this->is_admin_in_agency,
            'image_color'          => @$this->color_image,
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'salary' => (float) $agencySallary ?? 0,
            'target' => [
                'id' => @$this->lastSallary?->target_id ?? 0,
                'user_diamonds' => (float) ($this->lastSallary?->achieved_diamond ?? 0),
                'user_hours'    =>  $this->lastSallary?->achieved_hours ?? 0,
                'user_days'     =>  $this->lastSallary?->achieved_days ?? 0,
                'old_targets'  => $this->latestOldTarget(),
            ],
            // 'top_users' => SenderGiftLogResource::collection($giftLog),
            'top_users' => $giftLog->map(function ($log) {
                return $log->sender?->profile?->avatar ?? '';
            })->filter()->values()->toArray(),
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVip(@$this->id, 18, 'color') : null),
        ];
    }



    protected function latestOldTarget()
    {
        $currentDate = now();
        $monthsWithYears = collect();

        for ($i = 2; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i)->startOfMonth();
            $monthsWithYears->push($date);
        }

        $joinRecord = $this->latestJoin; // تأكد أن العلاقة موجودة
        if (!$joinRecord) {
            return $monthsWithYears->map(fn($date) => [
                'month_number' => $date->month,
                'diamonds'     => 0,
            ]);
        }

        $joinedDate = Carbon::parse($joinRecord->join_date);
        $leaveDate  = $joinRecord->leave_date
            ? Carbon::parse($joinRecord->leave_date)
            : now();

        $result = [];

        foreach ($monthsWithYears as $monthStart) {
            $monthEnd = $monthStart->copy()->endOfMonth();

            if ($monthEnd->lt($joinedDate) || $monthStart->gt($leaveDate)) {
                $result[] = [
                    'month_number' => $monthStart->month,
                    'diamonds'     => 0,
                ];
                continue;
            }

            $latestSallary = $this->sallariesByMonth()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereBetween('created_at', [$joinedDate, $leaveDate])
                ->latest('created_at')
                ->first();

            $result[] = [
                'month_number' => $monthStart->month,
                'diamonds'     => $latestSallary?->target_diamonds ?? 0,
            ];
        }

        return $result;
    }
}
