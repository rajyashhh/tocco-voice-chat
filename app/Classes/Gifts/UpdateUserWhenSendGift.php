<?php

namespace App\Classes\Gifts;

use App\Classes\Enums\NotificationType;
use App\Exceptions\NotInfMoneyException;
use App\Jobs\IncreaseDiamondJob;
use App\Jobs\SendCustomOfficialMessageToUser;
use App\Models\User;
use Mockery\Exception;
use Modules\Vip\Entities\Vip;
use App\Models\UserGift;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Modules\Public\Http\Services\UpgradeReceiverLevelServices;

class UpdateUserWhenSendGift
{

    private array $expPercentages;

    public function __construct()
    {
        $this->expPercentages = Config::get('exp_percentages') ?? [1, 1];
    }

    public function update(int $totalCoins, User $receivedUser)
    {
        // Atomic counter increment — no lockForUpdate, no read-then-save. The hot
        // users row is touched by a single conditional UPDATE so concurrent gift
        // receivers never serialize on it and no lost updates occur.
        $affected = User::where('id', $receivedUser->id)
            ->update([
                'salary_is_updated'      => DB::raw('GREATEST(salary_is_updated, 1)'),
                'total_diamond_received' => DB::raw("total_diamond_received + {$totalCoins}"),
                'exchange_diamonds'      => DB::raw("exchange_diamonds + IF(type_user = 0 AND agency_id = 0, {$totalCoins}, 0)"),
            ]);

        if ($affected === 0) {
            throw new \Exception("User not found");
        }

        IncreaseDiamondJob::dispatch($receivedUser->id, $totalCoins)
            ->afterCommit()
            ->onQueue('increment-diamond');

        // Level upgrade + notification run AFTER the atomic write, holding no lock
        // and outside any DB::transaction, so the upgrade service queries never
        // extend a lock window on the hot users row.
        $this->recalculateReceiverLevelAndNotify($receivedUser->id);
    }

