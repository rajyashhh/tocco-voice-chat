<?php

namespace Modules\RoomCup\Console;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\RoomCup\Entities\RoomCupTarget;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Modules\RoomCup\Entities\RoomCupReward;
use App\Models\Room;
use App\Models\User;
use Modules\RoomCup\Helpers\RoomCupHelper;
use Symfony\Component\Console\Command\Command as EnumCommand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Facades\CustomNotification;
use App\Models\GiftLog;




class CalculateRoomCupRewards extends Command
{
    protected $signature = 'roomcup:calculate-rewards';
    protected $description = 'Calculate RoomCup rewards and distribute profits to the owner and admins if the target is achieved';
    protected string $type;

    public function handle(): int
    {
        if (!$this->isModuleEnabled('RoomCup')) {
            $this->warn("⛔ RoomCup module is disabled in modules_statuses.json.");
            return EnumCommand::SUCCESS;
        }
        $settings = $this->getRoomCupSettings();
        $type     = $settings['type'] ?? 'daily';
        $this->type = $type ;
        if (!$this->isEnabledRoomCup($settings)) {
            $this->warn("⛔ Room Cup not enabled");
            return EnumCommand::SUCCESS;
        }
        [$start, $end] = $this->getPeriodByType($type);


        $this->logStart($start, $end);

        $this->processGiftsInPeriod($start, $end);

        $this->logEnd();

        return EnumCommand::SUCCESS;
    }

    protected function isModuleEnabled(string $moduleName): bool
    {
        $path = base_path('modules_statuses.json');
        if (!file_exists($path)) {
            return false;
        }

        $modules = json_decode(file_get_contents($path), true);

        return isset($modules[$moduleName]) && $modules[$moduleName] === true;
    }
    private function isEnabledRoomCup(array $settings): bool
    {
        return $settings['enabled'] ?? false;
    }


    private function getRoomCupSettings(): array
    {
        $default = [
            'enabled'          => true,
            'interval'         => 1,
            'type'             => 'daily',
            'time'             => '00:00',
            'day'              => 0,
        ];

        $settings = [];

        foreach ($default as $key => $defaultValue) {
            $cacheKey = 'roomcup_' . $key;
            $value = Cache::get($cacheKey);

            if ($value === null) {
                $setting = Setting::where('key', $cacheKey)->first();
                $value = $setting ? $setting->value : $defaultValue;

                Cache::put($cacheKey, $value, now()->addDays(30));
            }

            if ($key === 'enabled') {
                $value = (bool) $value;
            } elseif (in_array($key, ['day', 'interval'])) {
                $value = (int) $value;
            }

            $settings[$key] = $value;
        }

        return $settings;

    }

    private function getPeriodByType(string $type): array
    {
        $tz = getTimezone();
        return match ($type) {
            'daily'   => [
                Carbon::yesterday($tz)->startOfDay()->setTimezone('UTC'),
                Carbon::yesterday($tz)->endOfDay()->setTimezone('UTC'),
            ],
            'weekly'  => [
                Carbon::now($tz)->subWeek()->startOfWeek()->setTimezone('UTC'),
                Carbon::now($tz)->subWeek()->endOfWeek()->setTimezone('UTC'),
            ],
            'monthly' => [
                Carbon::now($tz)->subMonth()->startOfMonth()->setTimezone('UTC'),
                Carbon::now($tz)->subMonth()->endOfMonth()->setTimezone('UTC'),
            ],
            default   => [
                Carbon::yesterday($tz)->startOfDay()->setTimezone('UTC'),
                Carbon::yesterday($tz)->endOfDay()->setTimezone('UTC'),
            ],
        };
    }

