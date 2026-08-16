<?php

use App\Admin\Controllers\AgencySettingsController;
use App\Helpers\Common;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\CountriesInPolygonController;
use App\Http\Controllers\Api\FairLuckV5Controller;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\V1\AllGameController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BackgroundController;
use App\Http\Controllers\Api\V1\ChargeController;
use App\Http\Controllers\Api\V1\ChargeLevelController;
use App\Http\Controllers\Api\V1\CharismaLevelController;
use App\Http\Controllers\Api\V1\CharismaResetController;
use App\Http\Controllers\Api\V1\CoinController;
use App\Http\Controllers\Api\V1\CoinReportController;
use App\Http\Controllers\Api\V1\ColorController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\EmojiController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\GiftCategoryController;
use App\Http\Controllers\Api\V1\GiftController;
use App\Http\Controllers\Api\V1\GiftLogController;
use App\Http\Controllers\Api\V1\GooglePaymentController;
use App\Http\Controllers\Api\V1\GroupChatController;
use App\Http\Controllers\Api\V1\HomeCarouselController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MusicController;
use App\Http\Controllers\Api\V1\PackController;
use App\Http\Controllers\Api\V1\PaymentGetWayController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PkController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\ReportUserController;
use App\Http\Controllers\Api\V1\RequestBackgroundImageController;
use App\Http\Controllers\Api\V1\Room\EnteranceController;
use App\Http\Controllers\Api\V1\Room\MicrophoneController;
use App\Http\Controllers\Api\V1\RoomCategoryController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SensitiveWordController;
use App\Http\Controllers\Api\V1\StorageUploadController;
use App\Http\Controllers\Api\V1\UploadLinkController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V2\AgencyController;
use App\Http\Controllers\Api\V2\MallController;
use App\Http\Controllers\AppFeatureController;
use App\Http\Controllers\CodapayController;
use App\Http\Controllers\Dashboard\StatisticsController;
use App\Http\Controllers\FirebaseAuthController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\NowPaymentsController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\PaySkyController;
use App\Http\Controllers\PaytabsController;
use App\Http\Controllers\RoomSettingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\UtdController;
use App\Http\Controllers\VersionController;
use App\Jobs\AllOpeningRoomsZegoRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Achievement\Http\Controllers\AchievementController;
use Modules\Region\Http\Controllers\AreaManagerController;
use Modules\Public\Http\Controllers\web\UpgradeLevelController;
use Modules\UsersWallet\Http\Controllers\Api\ExchangeController;
use Modules\Vip\Http\Controllers\Api\VipController;


// HAProxy health check endpoint — must be outside throttle middleware
Route::get('/health', function () {
    return response('ok', 200);
})->withoutMiddleware(['throttle:api', 'throttle']);
Route::get('/badges', [BadgeController::class, 'index']);
Route::post('/now-payments-callback', [NowPaymentsController::class, 'paymentCallback'])
    ->middleware(['verify.nowpayments.signature']);
Route::post('utd-stream-webhook', [\App\Http\Controllers\Api\V1\UtdStreamWebhookController::class, 'handle'])
    ->middleware(['verify.utdstream.webhook']);
