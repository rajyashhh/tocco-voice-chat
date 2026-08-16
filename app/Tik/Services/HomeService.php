<?php

namespace App\Tik\Services;


use Exception;
use App\Models\Pack;
use App\Models\Room;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Tik\Repositories\PackRepository;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\WareRepository;
use App\Tik\Repositories\ImageRepository;
use App\Tik\Repositories\TicketRepository;
use App\Tik\Repositories\GiftLogRepository;
use App\Tik\Repositories\LiveTimeRepository;
use Illuminate\Support\Facades\Log;
use Modules\Vip\Repositories\OvipRepository;
use Modules\Vip\Repositories\UserVipRepository;


class HomeService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LiveTimeRepository $liveTimeRepository,
        private readonly GiftLogRepository $giftLogRepository,
        private readonly ImageRepository $imageRepository,
        private readonly OvipRepository $ovipRepository,
        private readonly WareRepository $wareRepository,
        private readonly RoomRepository $roomRepository,
        private readonly PackRepository $packRepository,
        private readonly UserVipRepository $userVipRepository,
        private readonly TicketRepository $ticketRepository,
    ) {}

    public function totalHours($request, $userId)
    {
        $user  = $this->userRepository->findById($userId);
        $today = false;

        if ($request->time == 'today') {

            $userHours = $this->liveTimeRepository->totalHoursUser($userId);
            $totalTime = Common::totalTime($userHours);


            $days = $user->today_days;
            if ($days >= 1) {
                $today = true;
            }
            $diamonds = $this->giftLogRepository->getSumOfReceiverObtain($userId);
            $type = 0;
        } elseif ($request->time == 'month') {
            $userHours = $this->liveTimeRepository->totalHoursByMonth($userId);
            $totalTime = Common::totalTime($userHours);

            $days = $user->monthly_days + $user->today_days;
            $diamonds =  $user->user_diamond; // userDiamond
            $type = 1;
        } else {
            $userHours = $this->liveTimeRepository->totalHours($userId);
            $totalTime = Common::totalTime($userHours);
            $days = $user->total_days + $user->today_days;
            $diamonds = $user->total_diamond_received;
            $type = 3;
        }

        return [$user, $diamonds, $days, $type, $totalTime, $today];
    }

    public function imageIndex()
    {
        $pk_images = $this->imageRepository->getImage();
        $vip_images = $this->ovipRepository->getOvip();

        $levels = $vip_images->pluck('level')->unique()->values()->all();
        $waresMap = $this->wareRepository->getWithTypesAndLevels([4, 6], $levels);

        foreach ($vip_images as $k => &$image) {
            $frame = $waresMap[4][$image->level] ?? null;
            $intro = $waresMap[6][$image->level] ?? null;
            $image->frame = $frame ? $frame->makeHidden(['type', 'level']) : null;
            $image->intro = $intro ? $intro->makeHidden(['type', 'level']) : null;
        }
        unset($image);

        return [$pk_images, $vip_images];
    }

    public function wapel($userId, $ownerId)
    {
        $room = $this->roomRepository->findRoomUser($ownerId);
        if (!$room) throw new Exception('room not found');
        $vip = $this->userVipRepository->findByUserId($userId);

        if (!$vip) throw new Exception('not found');
        $level =  $vip->OVip->level;
        $wapel = $this->packRepository->findByUserId($userId, 12);

        if ($wapel) {
            $expire = $wapel->expire;

            $wapel->use_num -= 1;
            $wapel->save();
            if ($wapel->use_num < 1) {
                $wapel->delete();
            }
            $ware = $this->wareRepository->findById($wapel->target_id);
        }
        return [$level, $expire, $ware, $room->id, $wapel];
    }

    public function openTicket($request)
    {
        $data = [
            'user_id' => $request->user_id,
            'contact_num' => $request->contact,
            'problem' => $request->txt,
            'description' => $request->description,
            'status' => 1
        ];
        $tkt = $this->ticketRepository->create($data);
        if ($request->hasFile('img')) {
            $img = $request->file('img');
            $path = Common::upload('ticket', $img);
            $tkt->img = $path;
            $tkt->save();
        }

        return  $tkt;
    }

    public function changePackMode(string $type, array $privilegeArr, User $user, bool $isAvailable): bool
    {
        if (!array_key_exists($type, $privilegeArr)) {
            return false;
        }

        $privilegeId = $privilegeArr[$type];

        // Check if the ware exists when enabling the privilege
        if ($isAvailable && !Ware::where('type', $privilegeId)->exists()) {
            throw new Exception('not found');
        }

        // Ensure the user has the pack before updating
        $packQuery = Pack::where('user_id', $user->id)
            ->where('vip_user_id',$user->UserVip->id)
            ->whereHas('ware', fn($q) => $q->where('level', $user->UserVip->level ?? 0))
            ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
            ->where('type', $privilegeId);

        if (!$isAvailable) $packQuery->where('is_used', 1);

        if (!$packQuery->exists()) {

            throw new Exception(__('api.notWare'));
        }
        if (!$user->UserVip) throw new Exception(__('api.notWare'));

        // Fetch the specific pack with VIP level and expiration check
        $pack = $packQuery->first();

        if (!$pack) {
            throw new \Exception(__('api.notWare'));
        }
        // Update pack status
        $pack->update([
            'is_used' => $isAvailable,
            'using' => 1,
        ]);



        return true;
    }
}
