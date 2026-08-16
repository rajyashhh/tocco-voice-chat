<?php

namespace App\Classes\Gifts;

use App\Enums\UserDiamondLogType;
use App\Helpers\UserDiamondLogHelper;
use App\Models\Pk;
use Carbon\Carbon;
use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\Family;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Models\AppFeature;
use App\Models\FamilyRank;
use App\Models\FamilyLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Classes\Enums\NotificationType;
use App\Services\RoomCalculationService;
use Illuminate\Database\Eloquent\Collection;
use App\Jobs\SendCustomOfficialMessageToUser;
use Str;

class SendGiftService
{

    public function sendGift($number, Room $room, Gift $gift, User $senderUser, User $receivedUser, $isPlay = 0, $isPK = 0, $totalPrice = null, $platformObtain = null, $appFeatureStatus = null)
    {

        if ($totalPrice == null)
            $totalPrice = $gift->price * $number;

        $info = $this->getGiftLogData($gift, $room, $number, $totalPrice, $senderUser, $receivedUser, $isPlay, isPk: $isPK, appFeatureStatus: $appFeatureStatus);

        GiftLog::query()->create($info);

        $this->recordGiftRankings([$info]);
    }

    public function sendGift2($number, Room $room, Gift $gift, User $senderUser, Collection $receivedUsers, $isPlay = 0, $totalPrice = null, $isPk = false, $cpId = null)
    {
        if ($totalPrice == null)
            $totalPrice = $gift->price * $number;
        $data = [];

        $appFeatureStatus = AppFeature::where('slug', 'room_gift_target')->value('status');

        foreach ($receivedUsers as $receivedUser) {
            $info = $this->getGiftLogData($gift, $room, $number, $totalPrice, $senderUser, $receivedUser, $isPlay, isPk: $isPk, cpId: $cpId, appFeatureStatus: $appFeatureStatus);
            $data[] = $info;
        }

        usort($data, fn($a, $b) => $a['receiver_id'] <=> $b['receiver_id']);
        DB::table('gift_logs')->insert($data);

        $this->recordGiftRankings($data);
    }

    public function sendGift3($number, Room $room, Gift $gift, User $senderUser, Collection $receivedUsers, $isPlay = 0, $totalPrice = null, $isPk = false, array $cpIds = null, $sourceType = null, $type = null)
    {
        if ($totalPrice == null)
            $totalPrice = $gift->price * $number;
        $roomBoomUuid = (string) Str::uuid();
        $data = [];
        $diamondLogs = [];
        $now = now();

        if ($type !== 'bag') {
            $featureType = $room->type === 'audio'
                ? UserDiamondLogType::GIFT_ROOM_AUDIO
                : UserDiamondLogType::GIFT_ROOM_LIVE;

            UserDiamondLogHelper::bulkLogByType($receivedUsers, $featureType, $totalPrice, $senderUser->id);
        }

        $appFeatureStatus = AppFeature::where('slug', 'room_gift_target')->value('status');
        foreach ($receivedUsers as $receivedUser) {
            $cpId = @$cpIds[$receivedUser->id] ?? null;
            $info = $this->getGiftLogData($gift, $room, $number, $totalPrice, $senderUser, $receivedUser, $isPlay, isPk: $isPk, cpId: $cpId, sourceType: $sourceType, appFeatureStatus: $appFeatureStatus);
            $info['room_boom_uuid'] = $roomBoomUuid;
            $data[] = $info;
        }

        usort($data, fn($a, $b) => $a['receiver_id'] <=> $b['receiver_id']);
        DB::table('gift_logs')->insert($data);

        $this->recordGiftRankings($data);

        return $roomBoomUuid;
    }

