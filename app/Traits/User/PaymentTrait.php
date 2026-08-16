<?php

namespace App\Traits\User;

use App\Enums\UserCoinLogType;
use App\Exceptions\PaymentOwnerMissingException;
use App\Helpers\Common;
use App\Helpers\LogHelper;
use App\Helpers\UserCoinLogHelper;
use App\Models\Coin;
use App\Models\ShippingAgency;
use App\Models\User;
use App\Models\CoinLog;
use App\Helpers\UserCommon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Achievement\Http\Services\UserAchievementService;


trait PaymentTrait
{

    /**
     * @param $orderId
     * @param mixed $productId
     * @param int|string|null $userId
     * @param $type = null
     * @return false|CoinLog
     */
    public function makePayment($orderId, mixed $productId, int|string|null $userId, $type = null): false | CoinLog
    {
        if ($userId === null) return false;

        // trx carries the unique index, but MySQL allows multiple NULLs under it,
        // so a null/empty gateway reference would slip past the idempotency claim
        // and could be credited more than once. Reject it explicitly.
        if ($orderId === null || $orderId === '') return false;

        $coins = Coin::find($productId);
        if (!$coins) return false;

        // The coin_log is the idempotency claim: its trx column carries a unique
        // index, so a second delivery of the same gateway reference collides on
        // insert (1062) and credits nothing. Claim + credit run in one locked
        // transaction so a concurrent replay can never read a stale "not paid"
        // state and double-mint.
        try {
            $data = DB::transaction(function () use ($orderId, $userId, $type, $coins) {
                $coinLog = CoinLog::create([
                    "obtained_coins" => $coins->coin,
                    "user_id"        => $userId,
                    'method'         => $type,
                    'donor_id'       => 0,
                    'donor_type'     => 0,
                    'status'         => 1,
                    'trx'            => $orderId,
                    'paid_usd'       => $coins->usd ?? 0,
                ]);

                $user = User::whereKey($userId)->lockForUpdate()->first();
                if (!$user) {
                    // No owner to credit; abort the whole claim so the reference
                    // stays replayable once the account exists.
                    throw new PaymentOwnerMissingException();
                }

                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $coins->coin,
                    $amountBefore,
                    UserCoinLogType::PAYMENT,
                );

                $user->increment('di', $coins->coin);
                UserCommon::addChargeLevel($user->id, $coins->coin);

                return $coinLog;
            });
        } catch (PaymentOwnerMissingException $e) {
            return false;
        } catch (QueryException $e) {
            // Duplicate gateway reference (unique trx) => already credited.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return false;
            }
            throw $e;
        }

        // Target progression only advances when a real credit was applied.
        UserCommon::updateUserTotalCoins($userId, $coins->coin);

        return $data;
    }

    public function webhookPayment($orderId, $method = null, $newTrx = null): JsonResponse
    {

        info('in trait');
        if ($method === 'paypal'){
            info('in paypal');
            $coinLog = $this->findCoinLogTrx($orderId, $method);
        } else {
            // First attempt: find by coinlog id
            $coinLog = $this->findCoinLog($orderId, $method);
            // Fallback: find by trx if id doesn't exist (UTD generates orderId as trx)
            if (!$coinLog) {
                info('webhookPayment: fallback to trx lookup for orderId', ['orderId' => $orderId, 'method' => $method]);
                $coinLog = $this->findCoinLogTrx($orderId, $method);
            }
        }

        info($coinLog);
        if (!$coinLog) {
            return $this->transactionNotFoundResponse();
        }

        // Claim + credit atomically. The coin_log row is locked and its status
        // re-read under the lock, so two concurrent callbacks for the same order
        // cannot both pass the "not processed" check and double-credit.
        $credited = DB::transaction(function () use ($coinLog, $newTrx) {
            $locked = CoinLog::whereKey($coinLog->getKey())->lockForUpdate()->first();

            if (!$locked || $this->isAlreadyProcessed($locked)) {
                return false;
            }

            $this->updateCoinLogAsPaid($locked, $newTrx);
            $this->resolveCoinLogOwner($locked);

            return true;
        });

        if (!$credited) {
            return $this->alreadyProcessedResponse();
        }

        return $this->finalizeResponse($coinLog);
    }

    private function findCoinLog($orderId, $method = null): ?CoinLog
    {
        return CoinLog::where('id', $orderId)
            ->when($method != null, fn($q) => $q->where('method', $method))
            ->first();
    }

    private function findCoinLogTrx($orderId, $method = null): ?CoinLog
    {
        return CoinLog::where('trx', $orderId)
            ->when($method != null, fn($q) => $q->where('method', $method))
            ->first();
    }

    private function isAlreadyProcessed(CoinLog $coinLog): bool
    {
        return $coinLog->status == 1;
    }

    private function updateCoinLogAsPaid(CoinLog $coinLog, $newTrx = null): void
    {
        $coinLog->update(['status' => 1]);

        if ($newTrx){
            $coinLog->update(['trx' => $newTrx]);
        }
    }

    private function resolveCoinLogOwner(CoinLog $coinLog)
    {
        $owner = $coinLog->owner;

        if ($owner instanceof User) {
            return $this->processUserPayment($owner, $coinLog);
        }

        if ($owner instanceof ShippingAgency) {
            return $this->processAgencyPayment($owner, $coinLog);
        }

    }

    private function processUserPayment(User $user, CoinLog $coinLog): void
    {
        $user = User::whereKey($user->getKey())->lockForUpdate()->first();

        $amountBefore = $user->di;
        $user->increment('di', $coinLog->obtained_coins);

        UserCoinLogHelper::logByType(
            $user->id,
            $coinLog->obtained_coins,
            $amountBefore,
            UserCoinLogType::PAYMENT
        );

        UserCommon::addChargeLevel($user->id, $coinLog->obtained_coins);

        (new UserAchievementService())->insertCharging($user, $coinLog->obtained_coins);

    }

    private function processAgencyPayment(ShippingAgency $agency, CoinLog $coinLog): void
    {
        ShippingAgency::whereKey($agency->getKey())
            ->lockForUpdate()
            ->first()
            ?->increment('coins', $coinLog->obtained_coins);
    }

    private function transactionNotFoundResponse()
    {
        return response()->json([
            'status' => 'failed',
            'reason' => 'Transaction not found',
        ]);
    }

    private function alreadyProcessedResponse()
    {
        return response()->json([
            'status' => 'failed',
            'reason' => 'Transaction already processed',
        ]);
    }

    private function finalizeResponse(CoinLog $coinLog)
    {
        return response()->json([
            'status'  => true,
            'trx'     => $coinLog->trx,
            'message' => 'Transaction completed successfully.',
        ]);
    }


}
