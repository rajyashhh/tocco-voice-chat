<?php

namespace App\Helpers;

use App\Enums\UserCoinLogType;
use App\helper\InvitationEarningHelper;
use App\helper\InvitationWalletHelper;
use Illuminate\Support\Facades\Log;
use Modules\SwitchAccount\Entities\UserDevicesHistory;
use Modules\Vip\Entities\OVip;
use Modules\Vip\Entities\Vip;
use App\Models\Gift;
use App\Models\Pack;
use App\Models\Room;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use App\Models\Config;
use App\Models\Target;
use Encore\Admin\Show;
use GuzzleHttp\Client;
use App\Models\Country;
use App\Models\GiftLog;
use App\Models\PackLog;
use Modules\Vip\Entities\UserVip;
use App\Models\UserSallary;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use Kreait\Firebase\Factory;
use App\Models\UserLuckyGift;
use Illuminate\Support\Carbon;
use App\Models\OfficialMessage;
use Encore\Admin\Facades\Admin;
use App\Models\UserCodeInvitation;
use App\Models\UserEarnInvitation;
use Illuminate\Support\Facades\DB;
use App\Models\AgencyMangerPullingOut;
use App\Traits\HelperTraits\InfoTrait;
use App\Traits\HelperTraits\RoomTrait;
use Modules\Vip\Helpers\VipCommon;
use Twilio\Rest\Client as TwilioClint;

use App\Http\Resources\CountryResource;
use App\Traits\HelperTraits\AdminTrait;
use App\Traits\HelperTraits\CalcsTrait;
use App\Traits\HelperTraits\MoneyTrait;
use App\Traits\HelperTraits\FilterTrait;
use App\Traits\HelperTraits\AttributesTrait;
use Illuminate\Database\Eloquent\Collection;
use App\Classes\Facades\Agency as FacadesAgency;
use Modules\Public\Http\Services\UserCounterServices;
use App\Models\CoinTarget;
use App\Models\UserCoinTarget;
use App\Models\UserTargetCoin;
use App\Facades\CustomNotification;
use Modules\Badge\Entities\UserBadge;

class UserCommon
{


    public static function specialTransfer($amount)
    {
        $usd_trans = Config::where("name", 'special_transfer_to_usd')->first();
        $data = 0;
        if ($usd_trans != null) {
            $data = $amount / $usd_trans->value;
        }
        return $data;
    }

    public static function CheckUserNew($userId)
    {
        $user = User::where("id", $userId)->first();
        $createdAt = new \Carbon\Carbon($user->created_at);
        $now = \Carbon\Carbon::now();
        $data = UserCodeInvitation::with("user")->where(['invited_id' => $userId])->first();
        if ($createdAt->diffInHours($now) <= 48 && $data == null) {
            return true;
        } else {
            return false;
        }
    }

    public static function CheckUserParent($userId)
    {
        $data = UserCodeInvitation::with("user")->where(['invited_id' => $userId])->first();
        return $data;
    }

    public static function getColoredName(User $user)
    {
        $hasColor  = Common::hasInPack(@$user?->senderShippingAgency?->owner?->id, 18, true);
        $colorName = (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip(@$user?->senderShippingAgency?->owner?->id, 18, 'color') : null);
        return $colorName;
    }