        public function sendGift3ForLuckyGift($number, Room $room, Gift $gift, User $senderUser, Collection $receivedUsers, $isPlay = 0, $totalPrice = null, $isPk = false, array $cpIds = null, $sourceType = null, $type = null, string $batchNonce = '')
    {
        if ($totalPrice == null)
            $totalPrice = $gift->price * $number;
        // Stable idempotency key for the supporter rows: the caller passes the lucky
        // post-job's job_nonce (constant across retries / re-dispatch). With a unique
        // (room_boom_uuid, receiver_id) index + insertOrIgnore below, a re-run writes
        // ZERO duplicate rows. Empty nonce (legacy in-flight payloads) falls back to a
        // fresh UUID — never collides, so insertOrIgnore behaves like a plain insert.
        $roomBoomUuid = $batchNonce !== '' ? $batchNonce : (string) Str::uuid();
        $data = [];
        $diamondLogs = [];
        $now = now();

        if ($type !== 'bag') {
            $featureType = $room->type === 'audio'
                ? UserDiamondLogType::GIFT_ROOM_AUDIO
                : UserDiamondLogType::GIFT_ROOM_LIVE;

            UserDiamondLogHelper::bulkLogByType($receivedUsers, $featureType, $totalPrice, $senderUser->id);
        }

        $appFeatureStatus = AppFeature::where('slug', 'room_gift_target')->value('status');
//
        foreach ($receivedUsers as $receivedUser) {
            $cpId = @$cpIds[$receivedUser->id] ?? null;
            $info = $this->getGiftLogDataForLuckyGift($gift, $room, $number, $totalPrice, $senderUser, $receivedUser, $isPlay, isPk: $isPk, cpId: $cpId, sourceType: $sourceType, appFeatureStatus: $appFeatureStatus);
            $info['room_boom_uuid'] = $roomBoomUuid;
            $data[] = $info;
        }

        // insertOrIgnore (not insert): exactly-once supporter rows keyed on the unique
        // (room_boom_uuid, receiver_id) index. One row per receiver, ONE bulk write per
        // send/combo — no fan-out, no full-scan. A re-dispatched/retried post-job that
        // re-reaches here silently inserts nothing instead of duplicating supporters.
        DB::transaction(function () use ($data) {
            DB::table('gift_logs')->insertOrIgnore($data);
        }, attempts: 3);

        return $roomBoomUuid;
    }

    public function calculate($uid, $toUid, $total)
    {
        $room_user = DB::table('users')->select(['id', 'is_sign', 'scale', 'is_leader'])->where('id', $uid)->first();
        if (!$room_user) {
            throw new \Exception('room owner not found');
        }
        $room_scale = Common::getConfig('platform_share');
        $room_scale = $room_scale ? $room_scale : 0;//Platform share
        if (!$room_user->is_sign) {                                                                         //non-contract homeowner
            $data['uid'] =
                0;                                                                                          //Room running water
            $data['toUid'] =
                $total * ((100 - $room_scale) / 100);                                                       //recipient
            $data['platform'] = $total * ($room_scale / 100);                                               //Platform flow
            $data['uid_yj'] = 0;                                                                          //homeowner
        } else {
            //Room running water
            $stream = $total * $room_user->scale / 100;
            //platform
            $platform = $total * ($room_scale - $room_user->scale) / 100;
            if ($room_user->is_leader) {
                $scale =
                    DB::table('leaders')->where('uid', $uid)->where('user_id', $toUid)->where('status', 2)->value('scale') ?: 100;
            } else {
                $scale = 100;
            }
            //recipient
            $room_scale_sign = (100 - $room_scale) / 100;
            $get_gift = $total * ($room_scale_sign * $scale / 100);
            $uid_yj = $total * ($room_scale_sign * (100 - $scale) / 100);

            $data['uid'] = $stream;  //Room running water
            $data['toUid'] = $get_gift;//recipient
            $data['platform'] = $platform;//Platform flow
            $data['uid_yj'] = $uid_yj;  //homeowner
        }
        $data = array_map(function ($val) {
            //            $gvic = Common::getConf ('gift_value_in_coins')?:0.1;
            $gvic = 1;
            return round($val * $gvic, 2);
        }, $data);
        return $data;



    }
    public function updateFamilyLevel(Family &$family, $totalCoins)
    {
        //get total giftlogs
        $this->updateFamilyModel($totalCoins, $family);

    }