    private function processGiftsInPeriod(Carbon $start, Carbon $end): void
    {
        $minTarget = RoomCupTarget::min('total');

        if ($minTarget === null) {
            // No targets configured: nothing can ever qualify, and passing NULL
            // into having() throws. Skip the whole run safely.
            $this->warn('⛔ No Room Cup targets configured; skipping rewards calculation.');
            $this->logRoomCup('No Room Cup targets configured (room_cup_targets empty); run skipped.');
            return;
        }

        if ($this->type === 'daily') {
            // Daily: aggregate gifts from GiftLog per room for the day
            $aggregatedGifts = GiftLog::whereBetween('created_at', [$start, $end])
                ->select(
                    'room_id',
                    DB::raw('SUM(giftPrice) as current_total'),
                )
                ->whereNotNull('room_id')
                ->groupBy('room_id')
                ->having('current_total', '>=', $minTarget)
                ->orderBy('room_id')
                ->get();

            // Get number_of_visitors from TotalRoomGift per room
            $visitorsMap = TotalRoomGift::whereBetween('created_at', [$start, $end])
                ->select(
                    'room_id',
                    DB::raw('SUM(number_of_visitors) as number_of_visitors')
                )
                ->groupBy('room_id')
                ->pluck('number_of_visitors', 'room_id');

            foreach ($aggregatedGifts as $gift) {
                try {
                    $visitorsCount = $visitorsMap[$gift->room_id] ?? 0;

                    $totalRoomGift = TotalRoomGift::firstOrCreate(
                        [
                            'room_id' => $gift->room_id,
                            'created_at' => $start,
                        ],
                        [
                            'current_total' => $gift->current_total,
                            'number_of_visitors' => $visitorsCount,
                            'updated_at' => now(),
                        ]
                    );

                    $gift->id = $totalRoomGift->id;
                    $gift->number_of_visitors = $visitorsCount;

                    $this->processGift($gift);
                } catch (\Throwable $e) {
                    $this->error("Failed to process gift | Room: {$gift->room_id} | Error: {$e->getMessage()}");
                    $this->logRoomCup("EXCEPTION processing gift | Room: {$gift->room_id} | Error: {$e->getMessage()} | Trace: {$e->getTraceAsString()}");
                }
            }
        } else {
            // Weekly/Monthly: aggregate gifts from GiftLog per room
            $aggregatedGifts = GiftLog::whereBetween('created_at', [$start, $end])
                ->select(
                    'room_id',
                    DB::raw('SUM(giftPrice) as current_total'),
                )
                ->whereNotNull('room_id')
                ->groupBy('room_id')
                ->having('current_total', '>=', $minTarget)
                ->orderBy('room_id')
                ->get();

            // Get number_of_visitors from TotalRoomGift per room
            $visitorsMap = TotalRoomGift::whereBetween('created_at', [$start, $end])
                ->select(
                    'room_id',
                    DB::raw('SUM(number_of_visitors) as number_of_visitors')
                )
                ->groupBy('room_id')
                ->pluck('number_of_visitors', 'room_id');

            foreach ($aggregatedGifts as $gift) {
                try {
                    $visitorsCount = $visitorsMap[$gift->room_id] ?? 0;

                    $totalRoomGift = TotalRoomGift::firstOrCreate(
                        [
                            'room_id' => $gift->room_id,
                            'created_at' => $start,
                        ],
                        [
                            'current_total' => $gift->current_total,
                            'number_of_visitors' => $visitorsCount,
                            'updated_at' => now(),
                        ]
                    );

                    $gift->id = $totalRoomGift->id;
                    $gift->number_of_visitors = $visitorsCount;

                    $this->processGift($gift);
                } catch (\Throwable $e) {
                    $this->error("Failed to process gift | Room: {$gift->room_id} | Error: {$e->getMessage()}");
                    $this->logRoomCup("EXCEPTION processing gift | Room: {$gift->room_id} | Error: {$e->getMessage()} | Trace: {$e->getTraceAsString()}");
                }
            }
        }

        // Reset session for all rooms that had gifts in this period
        $this->resetRoomSessions();
    }

    private function logStart(Carbon $start, Carbon $end): void
    {
        $this->info("🚀 Starting full calculation for gifts between {$start} and {$end}");
    }

    private function logEnd(): void
    {
        $this->info("✅ Calculation finished");
    }

