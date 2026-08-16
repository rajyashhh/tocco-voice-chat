<?php

use App\Http\Controllers\Dashboard\Achievement\AdminAchievementGiftsController;
use App\Http\Controllers\Dashboard\Auth\AuthUserController;
use App\Http\Controllers\Dashboard\Banners\AdminBannersController;
use App\Http\Controllers\Dashboard\Banners\AdminCuarselController;
use App\Http\Controllers\Dashboard\Bans\AdminBansController;
use App\Http\Controllers\Dashboard\Charing\AdminChargingAgencyController;
use App\Http\Controllers\Dashboard\Charing\AdminChargingReportsController;
use App\Http\Controllers\Dashboard\Charing\AdminCharingCoinsController;
use App\Http\Controllers\Dashboard\Charing\AdminCharingExchangesController;
use App\Http\Controllers\Dashboard\Charing\AdminCharingSilverController;
use App\Http\Controllers\Dashboard\Charing\AdminPaymentController;
use App\Http\Controllers\Dashboard\Charing\AdminUserCharingController;
use App\Http\Controllers\Dashboard\Families\AdminFamiliesController;
use App\Http\Controllers\Dashboard\Families\AdminFAmilyLevelsController;
use App\Http\Controllers\Dashboard\Invitations\AdminInvitationsController;
use App\Http\Controllers\Dashboard\Levels\AdminLevelssController;
use App\Http\Controllers\Dashboard\OfficalMssages\AdminOfficalMssagesController;
use App\Http\Controllers\Dashboard\Profile\ProfileDashboardController;
use App\Http\Controllers\Dashboard\Room\AdminBackgroundRequestController;
use App\Http\Controllers\Dashboard\Room\AdminBackgroundRoomController;
use App\Http\Controllers\Dashboard\Room\AdminEmojeRoomController;
use App\Http\Controllers\Dashboard\Room\AdminGiftRoomController;
use App\Http\Controllers\Dashboard\Room\AdminRoomCategoriesController;
use App\Http\Controllers\Dashboard\Room\AdminRoomsController;
use App\Http\Controllers\Dashboard\Users\UsersDashboard;
use App\Http\Controllers\Dashboard\Vips\AdminPrivilegeVipsController;
use App\Http\Controllers\Dashboard\Vips\AdminVipsController;
use App\Http\Controllers\Dashboard\Wares\AdminWaresController;
use App\Http\Controllers\Dashboard\Achievement\AdminAchievementLevelsController;
use App\Http\Controllers\Dashboard\Achievement\AdminSendAchievementController;
use App\Http\Controllers\Dashboard\Codes\AdminCodesController;
use App\Http\Controllers\Dashboard\Countries\AdminCountriesController;
use App\Http\Controllers\Dashboard\Events\AdminGeneralRolesController;
use App\Http\Controllers\Dashboard\Events\Chargebenefit\AdminChargebenefitController;
use App\Http\Controllers\Dashboard\Events\Chargebenefit\AdminChargebenefitRewordsController;
use App\Http\Controllers\Dashboard\Events\PeriodEvent\AdminPeriodEventController;
use App\Http\Controllers\Dashboard\Events\PeriodEvent\AdminPeriodEventRewordsController;
use App\Http\Controllers\Dashboard\Events\PK\AdminPKEventsController;
use App\Http\Controllers\Dashboard\Events\PK\AdminPKEventsRewords;
use App\Http\Controllers\Dashboard\Events\WeeklyStar\AdminWeeklyStarEvents;
use App\Http\Controllers\Dashboard\Events\WeeklyStar\AdminWeeklyStarEventsRewords;
use App\Http\Controllers\Dashboard\GroupChat\AdminGroupChatController;
use App\Http\Controllers\Dashboard\Posts\AdminMomentController;
use App\Http\Controllers\Dashboard\Posts\AdminReelsController;
use App\Http\Controllers\Dashboard\Reports\AdminReportsController;
use App\Http\Controllers\Dashboard\Trash\AdminTrashController;
use App\Http\Controllers\Dashboard\Wallet\AdminCoreWalletController;
use App\Http\Controllers\Dashboard\Wares\AdminSpecialIdController;
use Illuminate\Support\Facades\Route;


