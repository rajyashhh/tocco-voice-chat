<?php

namespace App\Tik\Services;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use Exception;
use App\Models\User;
use App\Models\Agency;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\ShippingAgency;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\AgencyRepository;
use App\Tik\Repositories\ChargeRepository;
use App\Tik\Repositories\CoinLogRepository;
use App\Tik\Repositories\RoomSalaryRepository;
use App\Tik\Repositories\UserSalaryRepository;
use App\Tik\Repositories\AgencySalaryRepository;
use App\Http\Resources\Api\V1\GeneralUserResource;
use App\Tik\Repositories\ShippingAgencyRepository;
use App\Http\Resources\Api\V1\GeneralAgencyResource;
use Modules\SalaryTransaction\Entities\ChargeAgency;
use Modules\Achievement\Http\Services\UserAchievementService;

class ChargeRepoService
{
    public function __construct(
        private readonly ChargeRepository $chargeRepository,
        private readonly RoomSalaryRepository $roomSalaryRepo,
        private readonly UserRepository $userRepository,
        private readonly UserSalaryRepository $userSalaryRepository,
        private readonly AgencyRepository $agencyRepository,
        private readonly ShippingAgencyRepository $shippingAgencyRepository,
        private readonly AgencySalaryRepository $agencySalaryRepository,
        private readonly CoinLogRepository $coinLogRepository
    ) {}

    public function create(array $data)
    {
        return $this->chargeRepository->create($data);
    }

    public function chargeCoinsFromOwner($amount, $userId, $toUserUuId)
    {
        $userResve = $this->userRepository->searchUser($toUserUuId);
        if (! $userResve) {
            throw new Exception('this user not found');
        }

        $user_id = $userResve->id;
        $room = $userResve->ownerRoom;

        if (! isset($room)) {
            throw new Exception('room not founded');
        }
        $salary = $room->salary;
        if ($salary < $amount) {
            throw new Exception('Low Balance');
        }
        try {
            \DB::beginTransaction();
            // Increment 'di' column for the user
            $coinPrise = Common::getConf('one_usd_value_in_coins') ?? 50;
            $coins = $coinPrise * $amount;
            $userType = $userResve->user_type;

            $data = [
                'charger_id' => $userId,
                'charger_type' => 'host_agency',
                'user_id' => $user_id,
                'user_type' => $userType,
                'amount' => $coins,
                'usd' => $amount,
                'amount_type' => 2,
                'is_used_transferred' => true,
            ];

            $this->create($data);
            $this->roomSalaryRepo->incrementCutAmount($room->id, $amount);
            $this->userRepository->incrementCoins($toUserUuId, $coins);

            \DB::commit();
            (new UserAchievementService())->insertCharging($userResve, $coins);
            UserCommon::UserEarnedInvitation($userResve->id, $coins);

            return true; //
        } catch (Exception $e) {
            \DB::rollBack();
            throw new Exception('An error occurred, please try again later');
        }
    }

    public function chargeTo(User $fromUser, User $toUser, $coins, $isRoomTarget, $usd)
    {
        $chargeType = 'user';

        try {

            if (! $isRoomTarget) {

                $this->userSalaryRepository->incrementCutAmount($fromUser->id, $usd);
            } else {
                $this->roomSalaryRepo->incrementCutAmount($fromUser->ownerRoom?->id, $usd);
            }
            $this->charge($fromUser, $toUser, $chargeType, $coins, $usd);

            if ($toUser instanceof User) {
                (new UserAchievementService())->insertCharging($toUser, $coins);
            }
            UserCommon::UserEarnedInvitation($toUser->id, $coins);
            UserCommon::addChargeLevel($toUser->id, $coins);

            return true;
        } catch (Exception $e) {
            \DB::rollBack();
            throw new Exception('An error occurred, please try again later');
        }
    }

    public function chargeToAgency(User $fromUser, ShippingAgency $toAgency, $coins, $isRoomTarget, $usd)
    {
        // $chargeType = $isRoomTarget ? 'room_owner' : 'host';
        $chargeType = 'user';

        try {

            if (!$isRoomTarget) {

                $this->userSalaryRepository->incrementCutAmount($fromUser->id, $usd);
            } else {
                $this->roomSalaryRepo->incrementCutAmount($fromUser->ownerRoom?->id, $usd);
            }

            $this->chargeAgencyNew($fromUser, $toAgency, $chargeType, $coins, $usd);

            return true;
        } catch (Exception $e) {
            \DB::rollBack();

            throw new Exception('An error occurred, please try again later');
        }
    }