    private function processGift($gift): void
    {
        $this->line("📦 Processing RoomGift ID: {$gift->id} | Room: {$gift->room_id} | Total: {$gift->current_total}");
        $this->logRoomCup("Processing RoomGift ID: {$gift->id} | Room: {$gift->room_id} | Total: {$gift->current_total}");

        $room = Room::find($gift->room_id);

        if (!$room) {
            $this->warn("⛔ Room not found (ID: {$gift->room_id})");
            $this->logRoomCup("Room not found (ID: {$gift->room_id})");
            return;
        }

        $adminsCount   = $room->admins_v2()->count();
        $visitorsCount = $gift->number_of_visitors ?? 0;

        $this->line("👥 Admins: $adminsCount | Visitors: $visitorsCount | Total: {$gift->current_total}");
        $this->logRoomCup("Room #{$room->id}: Admins=$adminsCount, Visitors=$visitorsCount, Total={$gift->current_total}");

        $target = $this->findTarget($gift->current_total, $visitorsCount, $adminsCount);
        if (!$target) {
            $this->line("⛔ No target achieved for Room #{$room->id}");
            $this->logRoomCup("No target achieved for Room #{$room->id}");
            return;
        }

        $this->logRoomCup("Target found for Room #{$room->id}: Target ID={$target->id}, Owner Profit={$target->owner_profit}, Admin Profit={$target->admin_profit}");

        $room->additional_admin = 0;
        $room->save();

        self::adjustAdminsBasedOnTarget($room, $target);
        $this->logRoomCup("Adjusted admins for Room #{$room->id}, additional_admin={$room->additional_admin}");

        DB::transaction(function () use ($room, $gift, $target, $adminsCount) {
            $rewards = [];
            $targetId = $target->id;

            // Owner reward
            if ($target->owner_profit > 0) {
                $rewards[] = $this->makeReward($room->id, $gift->id, $targetId, $room->uid, 'owner', $target->owner_profit);
                $this->logRoomCup("Room owner #{$room->uid} will get {$target->owner_profit}");
            }

            // Admin reward
            if ($adminsCount > 0 && $target->admin_profit > 0) {
                $share = $target->admin_profit / $adminsCount;
                foreach ($room->admins_v2() as $admin) {
                    $rewards[] = $this->makeReward($room->id, $gift->id, $targetId, $admin->id, 'admin', $share);
                    $this->logRoomCup("Admin {$admin->id} will get {$share}");
                }
            }

            foreach ($rewards as $reward) {
                $tz = getTimezone();

                $query = RoomCupReward::where('room_id', $reward['room_id'])
                    ->where('user_id', $reward['user_id'])
                    ->where('type', $reward['type']);

                switch ($this->type) {
                    case 'daily':
                        $start = Carbon::now($tz)->startOfDay()->setTimezone('UTC');
                        $end   = Carbon::now($tz)->endOfDay()->setTimezone('UTC');
                        $query->whereBetween('created_at', [$start, $end]);
                        break;

                    case 'weekly':
                        $start = Carbon::now($tz)->startOfWeek()->setTimezone('UTC');
                        $end   = Carbon::now($tz)->endOfWeek()->setTimezone('UTC');
                        $query->whereBetween('created_at', [$start, $end]);
                        break;

                    case 'monthly':
                        $start = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
                        $end   = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');
                        $query->whereBetween('created_at', [$start, $end]);
                        break;
                }

                $exists = $query->exists();
                if ($exists) {
                    $this->line("⏭️ Skipping duplicate reward for user {$reward['user_id']} in room {$reward['room_id']} (gift {$reward['total_room_gift_id']})");
                    $this->logRoomCup("Skipping duplicate reward: Room={$reward['room_id']}, User={$reward['user_id']}, Gift={$reward['total_room_gift_id']}");
                    continue;
                }

                RoomCupReward::create($reward);

                $amountBefore = Common::getCurrentBalance($reward['user_id']);
                $this->line("🪙 Adding {$reward['amount']} to user {$reward['user_id']} (balance before: {$amountBefore})");
                $this->logRoomCup("Adding reward to user {$reward['user_id']}: Amount={$reward['amount']}, Balance before={$amountBefore}");

                UserCoinLogHelper::logByType(
                    $reward['user_id'],
                    $reward['amount'],
                    $amountBefore,
                    UserCoinLogType::ROOM_CUP,
                );

                User::whereKey($reward['user_id'])->increment('di', $reward['amount']);
                RoomCupHelper::updateRoomCupWallet($reward['amount']);

                $user = User::find($reward['user_id']);
                if ($user) {
                    CustomNotification::roomcupReward($user, $reward['amount'], $reward['type']);
                }
            }
        });

        $this->info("✅ Rewards distributed for Room #{$room->id}");
        $this->logRoomCup("Rewards distributed for Room #{$room->id}");
    }


    private function findTarget(float $total, int $visitors, int $admins): ?RoomCupTarget
    {
        return RoomCupTarget::where('total', '<=', $total)
            ->where('number_of_visitors', '<=', $visitors)
            ->orderByDesc('total')
            ->first();
    }

    private function makeReward(int $roomId, int $giftId, $targetId,  int $userId, string $type, float $amount): array
    {
        return [
            'room_id'            => $roomId,
            'total_room_gift_id' => $giftId,
            'target_id'          => $targetId,
            'user_id'            => $userId,
            'type'               => $type,
            'amount'             => $amount,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }


    public function adjustAdminsBasedOnTarget($room, $target): void
    {
        $currentTotal = $room->total_admins;
        $targetTotal  = (int) $target->number_of_admins;

        $difference = $targetTotal - $currentTotal;

        if ($difference === 0) {
            return;
        }

        if ($difference > 0) {
            $room->additional_admin += $difference;
        } else {
            $difference = abs($difference);
            $room->additional_admin = max(0, $room->additional_admin - $difference);
        }
        $room->save();
        $this->normalizeRoomAdmins($room);
    }

    private function logRoomCup(string $message): void
    {
        Log::channel('roomCup')->info($message);
    }
    private function normalizeRoomAdmins($room): void
    {
        $roomAdmin = $room->room_admin;
        $roomMax   = $room->total_admins;
        $configMaxRoom = Common::getConfig('max_room_admin') ?? 4;

        $adm_arr = ($roomAdmin == '') ? [] : explode(",", trim($roomAdmin));
        $adm_arr = array_filter(array_unique($adm_arr));

        $allowedMax = ($roomMax >= $configMaxRoom) ? $roomMax : $configMaxRoom;

        if (count($adm_arr) > $allowedMax) {
            $adm_arr = array_slice($adm_arr, 0, $allowedMax);
        }
        $str = implode(",", $adm_arr);
        $room->update(['room_admin' => $str]);
    }

    private static function resetRoomSessions(): void
    {
         Room::query()->update(['session' => 0]);
    }
}