    public static function UserStatistic($userId, $type, bool $reals = false, \DateTime $startDate = null, \DateTime $endDate = null)
    {
        $timezone = getTimezone();
        if ($startDate == null || $endDate == null) {

            $startDate = Carbon::now($timezone)->startOfMonth()->timezone('UTC');
            $endDate = Carbon::now($timezone)->endOfMonth()->timezone('UTC');
        }
        $user = User::withCount(["reals" => function ($reals) use ($type, $startDate, $endDate) {
            if ($type == 0) {
                $reals->whereBetween("reals.created_at", getToday());
            } elseif ($type == 1) {
                $reals->whereBetween('reals.created_at', [$startDate, $endDate]);
            }
        }, 'real_comments' => function ($real_comments) use ($type, $startDate, $endDate) {
            if ($type == 0) {
                $real_comments->whereBetween("real_user_comments.created_at", getToday());
            } elseif ($type == 1) {
                $real_comments->whereBetween("real_user_comments.created_at", [$startDate, $endDate]);
            }
        }, 'real_likes' => function ($real_likes) use ($startDate, $endDate, $type) {
            if ($type == 0) {
                $real_likes->whereBetween("real_user_likes.created_at", getToday());
            } elseif ($type == 1) {
                $real_likes->whereBetween("real_user_likes.created_at", [$startDate, $endDate]);
            }
        }, 'moments' => function ($moments) use ($startDate, $endDate, $type) {
            if ($type == 0) {
                $moments->whereBetween("moment.created_at", getToday());
            } elseif ($type == 1) {
                $moments->whereBetween("moment.created_at", [$startDate, $endDate]);
            }
        }, 'moment_comments' => function ($moment_comments) use ($startDate, $endDate, $type) {
            if ($type == 0) {
                $moment_comments->whereBetween("moment_user_comments.created_at", getToday());
            } elseif ($type == 1) {
                $moment_comments->whereBetween("moment_user_comments.created_at", [$startDate, $endDate]);
            }
        }, 'moment_likes' => function ($moment_likes) use ($startDate, $endDate, $type) {
            if ($type == 0) {
                $moment_likes->whereBetween("moment_user_likes.created_at", getToday());
            } elseif ($type == 1) {
                $moment_likes->whereBetween("moment_user_likes.created_at", [$startDate, $endDate]);
            }
        }])->find($userId);

        $key = ($reals) ? 'reals' : 'reel';
        $key2 = ($reals) ? 'moments' : 'moment';
        $key3 = ($reals) ? 'like' : 'likes';
        $key4 = ($reals) ? 'comment' : 'comments';

        $data = [
            $key2 => [
                'upload' => $user->moments_count ?? 0,
                $key3 => $user->moment_likes_count ?? 0,
                $key4 => $user->moment_comments_count ?? 0,
            ],
            $key => [
                'upload' => $user->reals_count ?? 0,
                $key3 => $user->real_likes_count ?? 0,
                $key4 => $user->real_comments_count ?? 0,
            ],

        ];
        return $data;
    }


    public static function UserEarnedInvitation($userId, $amount, $chargeId = 0)
    {
        if (self::isStopInvitationValid()) {
            return;
        }
        $invitation = self::getInvitation($userId);
        if (!$invitation || !self::isInvitationValid($invitation)) {
            return;
        }

        $parent = User::find($invitation->user_id);
        if (!$parent) {
            return;
        }

        if (!$parent->userSetting->show_invite_code) {
            return;
        }

        $percentage = self::getPercentage($parent);
        if ($percentage === null) {
            return;
        }

        self::applyEarnings($parent, $invitation, $amount, $percentage, $chargeId);
    }

    private static function getInvitation($userId)
    {
        // dd($userId);
        return UserCodeInvitation::where("invited_id", $userId)->first();
    }
    // private static function isStopInvitationValid()
    // {
    //     return settings()->get('stop_invite_code');
    // }

    private static function isStopInvitationValid()
    {

        return Common::stopSwitch('stop_invite_code') ? 1 : 0;
        // return getSettingCash('invite_code') ?? 0;
    }




    private static function isInvitationValid($invitation): bool
    {
        $days = (int) (Common::getConfig('invitation_code_date') ?? 0);
        // 0 (or any non-positive value) = no expiry at all: the inviter keeps
        // earning the commission forever. Skip the date window entirely.
        if ($days <= 0) {
            return true;
        }
        $invitationDate = Carbon::parse($invitation->created_at)->format("Y-m-d");
        $validUntil = Carbon::parse($invitationDate)
            ->addDays($days)
            ->format('Y-m-d');
        return date("Y-m-d") < $validUntil;
    }

    private static function getPercentage($parent): ?float
    {
        $configEarn = Config::where("name", "earn_from_invitation")->first();

        return $configEarn?->value ??  false;
    }

    private static function applyEarnings($parent, $invitation, $amount, $percentage, $chargeId): void
    {
        $parentWin = ($amount * $percentage) / 100;

        if ($parentWin <= 0) {
            return;
        }

        $chargeId = $chargeId ?: null;

        DB::transaction(function () use ($parent, $invitation, $amount, $percentage, $parentWin, $chargeId) {
            // Idempotency: a single charge can only ever produce one commission row.
            // A retried/duplicated charge with the same charge_id is a no-op.
            if ($chargeId !== null) {
                $exists = UserEarnInvitation::where('charge_id', $chargeId)
                    ->where('source_type', 'charge_percentage')
                    ->lockForUpdate()
                    ->exists();
                if ($exists) {
                    return;
                }
            }

            // Ledger-only: accrue the commission as an UNCLAIMED earning. The coins
            // are NOT credited to the inviter's balance here — they accumulate and are
            // moved to the wallet only when the inviter manually extracts them.
            $invitation->invited_charge += $amount;
            $invitation->user_percentage += $parentWin;
            $invitation->save();

            InvitationWalletHelper::updateInvitationWallet($parentWin);

            InvitationEarningHelper::addEarning(
                parentId: $parent->id,
                userId: $invitation->invited_id,
                sourceType: 'charge_percentage',
                amount: $parentWin,
                userCharge: $amount,
                parentPercentage: $percentage,
                chargeId: $chargeId,
            );
        });

        CustomNotification::UserEarnedInvitation($parent, $parentWin);
    }