Route::post('/check-phone', [UserController::class, 'checkPhone']);
Route::prefix(config('app.api_prefix'))->group(function () {
    // Protected test route - only accessible in local environment
    Route::middleware(['local'])->get('test-game-rtm', function () {
        $user = User::find(524);
        $room = Room::withoutAppends()->select(['id'])->where("uid", $user->now_room_uid)->first();

        $d = [
            "messageContent" => [
                "message" => "SBG",
                'uImage' => $user->profile?->avatar ?? 0,
                'uName' => $user->name ?? '',
                'uId' => $user->id ?? 0,
                'coins' => 50000,
                "gImage" => @$user->nowGame?->image
            ]
        ];
        $json = json_encode($d);
        dispatchJobToQueue(new AllOpeningRoomsZegoRequest($json, $user->id, $room?->id, false), 'heavyProcessing');
        return "gooooooooooooooooooooooooooooood";
    });

    Route::post('fawry-callback', [PaymentMethodController::class, 'callback'])->middleware("verify.fawry.signature");
    Route::post('utd-fawry-callback', [PaymentMethodController::class, 'utdCallback'])->middleware("verify.utdFawry.signature");
    Route::get('/fawry/done', [PaymentMethodController::class, 'success']);
    Route::post('utd-paymob-callback', [PaymentMethodController::class, 'utdPayMobCallback'])->middleware('verify.utdFawry.signature');
    Route::post('paypal-callback', [PayPalController::class, 'callback'])->name('paypal.callback')->middleware(['verify.paypal.webhook']);
    Route::get('paypal-return/{orderId}', [PayPalController::class, 'success'])->name('paypal.success');
    Route::get('paypal-cancel/{orderId}', [PayPalController::class, 'cancel'])->name('paypal.cancel');

    Route::get('codapay-callback', [CodapayController::class, 'callback'])->name('codapay.callback')->middleware(['verify.codapay.webhook']);
    Route::get('codapay-success/{id}/{country}', [CodapayController::class, 'success'])->name('codapay.success');

    Route::get('utd-success/{orderId}', [UtdController::class, 'success'])->name('utd.success');
    Route::post('utd-callback', [UtdController::class, 'callback'])->middleware(['verify.utdpay.webhook', 'throttle:30,1'])->name('utd.callback');

    Route::prefix('config')->group(function () {
        Route::post('app-check', [VersionController::class, 'versionAndCache']);
    });

    Route::post('/chatVideo', [StorageUploadController::class, 'chatVideo']);
    Route::get('/image-intro/{id}', [UserController::class, 'image_intro']);
    Route::get('colors', [ColorController::class, 'index']);
    Route::get('colors/v2', [ColorController::class, 'appCollor']);
    Route::get('firebase-config', [\App\Http\Controllers\Api\V1\ConfigController::class, 'firebaseConfig']);

    // v2 — user/agency/charge enumeration surfaces. Previously exposed
    // unauthenticated (registered before the auth:sanctum group below); gated
    // behind sanctum so only authenticated callers can enumerate this data.
    Route::prefix('search')->name('search.')->middleware(['auth:sanctum', 'checkLatestToken'])->group(function () {
        Route::get('users', [UserController::class, 'search'])->name('users');
        Route::get('users2', [UserController::class, 'search2'])->name('users2');
        Route::get('owner-rooms', [UserController::class, 'searchOwnerRoomWithPage'])->name('owner-rooms');
        Route::get('users7', [UserController::class, 'usersAudioRoom'])->name('users7');
        Route::get('users8', [UserController::class, 'usersLiveRoom'])->name('users8');
        Route::get('users-bd', [UserController::class, 'user_bd'])->name('users-bd');
        Route::get('users-bd2', [UserController::class, 'user_bd2'])->name('users-bd2');
        Route::get('users-bd-by-countries', [UserController::class, 'userBdByCountries'])->name('users-bd-by-countries');
        Route::get('users-superadmin', [UserController::class, 'superAdminUsers'])->name('users-superadmin');
        Route::get('users-subsuperadmin', [UserController::class, 'subSuperAdminUsers'])->name('users-subsupeadmin');
        Route::get('users-areamanager', [UserController::class, 'subAreaManager'])->name('users-areamanager');
        Route::get('users-superadmin2', [UserController::class, 'superAdminUsers2'])->name('users-superadmin2');
        Route::get('area-manager', [AreaManagerController::class, 'areaManger'])->name('area-manager');
        Route::get('users-by-country', [UserController::class, 'usersByCountry'])->name('users-superadmin.country');
        Route::get('users-by-countries', [UserController::class, 'usersByCountries'])->name('users-by-countries');
        Route::get('users3', [UserController::class, 'userAgency'])->name('users3');
        Route::get('users4', [UserController::class, 'userFamily'])->name('users4');
        Route::get('users5', [UserController::class, 'userAgencyShipping'])->name('users5');
        Route::get('app-manger', [UserController::class, 'userAgency'])->name('app-manger');
        Route::get('agencies', [UserController::class, 'agencies'])->name('agencies');
        Route::get('superadmin-agencies', [UserController::class, 'superAdminAgencies'])->name('superadmin-agencies');
        Route::get('host-agency', [UserController::class, 'hostAgencies'])->name('hostAgency');
        Route::get('charges', [UserController::class, 'charges'])->name('charges');
        Route::get('countries', [CountryController::class, 'searchCountries'])->name('countries');
        Route::get('regions', [CountryController::class, 'searchRegions'])->name('regions');
        Route::get('language', [LanguageController::class, 'searchLanguage'])->name('language');
        Route::get('get-country-users', [UserController::class, 'bdCountryUsers'])->name('country-users');
        Route::get('users-area-manager', [UserController::class, 'usersAreaManager'])->name('users-area-manager');
    });

    // authorization
    Route::prefix('auth')->group(function () {
        Route::get('all-countries', [CountryController::class, 'index']);

        // Authentication endpoints with strict rate limiting
        Route::post('register', [AuthController::class, 'register'])->middleware('auth.rate.limit:5,1');
        // Server-side OTP delivery (phone_otp_provider = twilio/whatsapp only;
        // firebase keeps the client-side SDK flow and this returns 422).
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('auth.rate.limit:5,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('auth.rate.limit:5,1');
        Route::post('recall-account', [AuthController::class, 'recallAccount'])->middleware('auth.rate.limit:3,1');
        Route::post('forget_password', [\App\Http\Controllers\Api\V2\Auth\ForgotPasswordController::class, 'reset'])->middleware('auth.rate.limit:3,5');
        Route::post('verify-code', [\App\Http\Controllers\Api\V2\Auth\ForgotPasswordController::class, 'verifyCode'])->middleware('auth.rate.limit:5,1');
    });


    Route::prefix('tickets')->middleware(['auth:sanctum', 'checkLatestToken', 'generalBan', 'userBan', 'throttle:10,1'])
        ->group(function () {
            Route::post('open', [\App\Http\Controllers\Api\V1\HomeController::class, 'openTicket']);
        });


    Route::post('/stripe-callback', [StripeController::class, 'handleWebhook']);
    Route::get('/payment/success', [StripeController::class, 'success']);
    Route::get('/payment/cancel', [StripeController::class, 'cancel']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/broadcasting/auth', function (Request $request) {
            return Broadcast::auth($request);
        });

        // ── Centrifugo auth surface (REALTIME_CHAT_REBUILD_PLAN §4.2) ─────────
        // Connection JWT + 1:1 subscription token. Client-facing, so guarded by
        // the same sanctum auth as broadcasting/auth. The subscribe proxy is a
        // server-to-server call and is registered separately below (no sanctum).
        Route::prefix('centrifugo')->group(function () {
            Route::post('token', [\App\Http\Controllers\Api\V1\CentrifugoAuthController::class, 'token']);
            Route::post('subscription', [\App\Http\Controllers\Api\V1\CentrifugoAuthController::class, 'subscription']);
        });
    });

    // Centrifugo subscribe proxy (REALTIME_CHAT_REBUILD_PLAN §4.2c). Invoked by
    // the Centrifugo node itself, authenticated by the shared proxy secret — NOT
    // sanctum (there is no user session on this hop).
    Route::post('centrifugo/subscribe', [\App\Http\Controllers\Api\V1\CentrifugoAuthController::class, 'subscribe'])
        ->middleware('verify.centrifugo.proxy');

    // all route with auth
    Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan', 'userBan', 'update.last.seen', 'localization'])->group(
        function () {
            Route::get('/agency-badges', [AgencySettingsController::class, 'badges']);

            Route::get('/user-gifts', [UserController::class, 'userGifts']);

            Route::get('user-room', [UserController::class, 'userRoom']);

            Route::post('/generate-upload-link', [UploadLinkController::class, 'uploadLink']);

            Route::post('/google-pay-purchased', [GooglePaymentController::class, 'purchasedFour']);
            Route::post('/testCharge', [GooglePaymentController::class, 'addChargeLevel']);

            Route::get('/countries/users', [CountryController::class, 'countries']);

            Route::get('/stripe-pay', [StripeController::class, 'pay']);
            Route::get('/paysky-pay', [PaySkyController::class, 'pay']);

            // ─── UTD-STREAM ─────────────────────────────────
            require __DIR__ . '/utd-stream.php';

            Route::prefix('config')->group(function () {
                Route::get('settings', [VersionController::class, 'settings']);
                Route::post('keys-values', [\App\Http\Controllers\Api\V1\ConfigController::class, 'getConfigValues']);
                //                Route::post('app-check', [\App\Http\Controllers\VersionController::class, 'versionAndCache']);
            });
            Route::get('user-app-setting', [UserController::class, 'appSetting'])->middleware('http.cache');

            Route::post('auth/logout', [UserController::class, 'logout']);
            Route::post('/change-room-effect', [UserController::class, 'showSetting']);
            Route::get('get-users-support', [UserController::class, 'get_users_support']);
            Route::post('hide', [HomeController::class, 'hide']);
            Route::get('user-statistics', [UserController::class, 'user_statistic']);
            Route::get('user-levels', [UserController::class, 'userLevels']);
            // rooms api
            Route::post('check-room', [RoomController::class, 'check_room']);

            Route::prefix('rooms')->group(function () {
                Route::get('level-badges', [VipController::class, 'roomBadges']);
                Route::get('/room-user', [RoomController::class, 'userRooms']);
                Route::get('/mine', [RoomController::class, 'mine']);
                Route::get('/user/{id}', [RoomController::class, 'userRoom']);
                Route::get('/', [RoomController::class, 'index'])->middleware('http.cache');
                Route::get('/live-rooms', [RoomController::class, 'getAllLiveRooms']);
                Route::post('/end-live', [RoomController::class, 'endLive']);
                // Live tap-hearts aggregation (batched client counts).
                Route::post('/taps', [\App\Http\Controllers\Api\V1\LiveTapsController::class, 'store'])->middleware('throttle:120,1');
                Route::get('/game-rooms', [RoomController::class, 'gameRoom']);
                Route::post('/create', [RoomController::class, 'store']);
                Route::get('/{id}', [RoomController::class, 'show'])->where('id', '[0-9]+');
                Route::get('/{owner_id}/extra-data', [RoomController::class, 'extraRoomData']);
                Route::get('/extra-data', [RoomController::class, 'extraDataRoom']);
                Route::post('/{owner_id}/send-private-comment', [RoomController::class, 'sendPrivateComment']);
                Route::post('charge_dollar_for_owner', [ChargeController::class, 'charge_co_for_owner']);
                Route::post('{room_id}/disable-writing', [RoomController::class, 'disable_writing']);
                Route::post('pk/change-image', [RoomController::class, 'changeRoomImage']);
                Route::post('/{id}/edit', [EnteranceController::class, 'update']);
                Route::post('firstOfRoom', [RoomController::class, 'firstOfRoom']);
                Route::post('admins', [RoomController::class, 'getAdmins']);
                Route::post('request-background-image', [RequestBackgroundImageController::class, 'RequestBackgroundImage']);
                Route::post('remove_pass', [RoomController::class, 'removeRoomPass']);
                Route::post('room_background_list', [BackgroundController::class, 'roomBackground']);
                Route::post('quit_room', [RoomController::class, 'quit_room_2']);
                //                Route::post('quit_room_2', [RoomController::class, 'quit_room_2']);
                Route::post('getRoomUsers', [RoomController::class, 'getRoomUsers']);
                Route::post('add_admin_to_room', [RoomController::class, 'is_admin']);
                Route::post('kick_out_of_room', [RoomController::class, 'out_room']);
                Route::post('remove_admin', [RoomController::class, 'remove_admin']);
                Route::post('update_admin_permissions', [RoomController::class, 'update_admin_permissions']);
                Route::post('black-list', [RoomController::class, 'blackList']);
                Route::post('remove-block', [RoomController::class, 'removeBlock']);
                Route::post('add-block', [RoomController::class, 'addBlock']);
                Route::post('{Room}/comment_status', [RoomController::class, 'commentStatus']);
                Route::post('/yellow-banner', [RoomController::class, 'sendComment']);
                Route::post('/check-admin-owner', [RoomController::class, 'adminOwner']);

                //Pk
                Route::middleware(['appFeatureEnable:pk'])->group(function () {
                    Route::post('create-pk', [PkController::class, 'createPK']);
                    Route::post('close-pk', [PkController::class, 'closePK']);
                    Route::post('show-pk', [PkController::class, 'showPK']);
                    Route::post('hide-pk', [PkController::class, 'hidePk']);
                });

                Route::middleware(['appFeatureEnable:pk'])->prefix('pk')->group(function () {
                    Route::post('create', [PkController::class, 'createPKWithoutStream']);
                    Route::post('close', [PkController::class, 'closePKWithoutStream']);
                    Route::post('show', [PkController::class, 'showPKWithoutStream']);
                    Route::post('hide', [PkController::class, 'hidePkWithoutStream']);
                });
                // Microphone

                Route::post('liveTime', [MicrophoneController::class, 'lifeTime']);
                Route::post('up-microphone', [MicrophoneController::class, 'upMicrophone2']);
                //                Route::post('up-microphone2', [MicrophoneController::class, 'upMicrophone2']);
                Route::post('leave-microphone', [MicrophoneController::class, 'goMicrophone2']);
                //                Route::post('leave-microphone2', [MicrophoneController::class, 'goMicrophone2']);
                Route::post('kick_microphone', [MicrophoneController::class, 'kickMicrophone']);
                Route::post('mute_microphone', [MicrophoneController::class, 'mute_microphone2']);
                //                Route::post('mute_microphone2', [MicrophoneController::class, 'mute_microphone2']);
                Route::post('unmute_microphone', [MicrophoneController::class, 'unmute_microphone2']);
                //                Route::post('unmute_microphone2', [MicrophoneController::class, 'unmute_microphone2']);
                Route::post('lock_microphone_place', [MicrophoneController::class, 'shut_microphone2']);
                //                Route::post('lock_microphone_place2', [MicrophoneController::class, 'shut_microphone2']);
                Route::post('unlock_microphone_place', [MicrophoneController::class, 'open_microphone2']);
                //                Route::post('unlock_microphone_place2', [MicrophoneController::class, 'open_microphone2']);
                Route::post('enter_room', [EnteranceController::class, 'enter_room']);
                Route::post('invite-user', [EnteranceController::class, 'invite_user']);
            });
            Route::post('change_room_mode', [RoomController::class, 'changeMode']);
            Route::post('rooms/change-mic-mode', [RoomController::class, 'changeMicMode']);
            Route::post('/firebase/custom-token', [FirebaseAuthController::class, 'loginWithUid'])->middleware('auth.rate.limit:10,1');
            Route::prefix('coins')->group(function () {
                Route::get('/list', [CoinController::class, 'coinList']);
                Route::post('/buyCoins', [CoinController::class, 'buyCoins']);
                Route::get('/payment', [CoinController::class, 'paymentCoin']);
                Route::get('user-report', [CoinController::class, 'userCoinReport']);
                Route::get('shipping-agency-report', [CoinController::class, 'shippingAgencyCoinReport']);
            });

            Route::prefix('charisma-levels')->middleware('charisma.badge')->group(function () {
                Route::get('/', [CharismaLevelController::class, 'index']);
            });

            Route::prefix('charisma')->group(function () {
                Route::post('/reset', [CharismaResetController::class, 'reset']);
            });

            Route::prefix('users')->group(function () {
                Route::get('/{id}', [UserController::class, 'show'])->where('id', '[0-9]+');
                Route::get('/details', [UserController::class, 'showUsersDetails'])->where('id', '[0-9]+');
                Route::get('v2/{id}', [UserController::class, 'vTwoshow'])->where('id', '[0-9]+');
                Route::get('/charger_agency', [UserController::class, 'chargerAgency']);
                Route::get('/play', [UserController::class, 'allUsersPlayGame']);
                Route::get('/stop-play', [UserController::class, 'updateGame']);
                Route::get('/online', [UserController::class, 'online']);
                Route::get('/friends', [UserController::class, 'friends']);
                Route::get('/data', [UserController::class, 'dataUser']);

                 Route::get('/level', [UserController::class, 'userLevelDetails']);

                Route::get('/stats/{id?}', [UserController::class, 'stats']);
                Route::get('/rooms/{id?}', [UserController::class, 'rooms']);
                Route::get('/vip-level/{id?}', [UserController::class, 'vipLevel']);
                Route::get('/frames/{id?}', [UserController::class, 'frames']);
            });

            Route::get('/room-countries', [RoomController::class, 'room_countries']);
            // end rooms api


            Route::prefix('account')->group(function () {
                Route::post('bind', [UserController::class, 'joinAccount']);
                Route::get('delete', [UserController::class, 'delete']);
                Route::post('change_phone', [UserController::class, 'changePhone'])->middleware('auth.rate.limit:3,5');
                Route::post('reset_password', [\App\Http\Controllers\Api\V2\Auth\ResetPasswordController::class, 'reset'])->middleware('auth.rate.limit:3,5');
            });

            Route::prefix('search')->group(function () {
                //                Route::get('/', [CommunityController::class, 'merge_search']);
                Route::get('/', [CommunityController::class, 'mergeSearchV2']);
                Route::get('user-friends', [CommunityController::class, 'user_friends']);
                Route::get('/history', [CommunityController::class, 'searchList']);
                Route::get('/clean_search_history', [CommunityController::class, 'cleanSearchList']);
            });

            Route::prefix('merge_search')->group(function () {
                Route::post('/', [CommunityController::class, 'merge_search']);
            });

            Route::prefix('community')->group(function () {
                Route::get('official_messages', [CommunityController::class, 'officialMessages']);
                Route::get('notifications', [CommunityController::class, 'notifications']);
            });

            Route::prefix('home_carousels')->group(function () {
                Route::get('/', [HomeCarouselController::class, 'index']);
            });


            Route::prefix('families')->middleware(['appFeatureEnable:families'])->group(function () {
                Route::get('all', [FamilyController::class, 'index']);
                Route::get('show/{id}', [FamilyController::class, 'show']);
                Route::post('create', [FamilyController::class, 'store']);
                Route::post('ranking', [FamilyController::class, 'ranking']);
                Route::post('top-ranking', [FamilyController::class, 'topUserRanking']);
                Route::post('edit/{id}', [FamilyController::class, 'update']);
                Route::post('join', [FamilyController::class, 'join']);
                Route::get('delete/{id}', [FamilyController::class, 'destroy']);
                Route::post('remove_user', [FamilyController::class, 'removeUser']);
                Route::post('req_list', [FamilyController::class, 'req_list']);
                Route::post('take_action', [FamilyController::class, 'RequestFamilyAction']);
                Route::post('change_user_type', [FamilyController::class, 'changeFamilyUserType']);
                Route::post('getMembersList', [FamilyController::class, 'getMembersList']);
                Route::post('getFamilyRooms', [FamilyController::class, 'getFamilyRooms']);
                Route::post('exitFamily', [FamilyController::class, 'exitFamily']);
            });



            Route::post('charge_history', [ChargeController::class, 'chargeHistory']);
            Route::post('user-charge-coins', [ChargeController::class, 'userChargeCoins']);
            Route::post('user-charge-coinsII', [ChargeController::class, 'userChargeCoinsII']);
             Route::get('/bad-words', [SensitiveWordController::class, 'index']);
            Route::prefix('gifts')->withoutMiddleware(['throttle', 'throttle:api'])->group(function () {
                Route::get('/', [GiftController::class, 'index']);
                Route::get('/v2', [GiftController::class, 'getByCategory']);
                Route::get('/images', [GiftController::class, 'get_images']);
                // Route::post('/send3', [GiftLogController::class, 'gift_queue_six2']);

                //todo
                Route::post('/send', [GiftLogController::class, 'gift_queue_cp']);
                Route::post('/v2/send-lucky-gift-combo', [GiftLogController::class, 'sendLuckyGift'])->middleware(['checkCpu', 'appFeatureEnable:lucky', 'throttle:lucky-gift']);
            });
            Route::prefix('gift-categories')->group(function () {
                Route::get('/', [GiftCategoryController::class, 'index']);
            });

            Route::get('my_gifts', [GiftLogController::class, 'giftLogsList']);


            Route::prefix('group-chat')->group(function () {
                Route::get('/', [GroupChatController::class, 'index']);
                Route::post('/send', [GroupChatController::class, 'store']);
            });

            Route::prefix('countries')->group(function () {
                Route::get('/', [CountryController::class, 'allCountries']);
                Route::get('/categories', [CountryController::class, 'countryCategory']);
                Route::get('/{id}', [CountryController::class, 'getCountry']);
                Route::get('/{id}/html', [CountryController::class, 'getCountryByHtml']);
                Route::post('change-request', [CountryController::class, 'changeRequest']);
            });
            // user controller
            Route::get('user-agency-information', [UserController::class, 'user_agency_information']);


            Route::prefix('room_category')->group(function () {
                Route::get('classes', [RoomCategoryController::class, 'allClasses']);
                Route::get('types', [RoomCategoryController::class, 'getTypes']);
                Route::get('types_by_class/{id}', [RoomCategoryController::class, 'getClassChildren']);
            });

            Route::prefix('backgrounds')->group(function () {
                Route::get('/', [BackgroundController::class, 'allBackgrounds']);
                Route::get('/me', [BackgroundController::class, 'allMyBackgrounds']);
                Route::get('/setting', [BackgroundController::class, 'backgroundSetting']);
            });

            Route::prefix('user_info')->group(function () {

                Route::get('my_pack', [PackController::class, 'my_pack']);
                Route::post('use_pack_item', [PackController::class, 'usePackItem']);
                Route::post('takeOff', [PackController::class, 'takeOff']);
                Route::post('takeOffV2', [PackController::class, 'takeOffV2']);
                //                Route::get('my_store', [UserController::class, 'my_store']);
                //                Route::get('my_income', [UserController::class, 'my_income']);
                Route::post('getTimes', [HomeController::class, 'getTimes']);
            });
            Route::post('send_pack', [UserController::class, 'sendPack']);

            Route::prefix('exchange')->group(function () {
                Route::get('/list', [ExchangeController::class, 'exchangeList']);
                Route::get('/v2/list', [ExchangeController::class, 'exchangeSettingNumber']);
                Route::post('/make', [ExchangeController::class, 'exchangeSave']);
                Route::post('/v2/make', [ExchangeController::class, 'exchangeCoin']);
                Route::get('/logs', [ExchangeController::class, 'exchangeLogs']);
            });

            Route::get('trxs', [ChargeController::class, 'trxLog']);
            Route::get('images', [HomeController::class, 'getImages']);

            Route::post('check_wapel', [HomeController::class, 'check_wapel']);

            Route::get('getUserHides', [HomeController::class, 'getUserHides']);

            // user api
            // Cold-start merge: my-data + my-store + user-app-setting in one GET.
            Route::get('bootstrap', [UserController::class, 'bootstrap'])->middleware('http.cache');
            Route::get('my-data', [UserController::class, 'my_data']);
            Route::post('update-user-image/{image_id}', [UserController::class, 'update_user_multi_images']);


            Route::get('explain-invitation', [UserController::class, 'explain_invitation'])->name('create-code-invitation');
            Route::get('parent-statistic', [UserController::class, 'UserEarnFromInvitationStatistics']);
            Route::get('parent-user', [UserController::class, 'parentUser']);
            Route::get('user-earn-from-invitation', [UserController::class, 'UserEarnFromInvitation']);
            Route::get('create-code-invitation', [UserController::class, 'CreateCodeInvitation']);
            Route::get('add-code-invitation', [UserController::class, 'AddCodeInvitation']);
            Route::get('/invitations/earnings', [UserController::class, 'invitationsEarnings']);
            Route::post('/invitations/earnings/{id}/claim', [UserController::class, 'invitationsEarningsClaim']);
            Route::get('/invitations/summary', [UserController::class, 'invitationSummary']);
            Route::post('/invitations/extract', [UserController::class, 'invitationExtract']);
            Route::post('/invitations/bonus/claim', [UserController::class, 'invitationBonusClaim']);

            // Todo Refact
            Route::get('my-store', [UserController::class, 'my_store_all'])->middleware('http.cache');

            Route::prefix('profile')->group(function () {
                Route::get('get/{id}', [ProfileController::class, 'show']);
                Route::post('update', [ProfileController::class, 'update']);
                Route::get('visitors', [ProfileController::class, 'myProfileVisitorsList']);
                Route::post('liked', [ProfileController::class, 'liked']);
                Route::post('ignored', [ProfileController::class, 'ignored']);
                Route::get('users', [ProfileController::class, 'getNearbyUsers']);
                Route::get('related', [ProfileController::class, 'related']);
                Route::get('following', [ProfileController::class, 'getFollowingUsers']);
            });

            // TODO refact @eriny
            Route::prefix('relations')->group(function () {
                Route::get('/', [UserController::class, 'userFriend']);
                Route::post('follow', [UserController::class, 'follow']);
                Route::post('un-follow', [UserController::class, 'unFollow']);
                Route::post('is_user_friend', [HomeController::class, 'check_if_friend']);
                Route::post('report_user', [ReportUserController::class, 'ReportUser']);
            });
            // end user api

            //start rankin
            Route::prefix('ranking')->group(function () {
                Route::post('/', [RankingController::class, 'ranking2']);
                Route::post('/room', [UserController::class, 'ranking_room']);
                Route::get('/top_user_ranking', [RankingController::class, 'topUserRanking']);
                Route::post('/one-room', [RankingController::class, 'oneRoomRanking']);
            });
            // end ranking

            Route::get('profile-frame-wares', [\App\Http\Controllers\Api\V1\WareController::class, 'profile_frame_wares']);
            // end vips



            Route::prefix('emojis')->group(function () {
                Route::get('/categories', [EmojiController::class, 'categories']);
                Route::get('/', [EmojiController::class, 'index']);
                Route::get('/v2', [EmojiController::class, 'all']);
                Route::get('/{id}', [EmojiController::class, 'show']);
            });

            Route::prefix('/v2/emojis')->group(function () {
                Route::get('/categories', [EmojiController::class, 'categories']);
                Route::get('/', [EmojiController::class, 'all']);
            });
            // start levels
            Route::get('levels-ranges', [UpgradeLevelController::class, 'getLevelsRange']);
            // end levels
            Route::prefix('mall')->middleware(['appFeatureEnable:mall'])->group(function () {
                Route::get('wares', [MallController::class, 'index']);
                Route::get('padding', [MallController::class, 'padding']);
                Route::post('buy', [MallController::class, 'buyWare']);
                Route::post('send', [MallController::class, 'sendWare']);
                Route::get('best-sale', [MallController::class, 'bestWareSale']);

                Route::get('wabble', [MallController::class, 'wabbleWare']);
                Route::get('wabbleAll', [MallController::class, 'wabbleAll']);
            });
            //start games
            Route::prefix('all-games1')->group(function () {
                Route::get('/', [AllGameController::class, 'index']);
                Route::get('/v2/out-of-room', [AllGameController::class, 'outRoom']);
                Route::get('/v2/in-room', [AllGameController::class, 'inRoom']);
                Route::post('update-game', [AllGameController::class, 'updateGame']);
            });
            // end games

            // questions
            Route::get('questions', [QuestionController::class, 'questions']);
            Route::post('send-mail-to-customer-service', [QuestionController::class, 'send_mail_to_customer_service']);
            // end questions

            Route::prefix('agencies')->middleware(['appFeatureEnable:agencies'])->group(function () {
                Route::post('charge_co_for_users', [ChargeController::class, 'sendMoneyFoeHost']);
                Route::get('charge_co_for_usersHistory', [ChargeController::class, 'chargeCoForUsersHistory']);
                Route::post('charge_dollar_for_owner', [ChargeController::class, 'ChargeDollarForOwner']);
                Route::get('charge_dollar_for_OwnerHistory', [ChargeController::class, 'chargeDollarHistory']);
                Route::post('join_request', [AgencyController::class, 'joinRequest']);
                Route::get('show', [AgencyController::class, 'view']);
                Route::get('details/{id}', [AgencyController::class, 'agencyDetails']);
                Route::get('admins/{id}', [AgencyController::class, 'admin']);
                Route::get('target-details/{id}', [AgencyController::class, 'agencyTargetDetails']); //target
                Route::get('stars/{id}', [AgencyController::class, 'star']);
                Route::get('heroes/{id}', [AgencyController::class, 'heroes']);
                Route::post('showAllusers', [AgencyController::class, 'agencyMembers']);
                Route::get('show-agency-request', [AgencyController::class, 'showAgencyRequest']);
                Route::get('show_request', [AgencyController::class, 'show_request']);
                Route::post('actions_request', [AgencyController::class, 'Accept_request']);
                Route::get('list_options_his', [AgencyController::class, 'list_options_his']);
                Route::post('historyAgancy', [AgencyController::class, 'historyAgencySearch']);
                Route::post('make-user-as-operator', [AgencyController::class, 'make_user_handling_requests']);
                Route::post('charge_to', [ChargeController::class, 'chargeTo']);
                Route::post('charges-history', [ChargeController::class, 'chargeToHistory']);
                Route::get('history/{id}', [AgencyController::class, 'history']);
                Route::post('{id}', [AgencyController::class, 'update'])->where('id', '[0-9]+');
                Route::get('charges', [AgencyController::class, 'agenciesCharge']);
                Route::post('charge-agency', [ChargeController::class, 'chargeFromAgencyToAnother']);
                Route::get('old-agencies', [AgencyController::class, 'gitOldAgencies']);
            });

            Route::post('search-user-agency', [ChargeController::class, 'getUserAgency']);
            Route::prefix('payment-gateway')->group(function () {
                Route::get('/', [PaymentGetWayController::class, 'index']);
                Route::post('/select-payment-get-way', [PaymentGetWayController::class, 'selectPaymentGateway']);
            });
            Route::get('/charge-level', [ChargeLevelController::class, 'chargeLevel']);

            // coins reports
            Route::get('/coin-reports', [CoinReportController::class, 'index']);
            Route::get('/event-coin-reports', [CoinReportController::class, 'eventCoins']);
            // end coin report
            Route::post('un_hide', [\App\Http\Controllers\Api\V1\HomeController::class, 'un_hide']);




            Route::prefix('banners')->group(function () {
                Route::get('/', [\App\Http\Controllers\BannerController::class, 'index2']);
                // Route::get('/', [\App\Http\Controllers\BannerController::class, 'index']);
                // Route::get('/banner', [\App\Http\Controllers\BannerController::class, 'index2']);
            });

            Route::prefix('black_list')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\BlackListController::class, 'index']);
                Route::post('/add', [\App\Http\Controllers\Api\V1\BlackListController::class, 'add']);
                Route::post('/remove', [\App\Http\Controllers\Api\V1\BlackListController::class, 'remove']);
                Route::get('/check/{userId}', [\App\Http\Controllers\Api\V1\BlackListController::class, 'checkBlockStatus']);
            });

            // Route::get('/data-data', function(){
            //     $id = \App\Models\User::first()?->id;
            //     $data = Common::level_center(@$id);
            //     return $data;
            // });

            // REMOVED (2026-08-16): test route — AuthController has no verifyGoogleToken()
            // method (the logic lives in AuthService); no client references this endpoint.

            // Music Store
            // Route::prefix('music')->group(function () {
            //     Route::get('/', [MusicStoreController::class, 'index']);
            //     Route::post('/', [MusicStoreController::class, 'store']);
            // });
            Route::get('achievement-valid-images', [AchievementController::class, 'achievement_valid_images']);

            Route::prefix('music')->group(function () {
                Route::get('/all', [MusicController::class, 'index']);
                Route::get('/user', [MusicController::class, 'userMusic']);
                Route::delete('{id}/user', [MusicController::class, 'destroyUserMusic']);
                Route::post('/create', [MusicController::class, 'store']);
            });

            Route::get('app_feature', [AppFeatureController::class, 'show']);
            Route::get('room_settings', [RoomSettingController::class, 'show']);

            Route::group(['prefix' => 'paytabs', 'as' => 'paytabs.'], function () {
                Route::any('pay', [PaytabsController::class, 'payment'])->name('pay');
                // Callback/return handled by top-level routes below (paytabs.callback / paytabs.return).
            });

            Route::get('/unsubscribe-all-from-topic/{topic}', function ($topic) {
                $tokens = User::whereNotNull('notification_id')
                    ->pluck('notification_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
                // $result = Common::unsubscribeFromTopic($tokens, $topic);

                // return response()->json($result);
            });
        }
    );

    Route::get('/languages', [LanguageController::class, 'index']);

    Route::get('/privacy-policy', function () {
        $Page = \App\Models\Page::where("name", "privacy-policy")->first();
        return response()->json(['html' => $Page]);
    });
});