    /**
     * @param $totalCoinsPerUser
     * @param mixed $family
     * @return void
     */
    public function updateFamilyModel($totalCoinsPerUser, Family $family): void
    {
        $familyId = $family->id;

        // Atomic increment instead of read-then-save: prevents lost updates when
        // several gifts hit the same family concurrently.
        Family::where('id', $familyId)->increment('total_diamond', $totalCoinsPerUser);
        $this->updateOrCreateFamilyRank($familyId, $totalCoinsPerUser);

        // Recompute level from the freshly incremented total.
        $newTotalDiamond = $family->total_diamond + $totalCoinsPerUser;
        $level =
            FamilyLevel::query()->where('exp', '<=', $newTotalDiamond)->orderByDesc('exp')->first();
        if ($level) {
            if ($level->id != $family->current_level_id) {
                dispatch(new SendCustomOfficialMessageToUser($familyId, NotificationType::FAMILY))->onQueue('notification_heavy');
            }
        }

        // Keep the in-memory model consistent for any caller relying on it.
        $family->total_diamond = $newTotalDiamond;
    }

    /**
     * @param mixed $familyId
     * @param $totalCoinsPerUser
     * @return void
     */
    public function updateOrCreateFamilyRank(mixed $familyId, $totalCoinsPerUser, ?Carbon $day = null): void
    {
        if ($day == null)
            $day = now();
        // Atomic increment instead of read-then-save (avoids lost updates under
        // concurrent gifts to the same family on the same day).
        $affected = FamilyRank::query()->where('family_id', $familyId)
            ->where('day', $day->day)
            ->where('month', $day->month)
            ->where('year', $day->year)
            ->increment('coins', $totalCoinsPerUser);

        if ($affected === 0) {
            $this->createFamilyRank($familyId, $totalCoinsPerUser);
        }
    }

    /**
     * @param mixed $familyId
     * @param $totalCoinsPerUser
     * @return void
     */
    public function createFamilyRank(mixed $familyId, $totalCoinsPerUser): void
    {
        FamilyRank::query()->create([
            'family_id' => $familyId,
            'day' => now()->day,
            'month' => now()->month,
            'year' => now()->year,
            'coins' => $totalCoinsPerUser
        ]);
    }

    public function updateFamilyLevelForReceiver(Collection $users, $totalCoinsPerUser): bool
    {
        $families = $users->pluck('family')->where('id', '!=', null);
        $familiesIds = $families->pluck('id')->toArray();
        if (count($familiesIds) == 0)
            return false;
        $repeatedData = $this->getDuplication($familiesIds);

        foreach ($repeatedData as $data) {
            $family = $families->where('id', $data['id'])->first();
            $this->updateFamilyModel($totalCoinsPerUser * $data['count'], $family);
        }

        return true;
    }

    /**
     * @param array $familiesIds
     * @return array
     */
    public function getDuplication(array $familiesIds): array
    {
        $repeatedData = [];

        foreach ($familiesIds as $familiesId) {
            $isFound = false;
            foreach ($repeatedData as $index => $data) {
                if ($data['id'] == $familiesId) {
                    $isFound = true;
                    $repeatedData[$index]['count'] += 1;
                    break;
                }
            }

            if (!$isFound) {

                $repeatedData[] = ['id' => $familiesId, 'count' => 1];
            }
        }
        return $repeatedData;
    }