    public static function UserLuckyGift($isWin, $userId, Gift $gift, $value, $number, $totalNumWin, $totalUserWin)
    {
        $data = [
            'user_id' => $userId,
            'gift_id' => @$gift->id,
            'type' => $isWin ? 1 : 0,
            'value' => $value,
            'number' => $number,
            'total_num_win' => $totalNumWin,
            'total_win' => $totalUserWin,
            'gift_price' => @$gift->price,
            'app_profit_coins' => @$gift->price,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $maxRetries = 5;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                DB::table('user_lucky_gifts')->insert($data);
                return;
            } catch (\Exception $e) {
                $retryCount++;
                
                if (strpos($e->getMessage(), '1205') !== false && $retryCount < $maxRetries) {
                    usleep(pow(2, $retryCount - 1) * 100 * 1000);
                    continue;
                }
                
                throw $e;
            }
        }
    }

    public function userMoreStatistics(User $user)
    {

        $userRacksPrice =  $user->userPacks->whereIn('type', [4, 5, 6])->where('sender_id', 'null')->sum('price');
        // @dump($userRacksPrice);
        $senderPacksPrice =  $user->sendPacks->whereIn('type', [4, 5, 6])->sum('price');
        $totalPacksPrice = ($userRacksPrice ?? 0) + ($senderPacksPrice ?? 0);
        $totalVipPrice = $user->userVips()->sum('price');

        // earned
        $user_statistic['earned']['charges'] = $user->charges?->where("amount", ">=", 0)->sum("amount");
        $user_statistic['earned']['coin_logs'] = $user->coinLogs?->sum("obtained_coins");
        $user_statistic['earned']['exchange_logs'] = $user->exchangeLogs?->sum("value");
        $user_statistic['earned']['coin_games'] = $user->coinGameUser?->where("type", 1)->sum("coins");
        $user_statistic['earned']['lucky_gifts'] = $user->luckyGifts?->where('type', 1)->where("value", ">=", 0)->sum("value");
        // losed
        $user_statistic['losed']['charges'] = $user->charges?->where("amount", "<", 0)->sum("amount");
        $user_statistic['losed']['gift_logs'] = $user->giftLogsSender?->sum("giftPrice");
        $user_statistic['losed']['packs'] = $totalPacksPrice ?? 0;
        $user_statistic['losed']['coin_games'] = $user->coinGameUser?->where("type", 0)->sum("coins");
        $user_statistic['losed']['request_background_images'] = $user->requestBackgroundImages?->where("status", '!=', 2)->sum("price");
        $user_statistic['losed']['vip_price'] = $totalVipPrice ?? 0;
        // total
        $user_statistic['total']['earned'] = array_sum($user_statistic['earned']);
        $user_statistic['total']['losed'] = array_sum($user_statistic['losed']);
        $user_statistic['total']['minus-between'] = $user_statistic['total']['earned'] - ($user_statistic['total']['losed'] * -1);

        return $user_statistic;
    }

    public static function userVip(User $user, $receiveType = 'vip-check')
    {
        $vip = OVip::query()->first();
        $user_vip_check = UserVip::query()->where('user_id', $user->id)
            ->where('level', '>=', $vip->level)->first();
        $expire = $vip->expire;


        if (!$user_vip_check) {
            VipCommon::createUserVip($vip, $user, $vip->expire, null, '', 1, 0, 0, $receiveType);
        }
    }

    public static function arabicToEnglishNumbers($string)
    {
        $newNumbers = range(0, 9);
        // الأرقام العربية
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($arabicNumbers, $newNumbers, $string);
    }

    // public static function englishToArabicNumbers($string) {
    //     // الأرقام الإنجليزية
    //     $englishNumbers = range(0, 9);
    //     // الأرقام العربية
    //     $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    //     return str_replace($englishNumbers, $arabicNumbers, $string);
    // }


    public static function englishToArabicNumbers($string)
    {
        $numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $createdAt = Carbon::parse($string)->locale('ar_SA')->isoFormat('h:mm:ss A');
        $createdAt = str_replace($numbers, $arabicNumbers, $createdAt);

        return $createdAt;
    }

    public static function englishToArabicNumbersDate($string)
    {
        $numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $createdAt = Carbon::parse($string)->locale('ar_SA')->format('Y-m-d');
        $createdAt = str_replace($numbers, $arabicNumbers, $createdAt);

        return $createdAt;
    }

    public static function arabicToEnglishNumbersDate($string)
    {
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        // تحويل التاريخ إلى صيغة يمكن فهمها باستخدام Carbon
        $createdAt = Carbon::parse($string)->format('Y-m-d');

        // استبدال الأرقام العربية بالأرقام الإنجليزية
        $createdAt = str_replace($arabicNumbers, $englishNumbers, $createdAt);

        return $createdAt;
    }

    public static function convertArabicNumbers($string)
    {
        $newNumbers = range(0, 9);
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($arabicNumbers, $newNumbers, $string);
    }

    // public static function addVipToUser(User $user, OVip $vip, $expire, $sender = null, $receiveType, $isUsed = null)
    public static function addVipToUser(User $user, OVip $vip, $expire, $sender = null, $receiveType = '', $isUsed = null, $sendNotification = 1, $vip_gift_message = null, $vip_img = null)
    {
        DB::beginTransaction();
        VipCommon::createUserVip($vip, $user, $expire, null, '', 1, 0, 0, $receiveType, $isUsed, $sendNotification, $vip_gift_message, $vip_img);
        DB::commit();
        // Common::sendOfficialMessage($user->id, __('تهانينا'), __('لقد حصلت على مستوى VIP جديد كهدية'));
        // $tokens_notfacion[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        // $title = config('app.name_ar');
        // $body = __('لقد حصلت على مستوى VIP جديد كهدية') . $user->name;
        // Common::send_firebase_notification($tokens_notfacion, $title, $body);
    }

    public static function removeVipFromUser(User $user, $id, $receiveType)
    {
        VipCommon::removeVipFromUser($user, $id, $receiveType);
    }

    public static function removeEventsWareFromUser(User $user, $id, $receiveType)
    {
        Pack::where('receive_type', $receiveType)
            ->where('user_id', $user->id)
            ->where('target_id',  $id)->delete();
    }

    public static function removeBadgeFromUser(User $user, $id, $receiveType)
    {

        UserBadge::where('receive_type', $receiveType)
            ->where('user_id', $user->id)
            ->where('badge_id',  $id)->delete();
    }

    public static function removeBadgeFromUserByReceiverType(User $user, $id = null, $receiveType)
    {
        UserBadge::where('receive_type', $receiveType)->where('user_id', $user->id)->delete();
    }


    public static function addWareToUser(User $user, Ware $ware, $expire, $sender = null, $receiveType = null, $sendNotification = 1)
    {
        $receiveType = $receiveType ?? 'not-sending';


        $pack = Pack::query()->where('user_id', $user->id)->where('target_id', $ware->id)->first();

        $title = __('congratulations');
        $body = $user->name . ':' . __('You have received a gift: :ware', ['ware' => $ware->name]);

        // if ($pack) {
        //     if ($pack->expire == 0) return '';
        //     if ($pack->expire > now()->timestamp) {
        //         if ($ware->expire != 0) {
        //             DB::beginTransaction();
        //             try {
        //                 $pack->expire += $expir ? ($expir * 86400) : ($ware->expire * 86400);
        //                 $pack->save();
        //                 DB::commit();

        //                 Common::sendOfficialMessage($user->id, $title, $body);
        //                 (new UserCounterServices)->eventUser($user, 'official-messages');

        //                 $tokens_notfacion[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        //                 Common::send_firebase_notification($tokens_notfacion, $title, $body);

        //             } catch (\Exception $exception) {
        //                 DB::rollBack();
        //             }
        //         }
        //     } else {
        //         $pack->delete();
        //     }
        // }
        DB::beginTransaction();
        try {
            $arr['user_id'] = $user->id;
            $arr['type'] = $ware->type;
            $arr['get_type'] = $ware->get_type;
            $arr['target_id'] = $ware->id;
            $arr['num'] = 1; //$qty;
            //            $arr['expire'] = $expir ? time() + ($expir * 86400) : ($ware->expire ? time() + ($ware->expire * 86400) : 0);
            $arr['is_read'] = 1;
            $arr['days'] = $expire;
            $arr['receive_type']      = $receiveType;

            $pack = Pack::query()->create($arr);
            $pack->senderable()->associate($sender);
            $pack->save();
            DB::commit();
            if ($sendNotification) {
                Common::sendOfficialMessage($user->id, $title, $body);
                (new UserCounterServices)->eventUser($user, 'official-messages');

                $tokens_notfacion[] = DB::table('users')->where('id', $user->id)->value('notification_id');
                Common::send_firebase_notification($tokens_notfacion, $title, $body);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
        }
    }


    public static function addEvintsWareToUser(User $user, Ware $ware, $expir, $sender = null, $receiveType = null, $isUsed = null, $feature = null, $message = null)
    {
        $title = __('congratulations');
        $body = $user->name . ':' . __('You have received a gift: :ware', [
            'ware' => $ware->name
        ]);
        $data = []; // Initialize $data array

        if ($feature) {
            $body = $user->name . ':' . __('wareGiftNotification', [
                'wareName' => $ware->name,
                'type'     => $feature->name
            ]);
        }

        if ($message) {
            $body = $message;
            $img = $ware->show_img;
            $data['image'] = StorageHelper::url($img);
        }

        DB::beginTransaction();
        try {
            $arr['user_id']   = $user->id;
            $arr['type']      = $ware->type;
            $arr['get_type']  = $ware->get_type;
            $arr['target_id'] = $ware->id;
            $arr['num']       = 1;
            $arr['is_read']   = 1;
            $arr['use_num']   = 0;
            $arr['using']     = 0;
            $arr['days']      = $expir;
            $arr['expire']   =  null;

            $arr['receive_type']      = $receiveType;


            $pack = Pack::query()->create($arr);

            if ($sender) {
                $pack->senderable()->associate($sender);
                $pack->save();
            }

            DB::commit();

            Common::sendOfficialMessage($user->id, $title, $body);
            (new UserCounterServices)->eventUser($user, 'official-messages');

            $tokens_notfacion[] = DB::table('users')
                ->where('id', $user->id)
                ->value('notification_id');

            Common::send_firebase_notification($tokens_notfacion, $title, $body, '', $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::error("حدث خطأ أثناء منح مكافأة الإنجاز: " . $exception->getMessage(), [
                'user_id' => $user->id,
                'reward_id' => $reward->id ?? 'N/A',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * @throws \Throwable
     */
    public static function assignRoomBoomWare(User $user, Ware $ware, $expire, $sender = null): void
    {
        DB::beginTransaction();
        try {
            $arr['user_id']   = $user->id;
            $arr['type']      = $ware->type;
            $arr['get_type']  = $ware->get_type;
            $arr['target_id'] = $ware->id;
            $arr['num']       = 1;
            $arr['is_read']   = 1;
            $arr['days']      = $expire;
            $arr['receive_type']      = 'room-boom';


            $pack = Pack::query()->create($arr);

            if ($sender) {
                $pack->senderable()->associate($sender);
                $pack->save();
            }

            DB::commit();

            (new UserCounterServices)->eventUser($user, 'official-messages');
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public static function addChargeLevel($userId, $amount)
    {
        $user = User::where("id", $userId)->first();
        $user->total_charge_coins += $amount;
        $user->save();

        $chargeUserExp = $user->total_charge_coins + $user->sub_charger_level;
        $level = Vip::where("exp", "<=",  $chargeUserExp)->where('type', 5)->orderByDesc("exp")->first();
        if ($level) {
            $user->charge_level = $level->level;
        }
        $user->save();
    }

    public static function updateUserTotalCoins($user_id, $total_coins)
    {
        $userTarget =  UserTargetCoin::updateOrCreate(
            ['user_id' => $user_id],
            ['total_coins' => \DB::raw("total_coins + {$total_coins}")]
        )->first();

        $coins = CoinTarget::with('gifts')
            ->where('coins', '<=', $userTarget->total_coins)
            ->orderBy('coins', 'desc')->first();

        $user = User::find($user_id);

        if ($coins) {
            $user_coin_target = UserCoinTarget::where([
                'user_id' => $user->id,
                'coin_target_id' => $coins->id,
            ])->first();

            if ($user_coin_target) {
                return true;
            }

            UserCoinTarget::create([
                'user_id' => $user->id,
                'coin_target_id' => $coins->id,
            ]);

            foreach ($coins->gifts as $reward) {
                switch ($reward->type) {
                    case 'coins':
                        CoinsTarget::assignCoinsUser($reward->item_id, $user);
                        break;
                    case 'vip':
                        CoinsTarget::assignVipUser($reward->item_id, $reward->expire, $user);
                        break;
                    case 'ware':
                        $ware = Ware::find($reward->item_id);
                        CoinsTarget::assignWareUser($ware, $reward, $user);
                        break;
                    case 'achievement':
                        CoinsTarget::assignAchievementUser($reward->item_id, $reward->expire, $user);
                        break;
                }
            }
        }
    }
}