Route::match(['get', 'post'], '/paytabs/callback', [PayTabsController::class, 'callback'])->name('paytabs.callback');
Route::match(['get', 'post'], '/paytabs/return/{payment_id}', [PayTabsController::class, 'return'])->name('paytabs.return');




Route::get('gifts-by-id', function (Request $request) {
    $gift = \App\Models\Gift::find($request->get('id'));
    if (!$gift) {
        return response()->json([]);
    }

    $imageUrl = $gift->show_img
        ?? ($gift->show_img ? Storage::url($gift->show_img) : null);

    return response()->json([
        'id'    => $gift->id,
        'name'  => $gift->name,
        'image' => $imageUrl,
    ]);
});


Route::post('/countries-in-polygon', [CountriesInPolygonController::class, 'getCountriesInPolygon']);

// Protected dashboard routes with authentication and rate limiting
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::get('dashboard/summary', [StatisticsController::class, 'summary']);
    Route::get('dashboard/charts', [StatisticsController::class, 'charts']);
    Route::get('dashboard/top-rooms', [StatisticsController::class, 'topRooms']);
});

// Queue restart route - protected with authentication, admin check, IP whitelist, and rate limiting
Route::middleware(['auth:sanctum', 'admin', 'adminIp', 'throttle:3,1'])->get('queue/restart', function () {
    \Log::warning('Queue restart triggered', [
        'ip' => request()->ip(),
        'user_id' => auth()->id(),
        'time' => now(),
    ]);

    \Artisan::call('queue:restart');
    return response()->json([
        'success' => true,
        'message' => 'Queue workers restarted successfully',
        'output' => \Artisan::output()
    ]);
});