    public function updatePkScoresAndSendToStream($pk, $userId, $roomId, $receivedIds, $giftPrice, $microphone)
    {
        if (!($pk instanceof Pk))
            return;

        $m = explode(',', $microphone);
        $mic_1 = isset($m[1]) ? $m[1] : 0;
        $mic_2 = isset($m[2]) ? $m[2] : 0;
        $mic_3 = isset($m[3]) ? $m[3] : 0;
        $mic_4 = isset($m[4]) ? $m[4] : 0;
        $mic_5 = isset($m[5]) ? $m[5] : 0;
        $mic_6 = isset($m[6]) ? $m[6] : 0;
        $mic_7 = isset($m[7]) ? $m[7] : 0;
        $mic_8 = isset($m[8]) ? $m[8] : 0;
        $team_1 = [$mic_1, $mic_2, $mic_5, $mic_6];
        $team_2 = [$mic_3, $mic_4, $mic_7, $mic_8];
        $t1 = implode(',', $team_1);
        $t2 = implode(',', $team_2);

        foreach ($receivedIds as $toUid) {
            if (in_array($toUid, $team_1)) {
                $pk->t1_score += $giftPrice;
            } elseif (in_array($toUid, $team_2)) {
                $pk->t2_score += $giftPrice;
            }
        }
        $pk->team_1 = $t1;
        $pk->team_2 = $t2;
        $pk->save();

        $ms = [
            'messageContent' => [
                "message" => "updatePk",
                "PkTime" => Carbon::parse($pk->end_at)->diffInMinutes(now()),
                "scoreTeam1" => $pk->t1_score,
                "scoreTeam2" => $pk->t2_score,
                "percentagepk_team1" => $pk->t1_per,
                "percentagepk_team2" => $pk->t2_per
            ]
        ];

        return json_encode($ms);
    }

    public function updatePkScoresAndSendToStreamJob($pk, $userId, $roomId, $receivedIds, $giftPrice, $room)
    {
        if (!($pk instanceof Pk))
            return;

        $microphones = $room->microphones()
            ->orderBy('position')
            ->get()
            ->keyBy('position');

        // FIX 4: Dynamically calculate team positions based on actual mic count
        // Instead of hardcoded [1,2,5,6] and [3,4,7,8] which only work for 8-mic rooms
        $totalMics = $microphones->count();
        $midpoint = (int) ceil($totalMics / 2);
        
        // Team 1: positions 0 to midpoint-1
        // Team 2: positions midpoint to totalMics-1
        $team1Positions = range(0, $midpoint - 1);
        $team2Positions = range($midpoint, $totalMics - 1);

        $team1 = collect($team1Positions)
            ->map(fn($pos) => $microphones[$pos]->user_id ?? 0)
            ->filter()
            ->values()
            ->toArray();

        $team2 = collect($team2Positions)
            ->map(fn($pos) => $microphones[$pos]->user_id ?? 0)
            ->filter()
            ->values()
            ->toArray();
            
        $t1Add = 0;
        $t2Add = 0;
        foreach ($receivedIds as $toUid) {
            if (in_array($toUid, $team1)) {
                $t1Add += $giftPrice;
            } elseif (in_array($toUid, $team2)) {
                $t2Add += $giftPrice;
            }
        }

        if ($t1Add > 0) {
            $pk->increment('t1_score', $t1Add);
        }
        if ($t2Add > 0) {
            $pk->increment('t2_score', $t2Add);
        }

        $pk->update([
            'team_1' => implode(',', $team1),
            'team_2' => implode(',', $team2)
        ]);

        $pk->refresh();

        $ms = [
            'messageContent' => [
                "message" => "updatePk",
                "PkTime" => Carbon::parse($pk->end_at)->diffInMinutes(now()),
                "scoreTeam1" => $pk->t1_score,
                "scoreTeam2" => $pk->t2_score,
                "percentagepk_team1" => $pk->t1_per,
                "percentagepk_team2" => $pk->t2_per
            ]
        ];
        $json = json_encode($ms);


        Common::sendToStream('SendCustomCommand', $roomId, $userId, $json);
    }