    /**
     * Recompute a single receiver's level and notify on change. Lock-free:
     * reads fresh state, runs the upgrade service, persists only if changed.
     */
    private function recalculateReceiverLevelAndNotify(int $userId): void
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return;
            }

            $lastReceivedLevel = $user->total_received_level;

            (new UpgradeReceiverLevelServices())->checkUserLevelUpgrated($user);
            $user->save();

            if ($user->total_received_level != $lastReceivedLevel) {
                dispatch(new SendCustomOfficialMessageToUser($user->id, NotificationType::RECEIVED_LEVEL))
                    ->onQueue('notification');
            }
        } catch (\Exception $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/diamond_upgrade.log'),
            ])->error("Error in checkUserLevelUpgrated for user {$userId}: " . $e->getMessage());
        }
    }

    public function updateUsers(int $totalCoins, array $userIds)
    {
        sort($userIds);

        // Snapshot prior levels (lock-free read) so we can detect changes after.
        $priorLevels = User::whereIn('id', $userIds)
            ->pluck('received_level', 'id');

        if ($priorLevels->isEmpty()) {
            return;
        }

        // Atomic bulk increment — single conditional UPDATE, no lockForUpdate and
        // no per-row read-then-save, so concurrent senders don't serialize on the
        // receivers' rows and no diamond updates are lost.
        User::whereIn('id', $userIds)->update([
            'salary_is_updated'      => DB::raw('GREATEST(salary_is_updated, 1)'),
            'total_diamond_received' => DB::raw("total_diamond_received + {$totalCoins}"),
            'exchange_diamonds'      => DB::raw("exchange_diamonds + IF(agency_id = 0, {$totalCoins}, 0)"),
        ]);

        $usersById = User::whereIn('id', $priorLevels->keys()->all())
            ->get()
            ->keyBy('id');

        foreach ($priorLevels as $userId => $lastReceivedLevel) {
            $user = $usersById->get($userId);
            if (!$user) {
                continue;
            }

            try {
                (new UpgradeReceiverLevelServices())->checkUserLevelUpgrated($user);
                $user->save();

                if ($user->total_received_level != $lastReceivedLevel) {
                    dispatch(new SendCustomOfficialMessageToUser($user->id, NotificationType::RECEIVED_LEVEL))
                        ->onQueue('notification');
                }
            } catch (\Exception $e) {
                // ANY QueryException (deadlock 1213, lock-wait 1205, server-gone,
                // ...) may have implicitly rolled back the WHOLE surrounding
                // transaction server-side; swallowing it would let a caller's money
                // transaction keep committing statements autocommit and then die on
                // commit() with "There is no active transaction". Re-throw so
                // DB::transaction can roll back cleanly and retry.
                if (DB::transactionLevel() > 0
                    && $e instanceof \Illuminate\Database\QueryException) {
                    throw $e;
                }
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/diamond_upgrade.log'),
                ])->error("Error in checkUserLevelUpgrated for user {$user->id}: " . $e->getMessage());
            }

            incrementMonthlyDiamond(
                $user->id,
                $totalCoins
            );
        }
    }
    public function updateReceivedLevels(User $receivedUser)
    {
        return DB::transaction(function () use ($receivedUser) {
            $user = User::where('id', $receivedUser->id)->lockForUpdate()->first();

            if (!$user) {
                throw new \Exception("User not found");
            }

            $lastReceivedLevel = $user->total_received_level;
            $totalDiamondReceived = $user->total_received_diamonds;
            $levelVip = $this->getLevel(1, $totalDiamondReceived);
            $receivedLevel = $levelVip != null ? (@$levelVip->level - $user->sub_receiver_level) ?? 0 : 0;

            DB::table('users')->where('id', $user->id)->update([
                'received_level' => $receivedLevel,
            ]);

            $user->refresh();

            if ($user->total_received_level != $lastReceivedLevel) {
                dispatch(new SendCustomOfficialMessageToUser($user->id, NotificationType::RECEIVED_LEVEL))
                    ->onQueue('notification');
            }

            return $user;
        });
    }

    public function getLevel(int $type, int $totalCoins)
    {

        return Vip::query()->where(['type' => $type])->where('exp', '<=', $totalCoins)->orderByDesc('exp')->orderByDesc('level')->limit(1)->first();
    }


    /*
     * 1 for receiver
     * 2, 3 for sender or vip
     */
    public function send(int $totalCoins, User $senderUser)
    {

        $affected = User::where('id', $senderUser->id)
            ->where('di', '>=', $totalCoins)
            ->update([
                'di' => DB::raw("di - {$totalCoins}"),
                'monthly_diamond_send' => DB::raw("monthly_diamond_send + {$totalCoins}"),
                'total_diamond_send' => DB::raw("total_diamond_send + {$totalCoins}")
            ]);

        if ($affected === 0) {
            throw new NotInfMoneyException();
        }

        // Re-fetch latest user state after raw DB update
        $senderUser->refresh();

        $lastLevel = $senderUser->total_sender_level;

        (new UpgradeLevelServices())->checkUserLevelUpgrated($senderUser);

        if ($senderUser->total_sender_level != $lastLevel) {
            dispatch(
                new SendCustomOfficialMessageToUser(
                    $senderUser->id,
                    NotificationType::SENDER_LEVEL
                )
            )->onQueue('notification');
        }


        return $senderUser;
    }
    public function sendFromBagAndRemoveGift(int $totalCoins, User $senderUser, int $giftId, int $number)
    {
        return DB::transaction(function () use ($totalCoins, $senderUser, $giftId, $number) {
            $user = User::where('id', $senderUser->id)->lockForUpdate()->first();

            if (!$user) {
                throw new \Exception("User not found");
            }

            $userGift = UserGift::where('user_id', $user->id)
                ->where('gift_id', $giftId)
                ->where('quantity', '>', 0)
                ->where(function ($query) {
                    $query->where('expire', 0)
                        ->orWhereRaw('DATE_ADD(created_at, INTERVAL expire DAY) >= NOW()');
                })
                ->lockForUpdate()
                ->first();

            throw_if((!$userGift), \Exception::class, 'Receiver has reached maximum allowed gifts');

            $updateData = [
                'monthly_diamond_send' => DB::raw("monthly_diamond_send + {$totalCoins}"),
                'total_diamond_send' => DB::raw("total_diamond_send + {$totalCoins}"),
            ];

            DB::table('users')->where('id', $user->id)->update($updateData);

            $userGift->quantity -= $number;

            if ($userGift->quantity > 0) {
                $userGift->save();
            } else {
                $userGift->delete();
            }

            $user->refresh();

            $lastSenderUser = $user->total_sender_level;

            (new UpgradeLevelServices())->checkUserLevelUpgrated($user);

            if ($user->total_sender_level > $lastSenderUser) {
                dispatch(new SendCustomOfficialMessageToUser(
                    $user->id,
                    NotificationType::SENDER_LEVEL
                ))->onQueue('notification');
            }

            return $user;
        });
    }


    public function getSenderLevel($totalDiamondSend, $totalDiamond, int $subSenderLevel)
    {
        $total = intval($totalDiamondSend + $totalDiamond) * $this->expPercentages['exp_sender_percentage'];
        // dd($total,$totalDiamondSend,$totalDiamond ,$this->expPercentages['exp_sender_percentage']);
        $levelVip = $this->getLevel(2, $total);
        return $levelVip != null ? (@$levelVip->level) ?? 0 : 0;
    }

    public function getRoomLevel($total)
    {
        // $total = intval($totalDiamondSend + $totalDiamond) * $this->expPercentages[0] ;
        $levelVip = $this->getLevel(4, $total);
        return $levelVip != null ? @$levelVip->level ?? 0 : 0;
    }

    public function getRoomLevelDetails($total)
    {
        $levelVip = $this->getLevel(4, $total);
        return $levelVip;
    }

    public function getReceiverLevel($totalDiamondReceived, $totalDiamond, int $subReceiverLevel)
    {
        $total = intval($totalDiamondReceived + $totalDiamond) * $this->expPercentages['exp_received_percentage'];

        $levelVip = $this->getLevel(1, $total);

        return $levelVip != null ? (@$levelVip->level) ?? 0 : 0;
    }
}