// Cashback Report Routes - Simple Version (without Job)
Route::middleware(['admin'])->prefix('admin')->group(function () {
    // API - Get report data with pagination
    Route::get('cashback-report-simple', [\App\Http\Controllers\CashbackReportControllerSimple::class, 'index']);

    // Export CSV
    Route::get('cashback-report-simple/export', [\App\Http\Controllers\CashbackReportControllerSimple::class, 'exportCsv']);

    // Clear cache
    Route::post('cashback-report-simple/clear-cache', [\App\Http\Controllers\CashbackReportControllerSimple::class, 'clearCache']);
});

// Cashback Compensation Routes - تعويض المستخدمين
Route::middleware(['admin', 'adminIp'])->prefix('admin/cashback-compensation')->group(function () {
    // معاينة المستخدمين المستحقين للتعويض
    Route::get('preview', [\App\Http\Controllers\CashbackCompensationController::class, 'preview']);

    // تعويض مستخدم واحد
    Route::post('user', [\App\Http\Controllers\CashbackCompensationController::class, 'compensateUser']);

    // تعويض مستخدمين محددين
    Route::post('selected', [\App\Http\Controllers\CashbackCompensationController::class, 'compensateSelected']);

    // تعويض الكل (خطير - يحتاج تأكيد)
    Route::post('all', [\App\Http\Controllers\CashbackCompensationController::class, 'compensateAll']);
});
