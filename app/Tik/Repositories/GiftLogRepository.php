<?php

namespace App\Tik\Repositories;

use Carbon\Carbon;
use App\Models\GiftLog;
use Illuminate\Support\Facades\DB;


class GiftLogRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new GiftLog());
    }

    public function getRoomRankingData($roomOwnerId, $type, $limit)
    {
        $query = GiftLog::with(['sender.profile', 'sender.mangerType']) // Eager load relationships
            ->where('roomowner_id', $roomOwnerId);

        // Filter for today's data if type is 1
        if ($type == 1) {
            $query->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
        }

        return $query->selectRaw("SUM(giftPrice) as exp, sender_id")
            ->groupBy('sender_id')
            ->orderByDesc('exp')
            ->limit($limit)
            ->get()
            ->reject(fn($item) => $item->exp == 0);
    }
    public function getFirstRoomByOwnerId($ownerId)
    {
        return $this->model->query()->selectRaw('sender_id, SUM(giftPrice) AS total')->where('roomowner_id', $ownerId)->groupBy('sender_id')->orderByDesc('total')->first();
    }

    public function getByAgency($rel, $start, $end, $agencyId, $keywords, $perPage, $page)
    {
        $startUtc = $start instanceof \Carbon\Carbon ? $start->copy()->utc() : $start;
        $endUtc = $end instanceof \Carbon\Carbon ? $end->copy()->utc() : $end;

        return $this->model
            ->where('agency_id', $agencyId)
            ->whereHas($rel)
            ->with($rel)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->selectRaw("sum(giftPrice) as exp, $keywords")
            ->groupBy($keywords)
            ->orderByRaw("exp desc")
            ->paginate($perPage, ['*'], 'page', $page);
    }


    public function getSumOfReceiverObtain($userId)
    {
        return $this->model->query()->where('receiver_id', $userId)
            ->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])
            ->sum('receiver_obtain');
    }
    public function getByReceiver($receiverId, $startDate, $endDate)
    {
        $startUtc = $startDate instanceof \Carbon\Carbon ? $startDate->copy()->utc() : $startDate;
        $endUtc = $endDate instanceof \Carbon\Carbon ? $endDate->copy()->utc() : $endDate;

        return $this->model->query()->whereBetween('created_at', [$startUtc, $endUtc])->where("receiver_id", $receiverId);
    }

    public function sumGiftPriceByReceiver($receiverId, $startDate, $endDate, $date)
    {
        $day = Carbon::parse($date);
        return $this->getByReceiver($receiverId, $startDate, $endDate)
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->sum("giftPrice");
    }

    public function totalUsersGiftPrice($receiverIds)
    {
        $this->model->query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereIn("receiver_id", $receiverIds)->sum('giftPrice');
    }

    public function getByDaily($userId, $agencyId, $start, $end, $timezone = null)
    {
        $startUtc = $start instanceof \Carbon\Carbon ? $start->copy()->utc() : $start;
        $endUtc = $end instanceof \Carbon\Carbon ? $end->copy()->utc() : $end;

        $tz = $timezone ?? getTimezone();
        $offset = \Carbon\Carbon::now($tz)->format('P'); 

        return $this->model->query()
            ->selectRaw("sum(giftPrice) as diamonds, max(CONVERT_TZ(created_at, '+00:00', ?)) as date", [$offset])
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->where('receiver_id', $userId)
            ->where('agency_id', $agencyId)
            ->groupBy(\DB::raw("DATE(CONVERT_TZ(created_at, '+00:00', '$offset'))"))
            ->orderBy('date', 'asc')
            ->get();
    }


    public function getByDailyNew($userId, $agencyId, $start_at, $end_at, $timezone = null)
    {
        // Ensure dates are in UTC for database query
        $startUtc = $start_at instanceof \Carbon\Carbon ? $start_at->copy()->utc() : $start_at;
        $endUtc = $end_at instanceof \Carbon\Carbon ? $end_at->copy()->utc() : $end_at;

        // Get timezone offset for MySQL CONVERT_TZ
        $tz = $timezone ?? getTimezone();
        $offset = \Carbon\Carbon::now($tz)->format('P'); // e.g., "+02:00"

        $data = $this->model->query()
            ->selectRaw("sum(giftPrice) as diamonds, max(CONVERT_TZ(created_at, '+00:00', ?)) as date", [$offset])
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->where('receiver_id', $userId)
            ->where('agency_id', $agencyId)
            ->groupBy(\DB::raw("DATE(CONVERT_TZ(created_at, '+00:00', '$offset'))"))
            ->limit(31)
            ->get();

        return $data;
    }

    public function getByDate($userId, $date)
    {
        $day = Carbon::parse($date);
        return $this->model->query()->selectRaw('receiver_id, SUM(giftPrice) AS total')->groupBy("receiver_id")->where('receiver_id', $userId)->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->first();
    }

    public function topUser($withRelation, $actionId)
    {
        return $this->model->with($withRelation)->select(DB::raw('sum(giftPrice) as totalGiftPrice'), $actionId)->groupBy($actionId)->orderByDesc('totalGiftPrice')->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->limit(3)->get();
    }

    public function getByUserId($userId)
    {
        return $this->model->query()
            ->has('sender')
            ->with([
                'sender.profile',
                'sender.country',
                'receiver',
            ])
            // ->select('sender_id', 'receiver_id')
            // ->selectRaw('SUM( giftPrice) AS total')
            // ->selectRaw('CAST(SUM(giftPrice) AS DECIMAL(10, 2)) AS total_decimal')
            ->selectRaw('
            sender_id,
            receiver_id,
            SUM(giftPrice) AS total
        ')
            ->where('receiver_id', $userId)
            ->groupBy('sender_id', 'receiver_id')
            ->orderByDesc('total')
            ->take(20)
            ->get();
    }

    public function userGiftInfo($id, $type, $startDate = null, $endDate = null, $perPage = null, $page = null)
    {
        return $this->model->with(['sender.profile', 'receiver.profile', 'receiver.specialId.ware', 'gift.category'])
            ->selectRaw('giftId, sender_id, receiver_id, SUM(giftPrice) AS total')
            ->when($type == 'sender', fn($q) => $q->where('sender_id', $id)->where('receiver_id', '!=', $id))
            ->when($type == 'receiver', fn($q) => $q->where('receiver_id', $id)->where('sender_id', '!=', $id))
            ->when($type == 'yourself', fn($q) => $q->where('receiver_id', $id)->where('sender_id', $id))
            ->when(is_null($type), function ($q) use ($id) {
                $q->where(function ($query) use ($id) {
                    $query->where('receiver_id', $id)
                        ->orWhere('sender_id', $id);
                });
            })
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $formattedStartDate = Carbon::parse($startDate)->startOfDay();
                $formattedEndDate = Carbon::parse($endDate)->endOfDay();
                $q->whereBetween('created_at', [$formattedStartDate, $formattedEndDate]);
            })->groupBy('giftId', 'sender_id', 'receiver_id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function listGiftReceiveLive($userId, $startDate = null, $endDate = null, $perPage, $page)
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        return $this->model->query()
            ->where('receiver_id', $userId)
            ->when($start !== null && $end !== null, function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })
            ->whereHas('room', function ($q) {
                $q->where('type', 'live');
            })
            ->selectRaw('sender_id, room_id,created_at,giftId,SUM( giftPrice) AS total')
            ->groupBy('sender_id', 'room_id', 'giftId', 'created_at')
            ->orderByDesc('created_at')
            ->with(['room', 'sender', 'gift'])
            ->paginate($perPage, ['*'], 'page', $page);
    }


    public function listGiftReceiveAudio($userId, $startDate = null, $endDate = null, $perPage, $page)
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        return $this->model->query()
            ->where('receiver_id', $userId)
            ->when($start !== null && $end !== null, function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })
            ->whereHas('room', function ($q) {
                $q->where('type', 'audio');
            })
            ->selectRaw('sender_id,created_at, room_id,giftId,SUM( giftPrice) AS total')
            ->orderByDesc('created_at')
            ->groupBy('sender_id', 'room_id', 'giftId', 'created_at')
            ->with(['room', 'sender', 'gift'])
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
