<?php

namespace App\Helpers;

use App\Models\Gift;
use App\Models\User;
use App\Models\Agency;
use App\Models\Family;
use Illuminate\Support\Facades\Log;
use Modules\Vip\Entities\Vip;
use Modules\Reals\Entities\Real;
use Illuminate\Support\Facades\DB;
use App\Models\UserOfficialMessage;
use Modules\Moment\Entities\Moment;
use App\Models\OfficialMessageAdmin;
use App\Models\ShippingAgency;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Modules\Public\Http\Services\UserCounterServices;

class CustomNotification
{
    public static function appName($lang)
    {
        $locale = $lang ?? app()->getLocale();
        return $locale == 'ar' ? Cache::get('app_title_ar') : Cache::get('app_title_en');
    }



    public static function addUserLevel($user)
    {
        Common::sendOfficialMessage($user->id, __('congratulation'), __('You have received a new VIP level as a gift'));
        $tokens_notfacion[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $title = self::appName($user->lan);
        $body = __('You have received a new VIP level as a gift') . $user->name;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $title, $body);
    }


    public function senderLevel(int $userId)
    {
        $user = User::withoutAppends()->where('id', $userId)->first();
        // The scope flips a GLOBAL static that outlives the request under
        // Octane: left on, every later request on this worker serves EMPTY
        // avatars/genders app-wide (black-logo images epidemic, 2026-06-11).
        User::$withoutAppends = false;
        if (!$user) {
            return 0;
        }
        $lang = $user->lan ?? 'en';
        $tokens_notfacion = DB::table('users')->where('id', $userId)->value('notification_id');
        $body = __('api.sender_level', ['user' => $user->name, 'level' => $user->total_sender_level], $lang);

        $title = __('api.senderLevelUpgrade', [], $lang);

        $image = Vip::where('level', $user->total_sender_level)->where('type', 2)->first()?->img;
        $data = getImagePath($image);
        $icon = $data;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data);
        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body, image: $data);
        (new UserCounterServices)->eventUser($user, 'official-messages', 1);
    }

    public function RoomLevel(int $userId, $level, $reward)
    {

        $user = User::withoutAppends()->where('id', $userId)->with('ownerAudioRoom.roomLevel')->first();
        User::$withoutAppends = false;
        if (!$user) {
            return 0;
        }

        $lang = $user->lan ?? 'en';
        $tokens_notfacion = DB::table('users')->where('id', $userId)->value('notification_id');
        $body = __('api.room_level', ['level' => $level, 'reward' => $reward], $lang);

        $title = __('congratulation', [], $lang);

        $image = $user->ownerAudioRoom->roomLevel?->img;
        $data = getImagePath($image);
        $icon = $data;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data);
        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body, image: $data);
        (new UserCounterServices)->eventUser($user, 'official-messages', 1);
    }
    public function receiverLevel(int $userId)
    {
        $user = User::withoutAppends()->where('id', $userId)->first();
        User::$withoutAppends = false;
        if (!$user) {
            return 0;
        }
        $lang = $user->lan ?? 'en';
        $tokens_notfacion = DB::table('users')->where('id', $userId)->value('notification_id');
        $body = __('api.receiver_level', ['level' => $user->total_received_level], $lang);
        $title = __('api.receiverLevelUpgrade', [], $lang);
        $image = Vip::where('level', $user->total_received_level)->where('type', 1)->first()?->img;
        $data = getImagePath($image);
        $icon = $data;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data);
        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body, image: $image);
        (new UserCounterServices)->eventUser($user, 'official-messages', 1);
    }

    public function BackgroudRequest(User $user, $type = 0)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';
        $body = $type == 0 ?
            __('api.background_accept', ['name' => $user->name], $lang)
            : __('api.background_refuse', ['name' => $user->name], $lang);
        $title = __('api.roomBackground', [], $lang);

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, messageType: 'backgroud-request');

        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages', 1);
    }

    public function target(int $userId)
    {

        $user = User::withoutAppends()->where('id', $userId)->first();
        if (!$user) {
            return 0;
        }
        User::$withoutAppends = false;
        $lang = $user->lan ?? 'en';
        $tokens_notfacion = DB::table('users')->where('id', $userId)->value('notification_id');
        $salary = $user->salary;
        $agencyName = $user->agency?->name;
        $body = __('api.target', ['user' => $user->name, 'salary' => $salary, 'agency' => $agencyName], $lang);
        $title = __('api.newTarget', [], $lang);
        $data['user_id'] = $user?->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, data: $data, messageType: 'achieve-target-monthly');
        Common::sendOfficialMessage($user->id, title: $body, content: $title, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages', 1);
    }




    public function momentComment(Moment $moment, User $userSender)
    {
        $momentUser = $moment->user;
        $lang = $momentUser->lan ?? 'en';
        $body = __('api.comment_moment', ['name' => $userSender->name], $lang);
        $title = __('api.momentComment', [], $lang);
        $data['moment_id'] = @$moment->id;
        $this->sendNotificationWithImage($momentUser, $body, $body, $userSender, $title, 'moment-comment', $data);
    }

    public function likeReal(Real $real, User $userLike)
    {
        $reelUser = $real->user;
        $lang = $reelUser->lan ?? 'en';
        $body = __('api.like_your_real', ['name' => $userLike->name], $lang);
        $title = __('api.likeReal', [], $lang);
        $data['real_id'] = @$real->id;
        $this->sendNotificationWithImage($reelUser, $body, $body, $userLike, $title, 'like-real', $data);
    }

    public function CommentReal(Real $real, User $user)
    {
        $reelUser = $real->user;
        $lang = $reelUser->lan ?? 'en';
        $body = __('api.comment_real', ['name' => $user->name], $lang);
        $title = __('api.realComment', [], $lang);
        $data['real_id'] = @$real->id;
        $this->sendNotificationWithImage($reelUser, $body, $body, $user, $title, 'real-comment', $data);
    }

    public function likeMoment(Moment $moment, User $user)
    {
        $momentUser = $moment->user;
        $lang = $momentUser->lan ?? 'en';
        $body = __('api.like_your_moment', ['name' => $user->name], 'ar');
        $title = __('api.likeMoment', [], $lang);
        $data['moment_id'] = @$moment->id;
        $this->sendNotificationWithImage($momentUser, $body, $body, $user, $title, 'like-moment', $data);
    }

    public function acceptAgency(Agency $agency, User $user)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user->lan ?? 'en';
        $body = __('api.accept_agency', ['name' => $agency->name], $lang);

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body);


        Common::sendOfficialMessage($user->id, title: $body, content: $agency->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function rejectAgency(Agency $agency, User $user)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user->lan ?? 'en';
        $body = __('api.reject_agency', ['name' => $agency->name], $lang);

        $data['image'] = getDriverUrl() . '/' . $user->profile->avatar;
        $data['user_id'] = $agency->app_owner_id;
        $icon = $data['image'];

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'agency-reject-request');

        Common::sendOfficialMessage($user->id, title: $body, content: $agency->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function agencyJoinRequest(Agency $agency, User $user)
    {
        $tokens_notification[] = DB::table('users')->where('id', $agency->app_owner_id)->value('notification_id');
        $lang = $user->lan ?? 'en';
        $body = __('api.agencyJoinRequest', ['name' => $user->name, 'agencyName' => $agency->name], $lang);
        $data['image'] = getDriverUrl() . '/' . $user->profile->avatar;
        $data['user_id'] = $agency->app_owner_id;
        $icon = $data['image'];

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'agency-join-request');
        Common::sendOfficialMessage($agency->app_owner_id, title: $body, content: $agency->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function agencyAddAdmin($agencyId, User $user)
    {
        $agency = Agency::find($agencyId);
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user->lan ?? 'en';
        $body = __('api.add_admin_agency', ['name' => $agency->name], $lang);

        $data['image'] = $agency->img;
        $data['agency_id'] = $agency->id;
        $icon = $agency->img;

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, $data, messageType: 'agency-add-admin');
        Common::sendOfficialMessage($user->id, image: $agency->img, title: $body, content: $agency->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function agencyRemoveAdmin($agencyId, User $user)
    {
        $agency = Agency::find($agencyId);
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user->lan ?? 'en';
        $body = __('api.remove_admin_agency', ['name' => $agency->name], $lang);

        $data['image'] = $agency->img;
        $data['agency_id'] = $agency->id;
        $icon = $agency->img;

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, $data, messageType: 'agency-remove-admin');

        Common::sendOfficialMessage($user->id, image: $agency->img, title: $body, content: $agency->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function visitProfile(User $user, User $visitor): void
    {
        if (!$user) {
            return;
        }
        $lang = $user->lan ?? 'en';
        $tokensNotification = $user->notification_id;
        $body = __('api.visited_profile', ['name' => $visitor->name], $lang);


        $avatarPath = $visitor->profile->avatar ?? 'default-avatar.png';
        $data = [
            'image' => getDriverUrl() . '/' . $avatarPath,
            'user_id' => $user->id,
        ];

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokensNotification,
                $this->appName($user->lan),
                $body,
                icon: $data['image'],
                data: $data,
                messageType: 'visit-profile'
            );
        }

        Common::sendOfficialMessage(
            $user->id,
            image: $avatarPath,
            title: $body,
            content: $visitor->name,
            titleAr: $body,
            fromUserId: $visitor->id
        );

        app(UserCounterServices::class)->eventUser($user, 'official-messages');
    }

    public function follow(User $receiver, User $user)
    {
        $tokens_notfacion = DB::table('users')->where('id', $receiver->id)->value('notification_id');
        $lang = $receiver->lan ?? 'en';
        $body = __('api.followed_you', ['name' => $user->name], $lang);
        $data['image'] = getImagePath($user->profile->avatar);
        $icon = $data['image'];
        if (!$receiver->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'follow');
        Common::sendOfficialMessage($receiver->id, image: $user->profile->avatar, title: $body, content: $user->name, titleAr: $body, fromUserId: $user->id);
        (new UserCounterServices)->eventUser($receiver, 'official-messages');
    }

    public function followBack(User $receiver, User $user)
    {
        $tokens_notfacion = DB::table('users')->where('id', $receiver->id)->value('notification_id');
        $lang = $receiver->lan ?? 'en';
        $body = __('api.follow_back', ['name' => $user->name], $lang);
        $data['image'] = getImagePath($user->profile->avatar);
        $icon = $data['image'];

        if (!$receiver->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'followBack');
        Common::sendOfficialMessage($receiver->id, image: $user->profile->avatar, title: $body, content: $user->name, titleAr: $body, fromUserId: $user->id);
        (new UserCounterServices)->eventUser($receiver, 'official-messages');
    }

    public function removeFamilyUser(Family $family, User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';
        $body = __('api.remove_from_family', ['name' => $family->name], $lang);
        $icon = $family->img;

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon);
        Common::sendOfficialMessage(user_id: $user->id, content: $body, title: $family->name, titleAr: $body, image: $family->img);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function adminFamily(Family $family, User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';
        $body = __('api.admin_family', ['name' => $family->name, 'appName' => $this->appName($lang)], $lang);

        $icon = $family->img;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon);
        Common::sendOfficialMessage(user_id: $user->id, content: $body, title: $family->name, titleAr: $body, image: $family->img);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function officialMsg(OfficialMessageAdmin $msg, $usersId)
    {
        $user_id = $msg->user_id;

        if ($usersId) {

            $usersChunk = User::where('notification_id', '!=', NULL)->whereIn('id', $usersId)
                ->select(['id', 'notification_id', 'lan'])->get()->unique('notification_id')->chunk(50);
            $body = $msg->content;
            $title = $msg->title;
            $data['image'] = null;
            $icon = null;
            if ($msg->img) {
                $data['image'] = getImagePath($msg->img);
                $icon = $data['image'];
            }


            foreach ($usersChunk as $chunk) {
                // Extract notification IDs
                $notificationIds = $chunk->pluck('notification_id')->toArray();

                // Send Firebase notification
                Common::send_firebase_notification(
                    $notificationIds,
                    title: $title,
                    body: $body,
                    icon: $icon,
                    data: $data,
                    messageType: 'system-msg'
                );

                // Save user_official_messages records
                foreach ($chunk as $user) {
                    UserOfficialMessage::create([
                        'official_message_id' => $msg->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
            (new UserCounterServices)->eventUsers('system-messages');
            // $users->chunk(200, function ($chunkedUsers) use ($usersTokenAr, $body, $title) {
            //     foreach ($chunkedUsers as $user) {
            //         Common::send_firebase_notification($usersTokenAr, $title, $body);
            //     }
            // });
        }

        if ($user_id != 0) {
            // $user = $msg->user;
            $tokens_notfacion = User::where('id', $user_id)->value('notification_id');
            $isLogout = User::where('id', $user_id)->value('is_logout');
            $body = $msg->content;
            $title = $msg->title;
            $data['image'] = null;
            $icon = null;
            if ($msg->img) {
                $data['image'] = getImagePath($msg->img);
                $icon = $data['image'];
            }

            if (!$isLogout)
                Common::send_firebase_notification($tokens_notfacion, $title, $body, icon: $icon, data: $data, messageType: 'system-msg');
        }
    }


    public function acceptRequestAgency(User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';

        $body = __("api.acceptYourAgency", [], $lang);


        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, messageType: 'accept-agency');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function remainingDiamonds(User $user, $type, $month, $amount)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';

        $body = $type == 'diamonds' ? __("api.remainingDiamonds", ['diamonds' => $amount, 'month' => $month], $lang) : __("api.remainingDiamondCoins", ['coins' => $amount, 'month' => $month], $lang);

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, messageType: 'remaining-diamonds');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function refuseRequestAgency(User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user->lan ?? 'en';
        $body = __("api.rejectYourAgency", [], $lang);

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, messageType: 'accept-agency');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function sendMomentGift(User $senderUser, Gift $gift, $receivedUser, $momentId)
    {
        $tokens_notfacion = DB::table('users')->where('id', $receivedUser->id)->value('notification_id');
        $lang = $receivedUser->lan ?? 'en';
        $body = __('api.user_send_gift_moment', ['name' => $senderUser->name, 'gift' => $gift->name], $lang);

        $data['image'] = getImagePath($gift->img);
        $data['moment_id'] = $momentId;
        $icon = $data['image'];

        if (!$receivedUser->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($receivedUser->lan), $body, icon: $icon, data: $data, messageType: 'send-moment-gift');
        Common::sendOfficialMessage($receivedUser->id, image: $senderUser->profile->avatar, title: $body, content: $senderUser->name, titleAr: $body, fromUserId: $senderUser->id);
        (new UserCounterServices)->eventUser($receivedUser, 'official-messages');
    }

    public function mallSend($user, $toUser, $type, $bubbleImage)
    {
        $tokens_notification = $toUser?->notification_id;
        $lang = $toUser?->lan ?? 'en';
        $wareTranslations = [
            '(Bubble)' => [
                'en' => '(Bubble)',
                'ar' => '(بابل)',
                'hi' => '(बाबेल)',
                'tr' => '(Babil)',
            ],
            '(frame)' => [
                'en' => '(frame)',
                'ar' => '(إطار)',
                'hi' => '(फ्रेम)',
                'tr' => '(çerçeve)',
            ],
            '(entering effect)' => [
                'en' => '(entering effect)',
                'ar' => '(تأثير الدخول)',
                'hi' => '(एंट्री इफेक्ट)',
                'tr' => '(giriş efekti)',
            ],
            '(Bubble ,frame or entering effect)' => [
                'en' => '(Bubble, frame or entering effect)',
                'ar' => '(بابل، إطار أو تأثير الدخول)',
                'hi' => '(बाबेल, फ्रेम या एंट्री इफेक्ट)',
                'tr' => '(Bubble, çerçeve veya giriş efekti)',
            ],
        ];

        $selectedWare = match ($type) {
            4 => '(Bubble)',
            5 => '(frame)',
            11 => '(entering effect)',
            default => '(Bubble ,frame or entering effect)',
        };

        // Get the translated ware
        $wareName = $wareTranslations[$selectedWare][$lang] ?? $selectedWare;

        $body = __('api.gift_aristocracy', [
            'name' => $user->name,
            'ware' => $wareName
        ], $lang);


        $data['image'] = getImagePath($bubbleImage);
        $icon = $data['image'];

        if (!$toUser->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'mall-send');
        Common::sendOfficialMessage($toUser->id, $body, '', titleAr: $body, fromUserId: $user->id);
        (new UserCounterServices)->eventUser($toUser, 'official-messages');
        (new UserCounterServices)->eventUser($toUser, 'mall');
    }

    public function acceptUserFamily(Family $family, ?User $user)
    {
        if (!$user)
            return;
        $tokens_notification = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.accept_family', ['name' => $family->name], $lang);

        $icon = $family->img;
        $data['family_id'] = $family->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, data: $data, messageType: 'accept-user-family');

        Common::sendOfficialMessage($user->id, type: 1, content: $body, image: $family->img, title: $family->name);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }


    public function requestJoinFamily(Family $family, User $user)
    {
        $tokens_notification = DB::table('users')->where('id', $family->owner?->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.send_Family', ['name' => $user->name], $lang);
        $icon = $user->profile->avatar;
        $data['family_id'] = $family->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, data: $data, messageType: 'request-join-family');
        Common::sendOfficialMessage($family->owner?->id, image: $user->profile->avatar, title: $body, content: $user->name, titleAr: $body, fromUserId: $user->id);
        (new UserCounterServices)->eventUser($family->owner, 'official-messages');
    }

    public function family(Family $family, User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.accept_family', ['name' => $family->name], $lang);

        $icon = $family->img;
        $data['family_id'] = $family->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, data: $data, messageType: 'family');
        Common::sendOfficialMessage($user->id, content: $family->name, title: $body, titleAr: $body, image: $family->img);
        (new UserCounterServices)->eventUser($family->owner, 'official-messages');
    }

    public function acceptAgencyApp(Agency $agencyName, User $user)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.accept_agency', ['name' => $agencyName->name], $lang);

        $data['image'] = $agencyName->img;
        $data['agency_id'] = $agencyName->id;
        $icon = $agencyName->img;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, $icon, $data, messageType: 'accept-agency-app');
        Common::sendOfficialMessage($user->id, image: $agencyName->img, title: $body, content: $agencyName->name, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }


    public function chargeAction(User $user, $request, $admin = "Admin", $agency = null, $coins = 0)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $name = $agency ? $agency->name : $user->name;

        // Use actual coins if provided, otherwise fallback to request amount
        $displayCoins = $coins ?: $request->amount;

        // Always use 'coins' as unit
        $unit = 'coins';

        $body = __('api.got_coin', [
            'coins' => $displayCoins,
            'name' => $name,
            'admin' => $admin,
            'unit' => $unit
        ], $lang);
        $data['coins'] = $displayCoins;
        $data['unit'] = $unit;

        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'charge-action-notifaction');
        Common::sendOfficialMessage($user->id, title: $body, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function UserEarnedInvitation(User $user, $amount)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.got_earned_coin', ['coins' => $amount, 'name' => $user->name], $lang);

        $data['coins'] = $amount;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'charge-action-notifaction');
        Common::sendOfficialMessage($user->id, title: $body, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function rankingReward(User $user, $level)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.rankingRewardLevel', ['level' => $level], $lang);
        $data['user_id'] = $user?->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'ranking-rewards');
        Common::sendOfficialMessage($user->id, title: $body, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function codeInvitationUses(User $user, User $invitationUser, float $amount = 0)
    {
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.code_invitation_uses', ['name' => $user->name, 'from' => @$invitationUser->name, 'coins' => $amount], $lang);

        $data = [
            'coins' => $amount,
        ];
        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'charge-action-notifaction'
            );
        }
        Common::sendOfficialMessage(
            $user->id,
            title: $body,
            titleAr: $body
        );
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }


    public function hostSalary(ShippingAgency $agency, User $invitationUser, float $amount = 0)
    {
        $user = $agency->owner;
        if (!$user) {
            return;
        }
        $tokens_notification[] = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.host_salary', ['agency' => $agency->name, 'from' => @$invitationUser->name, 'amount' => $amount], $lang);

        $data = [
            'coins' => $amount,
        ];
        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'host-salary-notification'
            );
        }
        Common::sendOfficialMessage(
            $user->id,
            title: $body,
            titleAr: $body
        );
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }



    public function banUser(User $user, $duration)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.ban_user_id', ['duration' => $duration, 'appName' => $this->appName($lang)], $lang);

        $data['user_id'] = $user?->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'ban-user');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function removeBanUser(User $user)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.removeBan_user_id', ['appName' => $this->appName($lang)], locale: $lang);

        $data['user_id'] = $user?->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'remove-ban-user');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function vips(User $user, $duration, $img)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $vipTranslations = [
            'aristocracy' => [
                'en' => 'VIP',
                'ar' => 'الاستقراطيه',
                'hi' => 'अристोक्रेसी',
                'tr' => 'Asalet',
            ],

        ];
        $vipType = 'aristocracy';
        $vipName = $vipTranslations[$vipType][$lang] ?? $vipType;
        $body = __('api.userVips', ['duration' => $duration, 'vip' => $vipName], $lang);

        $data['image'] = getImagePath($img);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'vips');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function familyLevelUpgrade(int $familyId)
    {
        $family = Family::query()->with(['users' => fn($query) => $query->withoutAppends()->select(['users.id', 'notification_id', 'lan'])])->where('families.id', $familyId)->first();
        if (!$family) {
            return 0;
        }
        $users = $family->users;
        $usersTokenEn = $users->where('lan', '!=', 'ar')->pluck('notification_id');
        $usersTokenAr = $users->where('lan', 'ar')->pluck('notification_id');

        $body_ar = __('api.family_level_up', ['name' => $family->name, 'level' => $family->currentLevel?->level ?? 0], 'ar');
        $body_en = __('api.family_level_up', ['name' => $family->name, 'level' => $family->currentLevel?->level ?? 0], 'en');
        Common::sendOfficialMessage($family->id, $body_en, __('Family level up'), titleAr: $body_ar);
        $data['image'] = getImagePath($family->image);
        $data['family_id'] = $familyId;
        $icon = $data['image'];


        Common::send_firebase_notification($usersTokenEn, config('app.name_en'), $body_en, icon: $icon, data: $data, messageType: 'family-level-upgrade');
        Common::send_firebase_notification($usersTokenAr, config('app.name_ar'), $body_ar, icon: $icon, data: $data, messageType: 'family-level-upgrade');
    }

    public function wareVip(User $user, $duration, $name, $image)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.wareVips', ['duration' => $duration, 'name' => $name], $lang);

        $data['image'] = getImagePath($image);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'ware-vip');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function dedicateBadges(User $user, $duration, $name, $images)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.wareVips', ['duration' => $duration, 'name' => $name], $lang);
        $image = $images->firstWhere('language', $lang)?->image ?? '';
        $data['image'] = getImagePath($image);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'ware-vip');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    /**
     * @param mixed $momentUser
     * @param \Illuminate\Foundation\Application|array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null $body_ar
     * @param \Illuminate\Foundation\Application|array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null $body_en
     * @param User $userSender
     * @param $tokens_notification
     * @return void
     */
    public function sendNotificationWithImage(mixed $momentUser, string $body_ar, string $body_en, User $userSender, string $title, string $messageType, $data): void
    {
        $firebaseBody = ($momentUser?->lan === 'ar') ? $body_ar : $body_en;
        $data['image'] = getDriverUrl() . '/' . $userSender->profile->avatar;
        $icon = $userSender->profile?->avatar;
        $tokens_notification = $momentUser?->notification_id;

        if (!$userSender->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($momentUser?->lan), $firebaseBody, $icon, $data, messageType: $messageType);
        Common::sendOfficialMessage($momentUser?->id, title: $body_en, content: $title, titleAr: $body_ar, fromUserId: $userSender->id);
    }

    public function acceptRequestToGetMony(User $user, $type = 1, $reason = null, $value = 0)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = $type == 1 ?
            __('api.accept_request_sallary', ["value" => $value], $lang)
            : __('api.denied_request_sallary', ["value" => $value, 'reason' => $reason], $lang);


        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, icon: '', data: [], messageType: 'request-message');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function roomAchievementTarget(User $user, $coins, $roomName)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';

        $body = __('api.roomTarget', ['coins' => $coins, 'room' => $roomName], $lang);

        $data['user_id'] = $user?->id;
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notification, $this->appName($user->lan), $body, data: $data, messageType: 'room-achievement-target');
        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }
    public function makeCp(User $user, User $sender, $type)
    {
        $tokens_notfacion = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.maleCp', ['type' => $type, 'name' => $sender->name], $lang);

        $content = 'cp';
        $data['image'] = getImagePath(@$sender->profile->avatar);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'text');
        Common::sendOfficialMessage($user->id, title: $body, content: $content, titleAr: $body,);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function cpAction(User $user, User $sender, $action, $refund = 0)
    {
        $tokens_notfacion = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        if ($action == 1) {
            $body = __('api.acceptCp', ['name' => $sender->name], $lang);
        } elseif ($refund > 0) {
            $body = __('api.rejectCpRefund', ['name' => $sender->name, 'coins' => number_format($refund)], $lang);
        } else {
            $body = __('api.rejectCp', ['name' => $sender->name], $lang);
        }

        $content = 'cp';
        $data['image'] = getImagePath(@$sender->profile->avatar);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'action-cp');
        Common::sendOfficialMessage($user->id, title: $body, content: $content, titleAr: $body,);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function luckyBox(User $user, $coins, $imageBox)
    {
        $tokens_notfacion = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = __('api.luckBox', ['coins' => $coins], $lang);

        $content = __('api.lucky_box', [], $lang);
        $data['image'] = getImagePath(@$imageBox);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'lucky_box');
        Common::sendOfficialMessage($user->id, title: $body, content: $content, titleAr: $body,);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function closeLuckyBox(User $user, $imageBox, $type)
    {
        $tokens_notfacion = DB::table('users')->where('id', $user->id)->value('notification_id');
        $lang = $user?->lan ?? 'en';
        $body = $type == 0 ? __('api.closeNormalBox', [], $lang) : __('api.closeSuperBox', [], $lang);

        $content = __('api.lucky_box', [], $lang);
        $data['image'] = getImagePath(@$imageBox);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'close_lucky_box');
        Common::sendOfficialMessage($user->id, title: $body, content: $content, titleAr: $body,);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function closedLuckyBosWithReturnCoins(User $user, $coins, $imageBox, $type)
    {
        $lang = $user->lan ?? 'en';
        $body = '';
        $tokens_notfacion = DB::table('users')->where('id', $user->id)->value('notification_id');
        $body = $type == 0 ?
            __('api.closeNormalBoxReturnCoins', ['coins' => $coins], $lang)
            : __('api.closeSuperBoxReturnCoins', ['coins' => $coins], $lang);

        $content = __('api.lucky_box', [], $lang);

        $data['image'] = getImagePath(@$imageBox);
        $icon = $data['image'];
        if (!$user->is_logout)
            Common::send_firebase_notification($tokens_notfacion, $this->appName($user->lan), $body, icon: $icon, data: $data, messageType: 'return_coins_lucky_box');
        Common::sendOfficialMessage($user->id, title: $body, content: $content, titleAr: $body,);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }



    function multiLang(string $key, array $replace = []): array
    {
        $locales = ['en', 'ar'];
        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = Lang::get($key, $replace, $locale);
        }

        return $translations;
    }

    public function charges($user, $title, $body, $replace)
    {
        $currentLang = app()->getLocale();
        $notificationToken = $user->notification_id;

        $translatedTitle = $this->multiLang($title);
        $translatedBody = $this->multiLang($body, $replace);

        Common::sendOfficialMessage($user->id, content: $translatedBody[$currentLang], title: $translatedTitle['en'], titleAr: $translatedTitle['ar']);
        (new UserCounterServices)->eventUser($user, 'official-messages');

        if (!$user->is_logout) {
            dispatch(function () use ($notificationToken, $translatedTitle, $translatedBody, $currentLang) {
                Common::send_firebase_notification($notificationToken, $translatedTitle[$currentLang], $translatedBody[$currentLang]);
            })->afterResponse();
        }
    }



    public static function withdrawalApproved(User $user, $amount)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';

        $body = __('Your withdrawal request of :amount has been approved.', [
            'amount' => $amount
        ], $lang);

        $icon = null;
        $data = [
            'type' => 'withdrawal-approved',
            'amount' => $amount,
        ];

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                self::appName($user->lan),
                $body,
                icon: $icon,
                data: $data,
                messageType: 'withdrawal-approved'
            );
        }

        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public static function withdrawalRejected(User $user, $amount)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';

        $body = __('Your withdrawal request of :amount has been rejected.', [
            'amount' => $amount
        ], $lang);

        $icon = null;
        $data = [
            'type' => 'withdrawal-rejected',
            'amount' => $amount,
        ];

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                self::appName($user->lan),
                $body,
                icon: $icon,
                data: $data,
                messageType: 'withdrawal-rejected'
            );
        }

        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function formRequestApproved(User $user, string $type, array $details = [])
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';

        if ($type === 'bd_form') {
            $body = __('api.agencyOwner', [
                'userName' => $details['username'] ?? '',
            ], $lang);
        } else {
            $body = __("api.acceptYourAgency", [], $lang);
        }

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                messageType: 'form-request-approved'
            );
        }

        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function formRequestRejected(User $user, string $type)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __("api.rejectYourAgency", [], $lang);

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                messageType: 'form-request-rejected'
            );
        }

        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function roomcupReward(User $user, float $amount, string $rewardType = 'owner')
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $body = __('api.roomcup_reward', ['amount' => $amount, 'reward_type' => $rewardType], $lang);

        $data['user_id'] = $user?->id;
        $data['amount'] = $amount;
        $data['reward_type'] = $rewardType;

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'roomcup-reward'
            );
        }

        Common::sendOfficialMessage($user->id, $body, '', titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function chargeKingWinner($user, int $rank, $coinsAmount)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $title = __('api.charge_king_winner_title', [], $lang);
        $body = __('api.charge_king_winner_body', ['rank' => $rank, 'coins' => $coinsAmount], $lang);

        $data['user_id'] = $user?->id;
        $data['rank'] = $rank;
        $data['coins'] = $coinsAmount;

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'charge-king-winner'
            );
        }

        Common::sendOfficialMessage($user->id, $body, $title, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function weeklyStarWinner($user, int $rank)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $title = __('api.weekly_star_winner_title', [], $lang);
        $body = __('api.weekly_star_winner_body', ['rank' => $rank], $lang);

        $data['user_id'] = $user?->id;
        $data['rank'] = $rank;

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'weekly-star-winner'
            );
        }

        Common::sendOfficialMessage($user->id, $body, $title, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }

    public function pkEventWinner($user, int $rank, string $pkType)
    {
        $tokens_notification = $user?->notification_id;
        $lang = $user?->lan ?? 'en';
        $typeKey = str_replace('pk-', '', $pkType);
        $typeLabel = __('api.pk_type_' . $typeKey, [], $lang);
        $title = __('api.pk_event_winner_title', [], $lang);
        $body = __('api.pk_event_winner_body', ['rank' => $rank, 'type' => $typeLabel], $lang);

        $data['user_id'] = $user?->id;
        $data['rank'] = $rank;
        $data['pk_type'] = $pkType;

        if (!$user->is_logout) {
            Common::send_firebase_notification(
                $tokens_notification,
                $this->appName($user->lan),
                $body,
                data: $data,
                messageType: 'pk-event-winner'
            );
        }

        Common::sendOfficialMessage($user->id, $body, $title, titleAr: $body);
        (new UserCounterServices)->eventUser($user, 'official-messages');
    }
}