    /**
     * Feed the unified Redis ranking (RankingScoreService) from the gift_logs rows
     * that were just inserted. One row carries giftPrice = totalPrice; multi-receiver
     * sends insert one row per receiver, so adding per row reproduces EXACTLY the
     * legacy cron's SUM(giftPrice) GROUP BY {sender|receiver|roomowner|agency}.
     *
     * Owner decision: this NORMAL-gift path feeds wealth/charm/room/agency only.
     * Lucky-gift spending is isolated in its own 'lucky' section (handled in
     * ProcessLuckyGiftPostJob) and never touches wealth or charm here.
     *
     * Best-effort: a Redis failure must NEVER break gift sending. The gift_logs +
     * DB-source-of-truth remain authoritative; ranking:backfill self-heals any miss.
     *
     * @param array<int, array<string, mixed>> $rows inserted gift_logs rows
     */
    private function recordGiftRankings(array $rows): void
    {
        try {
            // Pre-aggregate per (section, member) so a multi-receiver send (~256 rows)
            // produces a handful of distinct increments instead of ~256 inline
            // ZINCRBYs, then flush them in ONE pipeline (addBatch). End state is
            // identical to looping add() — SUM(giftPrice) GROUP BY {column}.
            $bySection = ['wealth' => [], 'charm' => [], 'room' => [], 'agency' => []];

            foreach ($rows as $row) {
                $price = (float) ($row['giftPrice'] ?? 0);
                if ($price <= 0) {
                    continue;
                }

                $sender   = (int) ($row['sender_id'] ?? 0);
                $receiver = (int) ($row['receiver_id'] ?? 0);
                $owner    = (int) ($row['roomowner_id'] ?? 0);
                $agencyId = (int) ($row['agency_id'] ?? 0);

                // Guard each member > 0 BEFORE aggregating (mirrors the agency guard
                // already below). A non-contract room owner (uid=0), an absent
                // receiver/sender, or a userless box path would otherwise seed a "0"
                // member into the aggregate. addBatch() drops it again at flush time,
                // but guarding here makes the intent explicit and keeps the pipeline
                // free of phantom-0 work — the leaderboard NEVER carries member "0".
                if ($sender > 0) {
                    $bySection['wealth'][$sender] = ($bySection['wealth'][$sender] ?? 0.0) + $price;
                }
                if ($receiver > 0) {
                    $bySection['charm'][$receiver] = ($bySection['charm'][$receiver] ?? 0.0) + $price;
                }
                if ($owner > 0) {
                    $bySection['room'][$owner] = ($bySection['room'][$owner] ?? 0.0) + $price;
                }
                if ($agencyId > 0) {
                    $bySection['agency'][$agencyId] = ($bySection['agency'][$agencyId] ?? 0.0) + $price;
                }
            }

            app(\App\Services\RankingScoreService::class)->addBatch($bySection);
        } catch (\Throwable $e) {
            Log::warning('recordGiftRankings failed (ranking redis best-effort)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param Gift $gift
     * @param Room $room
     * @param $number
     * @param mixed $totalPrice
     * @param User $senderUser
     * @param User $receivedUser
     * @param mixed $isPlay
     * @return array
     */
    public function getGiftLogData(Gift $gift, Room $room, $number, mixed $totalPrice, User $senderUser, User $receivedUser, mixed $isPlay, $isPk = false, $cpId = null, $sourceType = null, $appFeatureStatus = null): array
    {
     
       $expectedTotal = $gift->price * $number;

        if (abs($totalPrice - $expectedTotal) > 0.01) {
            Log::warning('Gift price mismatch', [
                'gift_id' => $gift->id,
                'expected' => $expectedTotal,
                'received' => $totalPrice,
                'gift_price' => $gift->price,
                'number' => $number,
                'source_type' => $sourceType,
            ]);
            $totalPrice = $expectedTotal;
        }
        $info['giftId'] = $gift->id;
        $info['roomowner_id'] = $room->uid;
        $info['giftNum'] = $number;
        $info['giftName'] = $gift->name ?: '_';
        $info['giftPrice'] = $totalPrice;
        $info['app_profit_coins'] = $totalPrice;
        $info['sender_id'] = $senderUser->id;
        $info['receiver_id'] = $receivedUser->id;
        $info['is_play'] = $isPlay ? 2 : 1;
        $info['type'] = 2;
        $info['created_at'] = $info['updated_at'] = date('Y-m-d H:i:s', time());

        $info['platform_obtain'] = 0.0;                          //platform
        $info['receiver_obtain'] = $totalPrice;                  //recipient
        $info['roomowner_obtain'] = floor($totalPrice * 0.03);    //homeowner

        $info['agency_id'] = $receivedUser->agency_id;//homeowner
        $info['receiver_family_id'] = @$receivedUser->family_id;         //homeowner
        $info['sender_family_id'] = @$senderUser->family_id;
        $info['pk'] = @$isPk ?? false;
        $info['cp_id'] = $cpId;
        $info['room_id'] = $room->id;
        $info['room_gift_status'] = $appFeatureStatus ?? false;
        $info['source_type'] = $sourceType ?? 'coins';  
        $info['total'] = $gift->price;

        return $info;
    }

     public function getGiftLogDataForLuckyGift(Gift $gift, Room $room, $number, mixed $totalPrice, User $senderUser, User $receivedUser, mixed $isPlay, $isPk = false, $cpId = null, $sourceType = null, $appFeatureStatus = null): array
    {
     

        $info['giftId'] = $gift->id;
        $info['roomowner_id'] = $room->uid;
        $info['giftNum'] = $number;
        $info['giftName'] = $gift->name ?: '_';
        $info['giftPrice'] = $totalPrice;
        $info['app_profit_coins'] = $totalPrice;
        $info['sender_id'] = $senderUser->id;
        $info['receiver_id'] = $receivedUser->id;
        $info['is_play'] = $isPlay ? 2 : 1;
        $info['type'] = 2;
        $info['created_at'] = $info['updated_at'] = date('Y-m-d H:i:s', time());

        $info['platform_obtain'] = 0.0;                          //platform
        $info['receiver_obtain'] = $totalPrice;                  //recipient
        $info['roomowner_obtain'] = floor($totalPrice * 0.03);    //homeowner

        $info['agency_id'] = $receivedUser->agency_id;//homeowner
        $info['receiver_family_id'] = @$receivedUser->family_id;         //homeowner
        $info['sender_family_id'] = @$senderUser->family_id;
        $info['pk'] = @$isPk ?? false;
        $info['cp_id'] = $cpId;
        $info['room_id'] = $room->id;
        $info['room_gift_status'] = $appFeatureStatus ?? false;
        $info['source_type'] = $sourceType ?? 'coins';  // قيمة افتراضية
        $info['total'] = $gift->price;

        return $info;
    }

    public function updatePkScoresAndSendToStreamJob2($pk, $receivedIds, $giftPrice, $room): array
    {
        if (!($pk instanceof Pk))
            return [];
        $microphones = $room->microphones()
            ->orderBy('position')
            ->get()
            ->keyBy('position');

        // FIX 4: Dynamically calculate team positions based on actual mic count
        // Instead of hardcoded [1,2,5,6] and [3,4,7,8] which only work for 8-mic rooms
        $totalMics = $microphones->count();
        $midpoint = (int) ceil($totalMics / 2);
        
        // Team 1: positions 0 to midpoint-1
        // Team 2: positions midpoint to totalMics-1
        $team1Positions = range(0, $midpoint - 1);
        $team2Positions = range($midpoint, $totalMics - 1);

        $team1 = collect($team1Positions)
            ->map(fn($pos) => $microphones[$pos]->user_id ?? 0)
            ->filter()
            ->values()
            ->toArray();

        $team2 = collect($team2Positions)
            ->map(fn($pos) => $microphones[$pos]->user_id ?? 0)
            ->filter()
            ->values()
            ->toArray();

        $t1Add = 0;
        $t2Add = 0;
        foreach ($receivedIds as $toUid) {
            if (in_array($toUid, $team1)) {
                $t1Add += $giftPrice;
            } elseif (in_array($toUid, $team2)) {
                $t2Add += $giftPrice;
            }
        }

        if ($t1Add > 0) {
            $pk->increment('t1_score', $t1Add);
        }
        if ($t2Add > 0) {
            $pk->increment('t2_score', $t2Add);
        }

        $pk->update([
            'team_1' => implode(',', $team1),
            'team_2' => implode(',', $team2),
        ]);

        $pk->refresh();
        return ["end_at" => $pk->end_at, 't1_score' => $pk->t1_score, 't2_score' => $pk->t2_score];
    }
}