    public function sendMoney(User $sender, $receiverUuid, $count)
    {

        $agency = $this->shippingAgencyRepository->findAgencyByOwnerId($sender->id, 1);
        if (! $agency || $agency->status === 0) {
            throw new Exception(__('api_responses.canNotCharge'));
        }

        if ($agency->is_frozen === 1) {
            throw new Exception(__('api_responses.frozen_agency'));
        }

        $userReceiver = $this->userRepository->searchUser($receiverUuid);
        if (! $userReceiver) {
            throw new Exception('this user not found');
        }

        $this->userRepository->decrementUserCoins($sender, $count);
        // $percentage = Common::getConf("one_usd_value_in_coins") ?? 1;4
        $percentage = Common::getCoinsValue('user_coins');

        $usd = $count / $percentage;
        $charge = $this->charge($sender, $userReceiver, 'agency', $count, $usd);

        return [$userReceiver, $charge?->id];
    }

    public function getChargeUserHistory($userId, $type, $by_date = null, $chargeType = null, $searchKey = null)
    {
        $charge = $this->chargeRepository->getChargeHistory($chargeType);
        if ($type === 'received') {
            $charge = $charge/* ->where('user_type', $charger_type) */->where('user_id', $userId);
        }
        if ($type === 'sent') {
            $charge = $charge/* ->where('charger_type', $charger_type) */->where('action_user_id', $userId);
        }

        if ($searchKey !== null) {
            $charge = $charge->when($searchKey, fn($query) => $query->whereHas('sender', fn($q) => $q->where('uuid', 'like', $searchKey)));
        }
        if ($by_date) {
            $charge = $charge->where('created_at', 'like', "%$by_date%");
        }

        return $charge->orderByDesc('created_at')->get();
    }