route::post('/google-sign-in',[AuthUserController::class,'google_sign_in']);
route::post('/login',[AuthUserController::class,'sign_in']);
Route::middleware('auth:sanctum','verified')->group(function(){
    route::resource('user',ProfileDashboardController::class);

    //Users Management
    route::resource('admin-users',UsersDashboard::class);
    route::get('/admin-user-can-play/{user_id}/{status}',[UsersDashboard::class,'can_play']);
    route::get('/admin-user-can-charge/{user_id}/{status}',[UsersDashboard::class,'can_charge']);
    route::get('/user-autocomplete',[UsersDashboard::class,'autocomplete']);

    //User Profile
    route::get('/user-event-report/{id}/{type}',[UsersDashboard::class,'event_report']);
    route::get('/user-package/{id}',[UsersDashboard::class,'user_pack']);
    route::get('/user-moment/{id}',[UsersDashboard::class,'user_moments']);
    route::get('/user-reels/{id}',[UsersDashboard::class,'user_reels']);
    route::get('/user-group-chat/{id}',[UsersDashboard::class,'user_group_chats']);
    route::get('/user-bans/{id}',[UsersDashboard::class,'user_bans']);
    route::get('/user-targets/{id}',[UsersDashboard::class,'user_targets']);


    Route::post('getTimes', [\App\Http\Controllers\Api\V1\HomeController::class, 'getTimes']);


    //Wares
    Route::controller(AdminWaresController::class)->group(function(){
        Route::resource('wares', AdminWaresController::class);
        route::get('/get-wares/{main_type}/{type}','index');
        route::get('/Sort-wares/{main_type}','sort');
        route::post('/Change-Sort-wares','change_sort');
        route::post('/Send-Wares','send');
        route::get('/enable-wares/{ware_id}/{status}','enable_wares');
        route::get('/wares-autocomplete','autocomplete');
    });

    //Wares
    Route::controller(AdminSpecialIdController::class)->group(function(){
        Route::resource('admin-special-id', AdminSpecialIdController::class);
        route::get('/Sort-special-id','sort');
        route::post('/Change-Sort-special-id','change_sort');
        route::post('/Send-special-id','send');
        route::get('/enable-special-id/{ware_id}/{status}','enable_special_id');

        route::get('/special-id-history','special_id_history');
        route::get('/Sort-special-id','sort');
    });

    //********************** Start Rooms ********************** \\
        //bacground
        Route::controller(AdminBackgroundRoomController::class)->group(function(){
            Route::resource('room-background', AdminBackgroundRoomController::class);
            route::get('/enable-room-background/{id}/{status}','enable_Background');
            route::get('/Sort-background/{main_type}','sort');
            route::post('/Change-Sort-background','change_sort');
        });

        Route::resource('background-requests', AdminBackgroundRequestController::class);

        //Categories
        Route::controller(AdminRoomCategoriesController::class)->group(function(){
            Route::resource('room-categories', AdminRoomCategoriesController::class);
            route::get('/enable-room-categories/{id}/{status}','enable_categories');
            route::get('/Sort-categories','sort');
            route::post('/Change-Sort-categories','change_sort');
        });

        //Emoji
        Route::controller(AdminEmojeRoomController::class)->group(function(){
            Route::resource('room-emoje', AdminEmojeRoomController::class);
            route::get('/enable-room-emoje/{id}/{status}','enable_emoje');
            route::get('/Sort-emoje/{main_type}','sort');
            route::post('/Change-Sort-emoje','change_sort');
        });

        //Gift
        Route::controller(AdminGiftRoomController::class)->group(function(){
            route::get('/get-room-gift/{type}','index');
            route::get('/enable-gift/{id}/{status}/{type}','enable_gift');
            Route::resource('room-gift', AdminGiftRoomController::class);
            route::get('/Sort-gift','sort');
            route::post('/Change-Sort-gift','change_sort');
        });

        //Rooms
        Route::controller(AdminRoomsController::class)->group(function(){
            Route::resource('rooms', AdminRoomsController::class);
            route::get('/get-rooms/{type}','index');
            route::get('/enable-rooms/{id}/{status}/{type}','enable_rooms');
        });
    //********************** End Rooms ********************** \\


    //********************** Start Charing System ********************** \\

        Route::resource('admin-payment', AdminPaymentController::class);

        //Charing System
        Route::resource('Charing-system', AdminUserCharingController::class);
        route::get('/Charing-system-Get-User/{type}/{id}',[AdminUserCharingController::class,'index']);

        Route::resource('CharingAgency', AdminChargingAgencyController::class);
        route::get('/CharingAgency/{id}/{status}',[AdminChargingAgencyController::class,'enable_user']);

        Route::resource('Admin-ChargingReports', AdminChargingReportsController::class);

        //exchanges
        Route::controller(AdminCharingExchangesController::class)->group(function(){
            Route::resource('charing-exchanges', AdminCharingExchangesController::class);
            route::get('/Sort-exchanges','sort');
            route::post('/Change-Sort-exchanges','change_sort');
        });


        //Silver
        Route::controller(AdminCharingSilverController::class)->group(function(){
            Route::resource('charing-silver', AdminCharingSilverController::class);
            route::get('/Sort-silver','sort');
            route::post('/Change-Sort-silver','change_sort');
        });

        //Coins
        Route::controller(AdminCharingCoinsController::class)->group(function(){
            Route::resource('charing-coins', AdminCharingCoinsController::class);
            route::get('/Sort-Coins','sort');
            route::post('/Change-Sort-Coins','change_sort');
        });
    //********************** End  Charing System ********************** \\


    //************************** start Family ************************* \\
        Route::controller(AdminFAmilyLevelsController::class)->group(function(){
            Route::resource('family-levels', AdminFAmilyLevelsController::class);
            route::get('/Sort-levels','sort');
            route::post('/Change-Sort-levels','change_sort');
        });

        Route::controller(AdminFamiliesController::class)->group(function(){
            Route::resource('admin-families', AdminFamiliesController::class);
            route::get('/enable-families/{id}/{status}','enable_families');

        });

    //************************** End Family **************************** \\

    //Levels
    Route::get('get-admin-levels/{type}',[ AdminLevelssController::class,'index']);
    Route::resource('admin-levels', AdminLevelssController::class);

    //Invitations
    Route::resource('admin-Invitations', AdminInvitationsController::class);
    Route::get('admin-user-Operations/{id}/{user_id}', [AdminInvitationsController::class,'Operations']);

    //Bans
    Route::resource('admin-Bans', AdminBansController::class);
    Route::get('get-bans-type',[ AdminBansController::class,'types']);


    //Vips Previlage
    Route::resource('admin-vip-privilege', AdminPrivilegeVipsController::class);

    //Vips
    Route::controller(AdminVipsController::class)->group(function(){
        Route::resource('admin-vips', AdminVipsController::class);
        route::get('/Sort-vips','sort');
        route::post('/Send-vips','Send');
        route::post('/Change-Sort-vips','change_sort');
        route::get('/vips-autocomplete','autocomplete');

    });

    //Cuarsel
    Route::controller(AdminCuarselController::class)->group(function(){
        Route::resource('admin-cuarsel', AdminCuarselController::class);
        route::get('/Sort-cuarsel','sort');
        route::get('/enable-cuarsel/{id}/{status}','enable_carousel');
        route::post('/Change-Sort-cuarsel','change_sort');
    });

    //Banners
    Route::controller(AdminBannersController::class)->group(function(){
        Route::resource('admin-banners', AdminBannersController::class);
        route::get('/Sort-banners','sort');
        route::get('/enable-banners/{id}/{status}','enable_banners');
        route::post('/Change-Sort-banners','change_sort');
    });

    route::post('/get-admin-official-Message',[ AdminOfficalMssagesController::class ,'index']);
    Route::resource('admin-official-Message', AdminOfficalMssagesController::class);

    //Medals
    Route::resource('admin-Achievement', AdminAchievementLevelsController::class);
    Route::resource('admin-Achievement-gifts', AdminAchievementGiftsController::class);

    Route::resource('admin-Send-Achievement', AdminSendAchievementController::class);
    route::get('/enable-Send-Achievement/{id}/{status}',[ AdminSendAchievementController::class,'enable_user_achievemnt']);

    Route::resource('admin-moment',AdminMomentController::class);
    Route::resource('admin-reels',AdminReelsController::class);

    //************************** start Events ************************* \\
        Route::resource('admin-event-pk',AdminPKEventsController::class);
        Route::resource('admin-event-pk-rewords',AdminPKEventsRewords::class);

        Route::resource('admin-event-WeeklyStar',AdminWeeklyStarEvents::class);
        Route::resource('admin-event-WeeklyStar-rewords',AdminWeeklyStarEventsRewords::class);

        Route::resource('admin-event-PeriodEvent',AdminPeriodEventController::class);
        Route::resource('admin-event-PeriodEvent-rewords',AdminPeriodEventRewordsController::class);

        Route::resource('admin-event-Chargebenefit',AdminChargebenefitController::class);
        Route::resource('admin-event-Chargebenefit-rewords',AdminChargebenefitRewordsController::class);

        Route::resource('admin-general-rols',AdminGeneralRolesController::class);
        Route::get('admin-event-general-reports/{type}',[AdminGeneralRolesController::class,'reports']);
        Route::get('admin-event-delete-reports/{id}/{type}',[AdminGeneralRolesController::class,'delete_reports']);
    //************************** End Events **************************** \\

        Route::resource('admin-GroupChat',AdminGroupChatController::class);
        Route::resource('admin-codes',AdminCodesController::class);
        Route::resource('admin-countries',AdminCountriesController::class);
        Route::get('enable-admin-countries/{id}/{status}',[AdminCountriesController::class,'enable_country']);
        Route::get('countries-autocomplete',[AdminCountriesController::class,'country_autocomplete']);

        Route::resource('admin-tarsh',AdminTrashController::class);

        Route::get('admin-moments-report',[AdminReportsController::class,'moment']);
        Route::get('admin-reels-report',[AdminReportsController::class,'reels']);
        Route::get('admin-tickets',[AdminReportsController::class,'tickets']);
        Route::get('admin-delete-tickets/{id}',[AdminReportsController::class,'delete_tickets']);


        Route::resource('admin-core-wallet',AdminCoreWalletController::class);
    });