    public function chargeDollarForOwner(User $sender, $receiverUuid, $count)
    {
        try {

            $receiver = $this->userRepository->searchUserById($receiverUuid);
            if (! $receiver) {
                throw new Exception('this user not found');
            }
            if ($receiver->transfer_salary === 1) {
                throw new Exception('api.freez_charge_user');
            }

            $agency = $this->agencyRepository->findByStatus($sender->agency_id);
            if (! isset($agency)) {
                throw new Exception(__('your agency not found'));
            } // Your agency stopped call the administrator

            if ($agency->is_frozen === 1) {
                throw new Exception(__('api_responses.frozen_agency'));
            }

            if ($agency->status === 0 || $agency->app_owner_id !== $sender->id) {
                throw new Exception(__('api_responses.canNotCharge'));
            }

            $salary = $agency->salary;

            if ($salary < $count) {
                throw new Exception('Low Balance');
            }

            $coinPrise = Common::getCoinsValue('user_coins');
            $numDi = $coinPrise * $count;
            $charge = $this->charge(sender: $agency, receiver: $receiver, chargeType: 'host_agency', amount: $numDi, usd: $count, transferred: true);
            $this->agencySalaryRepository->incrementCutAmount($agency->id, $count);

            return [$receiver, $numDi, $salary, $charge?->id];
        } catch (Exception $e) {
            // \DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function chargeDollarForOwner_to_agency(User $sender, $receiverid, $count)
    {

        try {

            $receiver = Common::searchAgency($receiverid);

            if (! $receiver) {
                throw new Exception('this  not found');
            }

            if ($receiver->is_frozen === 1) {
                throw new Exception(__('api_responses.frozen_agency'));
            }

            $agency = $this->shippingAgencyRepository->findAllByStatus($sender->agency_id);
            if (! isset($agency)) {
                throw new Exception('agency not founded');
            }
            if ($agency->is_frozen === 1) {
                throw new Exception(__('api_responses.frozen_agency'));
            }
            if ($agency->is_frozen === 1) {
                throw new Exception(__('api_responses.frozen_agency'));
            }

            if ($agency->status === 0 || $agency->app_owner_id !== $sender->id) {
                throw new Exception(__('api_responses.canNotCharge'));
            }

            $salary = $agency->salary;

            if ($salary < $count) {
                throw new Exception('Low Balance');
            }

            // DB::beginTransaction();
            // Increment 'di' column for the user
            // $coinPrise = Common::getConf('one_usd_value_in_coins') ?? 50;

            $coinPrise = Common::getCoinsValue('shipping_coins');
            $numDi = $coinPrise * $count;
            $this->chargeAgency(sender: $agency, receiver: $receiver, chargeType: 'host_agency', amount: $numDi, usd: $count, transferred: true);
            $this->agencySalaryRepository->incrementCutAmount($agency->id, $count);

            return [$receiver, $numDi, $salary];
        } catch (Exception $e) {
            // \DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function charge($sender, User $receiver, $chargeType, $amount, $usd = null, $transferred = false)
    {

        if ($chargeType === 'user') {
            WalletService::storeTransaction(
                $sender->id,
                'cut',
                $usd,
                'user_transaction',
                'transfer_to_user',
                ['receiver_id' => $receiver->id],
                'charge_to_user'

            );
        }
        $amountBefore =  $receiver->di;
        UserCoinLogHelper::logByType(
            $receiver->id,
            $amount,
            $amountBefore,
            UserCoinLogType::APP_CHARGE,
        );

        // $type = $receiver->user_type;
        $this->userRepository->incrementUserCoins($receiver, $amount);

        if ($usd === null) {
            $userCoinsRate = Common::getCoinsValue('user_coins') ?: 1;
            $usd = $amount / $userCoinsRate;
        }

        $data = [
            'charger_id' => $sender->id,
            'charger_type' => $chargeType,
            'user_id' => $receiver->id,
            'user_type' => 'user',
            'amount' => $amount,
            'amount_type' => 2,
            'usd' => $usd,
            'is_used_transferred' => $transferred,
            'action_user_id' => auth()->user()->id,
        ];
        return $this->create($data);
    }

    public function getCoinLogs($userId, ?string $searchKey = null)
    {
        return $this->coinLogRepository->getCoinsByUserId($userId, $searchKey);
    }

    public function chargeAgencyNew(User $sender, ShippingAgency $receiver, $chargeType, $amount, $usd = null, $transferred = false)
    {
        $type = $receiver->owner?->user_type ?? '';

        $receiver->increment('coins', $amount);

        WalletService::storeTransaction(
            $sender->id,
            'cut',
            $usd,
            'user_transaction',
            'transfer_to_agency',
            ['agency_id' => $receiver->id],
            'charge_to_agency'
        );

        $data = [
            'charger_id' => $sender->id,
            'charger_type' => $chargeType,
            'user_id' => $receiver->id,
            'agency_id' => null,
            'user_type' => 'agency',
            'amount' => $amount,
            'amount_type' => 2,
            'usd' => $usd ?? (function () use ($amount) {
                $shippingCoinsRate = Common::getCoinsValue('shipping_coins') ?: 1;
                return $amount / $shippingCoinsRate;
            })(),
            'is_used_transferred' => $transferred,
        ];

        $this->create($data);
    }

    public function chargeAgency($sender, Agency|ShippingAgency $receiver, $chargeType, $amount, $usd = null, $transferred = false)
    {


        $receiver->increment('coins', $amount);

        if ($usd === null) {
            $shippingCoinsRate = Common::getCoinsValue('shipping_coins') ?: 1;
            $usd = $amount / $shippingCoinsRate;
        }

        $data = [
            'charger_id' => $sender->id,
            'charger_type' => $chargeType,
            'user_id' => $receiver->id,
            'agency_id' => null,
            'user_type' => 'agency',
            'amount' => $amount,
            'amount_type' => 2,
            'usd' => $usd,
            'is_used_transferred' => $transferred,
            'action_user_id' => auth()->user()->id,

        ];

        $this->create($data);
    }

    public function userAgencySearch($request)
    {
        $type = $request->type;
        $id = $request->id;

        if ($type === 'agency') {
            return [
                'agency' => GeneralAgencyResource::collection(
                    $this->shippingAgencyRepository->filterAgency($id)
                ),
                'user' => [],
            ];
        }

        if ($type === 'user') {
            return [
                'agency' => [],
                'user' => GeneralUserResource::collection(
                    $this->userRepository->filterUserNew($id)
                ),
            ];
        }

        return [
            'agency' => [],
            'user' => [],
        ];
    }


    public function chargeAgencyToAnother(User $auth, $request)
    {
        DB::beginTransaction();

        try {

            switch ($request->type) {
                case 'agency':
                    $authAgency = $this->shippingAgencyRepository->getAgencyByOwnerId($auth->id);
                    if (! $authAgency) {
                        throw new Exception(__('api.notAgency'));
                    }
                    if ($authAgency->is_frozen) {
                        throw new Exception(__('api_responses.frozenMassForYou'));
                    }
                    if (! $authAgency->status) {
                        throw new Exception(__('api.notCharge'));
                    }

                    if ($authAgency->app_owner_id !== $auth->id) {
                        throw new Exception(__('api.yorSelf'));
                    }
                    if ($authAgency->coins < $request->amount) {
                        throw new Exception(__('api.notHaveAmount'));
                    }

                    $this->handleAgencyCharge($authAgency, $request);
                    break;

                case 'user':
                    $authAgency = $this->shippingAgencyRepository->getAgencyByOwnerId($auth->id);
                    if (! $authAgency) {
                        throw new Exception(__('api.notAgency'));
                    }
                    if ($authAgency->is_frozen) {
                        throw new Exception(__('api_responses.frozenMassForYou'));
                    }

                    $this->handleUserCharge($authAgency, $auth, $request);
                    break;

                default:
                    throw new Exception(__('api.invalidType'));
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function agencyCharge($chargerId, $userId, $amount, $type, $usd, $chargeType, $transferred = false, $agencyId = null)
    {
        if ($usd === null) {
            $coinsKey = ($type === 'agency') ? 'shipping_coins' : 'user_coins';
            $coinsRate = Common::getCoinsValue($coinsKey) ?: 1;
            $usd = $amount / $coinsRate;
        }

        $data = [
            'charger_id' => $chargerId,
            'charger_type' => $chargeType,
            'user_id' => $userId,
            'user_type' => $type,
            'amount' => $amount,
            'amount_type' => 2,
            'usd' => $usd,
            'is_used_transferred' => $transferred,
            'agency_id' => $agencyId,
        ];
        $this->create($data);
    }

    public function getChargeToUserHistory()
    {

        return $this->chargeRepository->getChargeToUserHistory();
    }

    public function getChargeAgencyHistory()
    {
        return $this->chargeRepository->getChargeToAgencyHistory();
    }

    private function handleAgencyCharge($authAgency, $request)
    {

        // if ($authAgency->id == $request->id) {
        //     throw new \Exception(__('api.notYourself'));
        // }
        $chargeAgency = $this->shippingAgencyRepository->findOrFail($request->id);
        if (! $chargeAgency) {
            throw new Exception(__('api.notAgencyFound'));
        }
        if (! $chargeAgency->status) {
            throw new Exception(__('api.notActive'));
        }
        if ($chargeAgency->is_frozen) {
            throw new Exception(__('api_responses.frozenMass'));
        }

        $this->processAgencyCharge($authAgency, $chargeAgency, $request->amount);
    }

    private function handleUserCharge($authAgency, $auth, $request)
    {
        $receiver = $this->userRepository->searchUserById($request->id);

        if (! $receiver) {
            throw new Exception(__('api.notUser'));
        }

        $this->processUserCharge($authAgency, $receiver, $request->amount);
    }

    /**
     * @throws Exception
     */
    private function processAgencyCharge($authAgency, $chargeAgency, $amount)
    {
        if ($authAgency->coins < $amount) {
            throw new Exception(__('balance not enough'));
        }



        $authAgency->decrement('coins', $amount);
        $chargeAgency->increment('coins', $amount);
        $usdRate = $amount / Common::getCoinsValue('shipping_coins');

        $this->agencyCharge(
            chargerId: $authAgency->id,
            userId: $chargeAgency->id,
            amount: $amount,
            type: 'agency',
            usd: $usdRate,
            chargeType: 'agency',
            agencyId: null,
        );
    }

    /**
     * @throws Exception
     */
    private function processUserCharge($authAgency, $receiver, $amount)
    {
        if ($authAgency->coins < $amount) {
            throw new Exception(__('balance not enough'));
        }
        $amountBefore =  $receiver->di;
        UserCoinLogHelper::logByType(
            $receiver->id,
            $amount,
            $amountBefore,
            UserCoinLogType::APP_CHARGE,
        );

        $authAgency->decrement('coins', $amount);
        $receiver->increment('di', $amount);
        $usdRate = $amount / Common::getCoinsValue('user_coins');

        $this->agencyCharge(
            chargerId: $authAgency->id,
            userId: $receiver->id,
            amount: $amount,
            type: 'user',
            usd: $usdRate,
            chargeType: 'agency',
            agencyId: null,
        );

        if ($receiver instanceof User) {
            (new UserAchievementService())->insertCharging($receiver, $amount);
        }

        UserCommon::UserEarnedInvitation($receiver->id, $amount);
        UserCommon::addChargeLevel($receiver->id, $amount);
    }
}
