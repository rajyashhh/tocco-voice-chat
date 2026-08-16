<?php
//
//
//use App\Helpers\Common;
//use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\utd\BanController;
//use App\Http\Controllers\utd\BoxController;
use App\Admin\Controllers\SettingController;
//use App\Http\Controllers\utd\RoomController;
//use App\Http\Controllers\Api\V1\PkController;
//use App\Http\Controllers\utd\ColorController;
//use App\Http\Controllers\utd\EmojiController;
//use App\Http\Controllers\utd\ImageController;
//use App\Http\Controllers\utd\LevelController;
//use App\Http\Controllers\utd\PagesController;
//use App\Http\Controllers\utd\ReelsController;
//use App\Http\Controllers\addTOjesonController;
//use App\Http\Controllers\Api\V1\VipController;
//use App\Http\Controllers\utd\AgencyController;
//use App\Http\Controllers\utd\BannerController;
//use App\Http\Controllers\utd\FamilyController;
//use App\Http\Controllers\utd\ReportController;
//use App\Http\Controllers\utd\SilverController;
//use App\Http\Controllers\utd\TicketController;
//use App\Http\Controllers\Api\V1\CoinController;
//use App\Http\Controllers\Api\V1\GiftController;
//use App\Http\Controllers\Api\V1\OvipController;
//use App\Http\Controllers\Api\V1\RoleController;
//use App\Http\Controllers\Api\V1\UserController;
//use App\Http\Controllers\Api\V1\WareController;
//use App\Http\Controllers\utd\BoxUserController;
//use App\Http\Controllers\utd\ChargesController;
//use App\Http\Controllers\utd\CountryController;
//use App\Http\Controllers\utd\MomentsController;
//use App\Http\Controllers\utd\PkEventController;
//use App\Http\Controllers\Api\V1\OfferController;
//use App\Http\Controllers\utd\ExchangeController;
//use App\Http\Controllers\utd\QuestionController;
//use App\Http\Controllers\utd\RoomVipsController;
//use App\Http\Controllers\utd\WithdrawController;
//use App\Admin\Controllers\AllStatisticController;
//use App\Http\Controllers\Api\V1\ConfigController;
//use App\Http\Controllers\Api\V1\TargetController;
//use App\Http\Controllers\utd\BlackListController;
//use App\Http\Controllers\utd\GroupChatController;
//use App\Http\Controllers\utd\LevelGiftController;
//use App\Http\Controllers\utd\RoleEventController;
//use App\Http\Controllers\utd\SallariesController;
//use App\Http\Controllers\Api\V1\AllGameController;
//use App\Http\Controllers\Api\V1\GiftLogController;
//use App\Http\Controllers\Api\V1\UtdUserController;
//use App\Http\Controllers\utd\AdminCheckController;
//use App\Http\Controllers\utd\BackgroundController;
//use App\Http\Controllers\utd\CpRelationController;
//use App\Http\Controllers\utd\DailyGiftsController;
//use App\Http\Controllers\utd\ImageColorController;
//use App\Http\Controllers\utd\ReportUserController;
//use App\Http\Controllers\utd\RoomTargetController;
//use App\Http\Controllers\AddTargetToJsonController;
//use App\Http\Controllers\utd\DedicateVipController;
//use App\Http\Controllers\utd\EventPeriodController;
//use App\Http\Controllers\utd\FamilyLevelController;
//use App\Http\Controllers\utd\MangerTypesController;
//use App\Http\Controllers\utd\ParentUsersController;
//use App\Http\Controllers\utd\SpecialWareController;
//use App\Http\Controllers\utd\TargetEventController;
//use App\Http\Controllers\utd\WeeklyEventController;
//use App\Http\Controllers\utd\ChargeAgencyController;
//use App\Http\Controllers\utd\ChargeReportController;
//use App\Http\Controllers\utd\DedicateWareController;
//use App\Http\Controllers\utd\HomeCarouselController;
//use App\Http\Controllers\utd\ReportMomentController;
//use App\Http\Controllers\utd\RoomCategoryController;
//use App\Http\Controllers\Api\V1\AdminUsersController;
//use App\Http\Controllers\Api\V1\GameReportController;
//use App\Http\Controllers\Api\V1\MangerTypeController;
//use App\Http\Controllers\Api\V1\PermissionController;
//use App\Http\Controllers\utd\ChargeCountryController;
//use App\Http\Controllers\utd\SalaryRequestController;
//use App\Http\Controllers\utd\SpecialIdFramController;
//use App\Http\Controllers\Api\V1\CoreWalletsController;
//use App\Http\Controllers\Api\V1\TrashedUserController;
//use App\Http\Controllers\Api\V2\Report_userController;
//use App\Http\Controllers\utd\DailyGiftTypesController;
//use App\Http\Controllers\utd\LevelIntervalsController;
//use App\Http\Controllers\utd\PaymentGateWayController;
//use App\Http\Controllers\utd\SpecialHistoryController;
//use App\Http\Controllers\utd\OfficialMessageController;
//use App\Http\Controllers\utd\RequestAgenciesController;
//use App\Http\Controllers\Api\V1\PaymentMethodController;
//use App\Http\Controllers\utd\PercentageTargetController;
//use App\Http\Controllers\utd\SallariesHistoryController;
//use App\Http\Controllers\utd\SpecialIdRequestController;
//use App\Http\Controllers\utd\RequestTakeSalaryController;
//use App\Http\Controllers\Api\V1\AgencyStatisticController;
//use App\Http\Controllers\utd\AppearChargerAgencyController;
//use App\Http\Controllers\utd\RewardLevelIntervalController;
//use App\Http\Controllers\utd\RequestBackgroundImageController;
//use Modules\Achievement\Http\Controllers\UtdAchievementController;
//use App\Http\Controllers\Api\V1\RoomController as RoomControllerVi;
//use App\Http\Controllers\Api\V1\ExchangeController as ExchangeDiamondController;
//
//// 'utd.decreptHeader'
//// utd apis
Route::middleware([])->group(function () {
//    //configs
//    Route::prefix('configs')->group(function () {
//        Route::get('/all', [ConfigController::class, 'index']);
//        Route::post('/update', [ConfigController::class, 'updateConfig']);
//        Route::get('/category', [ConfigController::class, "config"]);
//    });
//
//
//    Route::prefix('families')->group(function () {
//        Route::get('/', [FamilyController::class, 'index']);
//        Route::get('/all', [FamilyController::class, 'all']);
//        Route::post('/', [FamilyController::class, 'store']);
//        Route::post('/update/{id}', [FamilyController::class, 'update']);
//        Route::post('/delete/{id}', [FamilyController::class, 'destroy']);
//        Route::post('/delete-all', [FamilyController::class, 'delete_all']);
//        Route::get('/{id}', [FamilyController::class, 'show']);
//    });
//
//
//
//    Route::prefix('level-intervals')->group(function () {
//        Route::get('/', [LevelIntervalsController::class, 'index']);
//        Route::post('/', [LevelIntervalsController::class, 'store']);
//        Route::post('/update/{id}', [LevelIntervalsController::class, 'update']);
//        Route::post('/delete/{id}', [LevelIntervalsController::class, 'destroy']);
//        Route::get('/{id}', [LevelIntervalsController::class, 'show']);
//    });
//
//    Route::prefix('reward-level-interval/{reward_level_interval}')->group(function () {
//        Route::get('/', [RewardLevelIntervalController::class, 'index']);
//        Route::post('/', [RewardLevelIntervalController::class, 'store']);
//        Route::post('/update/{id}', [RewardLevelIntervalController::class, 'update']);
//        Route::post('/delete/{id}', [RewardLevelIntervalController::class, 'destroy']);
//
//        Route::get('/{id}', [RewardLevelIntervalController::class, 'show']);
//    });
//    Route::prefix('/reward-level')->group(function () {
//        Route::get('/types', [RewardLevelIntervalController::class, 'allType']);
//        Route::get('/wares', [RewardLevelIntervalController::class, 'wareInterval']);
//        Route::get('/vip', [RewardLevelIntervalController::class, 'vipInterval']);
//    });
//    Route::prefix('group-chat')->group(function () {
//        Route::get('/', [GroupChatController::class, 'index']);
//        Route::post('/', [GroupChatController::class, 'store']);
//        Route::post('/add-experience-points', [GroupChatController::class, 'add_experience_points']);
//        Route::post('/update/{id}', [GroupChatController::class, 'update']);
//        Route::post('/delete/{id}', [GroupChatController::class, 'destroy']);
//        Route::get('/{id}', [GroupChatController::class, 'show']);
//    });
//
//    Route::prefix('image-colors')->group(function () {
//        Route::get('/', [ImageColorController::class, 'index']);
//        Route::post('/', [ImageColorController::class, 'store']);
//        Route::post('/update/{id}', [ImageColorController::class, 'update']);
//        Route::post('/delete/{id}', [ImageColorController::class, 'destroy']);
//        Route::get('/{id}', [ImageColorController::class, 'show']);
//    });
//
//    Route::prefix('room-target')->group(function () {
//        Route::get('/', [RoomTargetController::class, 'index']);
//        Route::post('/', [RoomTargetController::class, 'store']);
//        Route::post('/update/{id}', [RoomTargetController::class, 'update']);
//        Route::post('/delete/{id}', [RoomTargetController::class, 'destroy']);
//        Route::get('/{id}', [RoomTargetController::class, 'show']);
//    });
//
//
//    Route::prefix('backgrounds')->group(function () {
//        Route::get('/', [BackgroundController::class, 'index']);
//        Route::post('/', [BackgroundController::class, 'store']);
//        Route::post('/update/{id}', [BackgroundController::class, 'update']);
//        Route::post('/delete/{id}', [BackgroundController::class, 'destroy']);
//        Route::get('/{id}', [BackgroundController::class, 'show']);
//    });
//
//    Route::prefix('percentage-target')->group(function () {
//        Route::get('/', [PercentageTargetController::class, 'index']);
//        Route::post('/', [PercentageTargetController::class, 'store']);
//    });
//
//    Route::prefix('parent-users')->group(function () {
//        Route::get('/', [ParentUsersController::class, 'index']);
//        Route::get('/{id}', [ParentUsersController::class, 'users']);
//    });
//
//    Route::prefix('pages')->group(function () {
//        Route::get('/', [PagesController::class, 'index']);
//        Route::get('/{id}', [PagesController::class, 'show']);
//        Route::post('/', [PagesController::class, 'store']);
//        Route::post('/update/{id}', [PagesController::class, 'update']);
//        Route::post('/delete/{id}', [PagesController::class, 'delete']);
//    });
//
//    Route::prefix('charges')->group(function () {
//        Route::get('/', [ChargesController::class, 'index']);
//        Route::post('/', [ChargesController::class, 'store']);
//    });
//
//    Route::prefix('appear-charger-agency')->group(function () {
//        Route::get('/', [AppearChargerAgencyController::class, 'index']);
//        Route::post('update/{id}', [AppearChargerAgencyController::class, 'update']);
//    });
//
//    Route::prefix('questions')->group(function () {
//        Route::get('/', [QuestionController::class, 'index']);
//        Route::post('/create', [QuestionController::class, 'store']);
//        Route::post('/update/{id}', [QuestionController::class, 'update']);
//        Route::post('/update-status/{id}', [QuestionController::class, 'update_status']);
//        Route::post('/delete/{id}', [QuestionController::class, 'delete']);
//        Route::post('/delete-all', [QuestionController::class, 'delete_all']);
//        Route::get('/{id}', [QuestionController::class, 'show']);
//    });
//
//    Route::prefix('categories')->group(function () {
//        Route::get('/', [RoomCategoryController::class, 'index']);
//        Route::post('/create', [RoomCategoryController::class, 'store']);
//        Route::get('/parent', [RoomCategoryController::class, 'parent']);
//        Route::post('/update/{id}', [RoomCategoryController::class, 'update']);
//        Route::post('/update-status/{id}', [RoomCategoryController::class, 'update_status']);
//        Route::post('/delete/{id}', [RoomCategoryController::class, 'delete']);
//        Route::post('/delete-all', [RoomCategoryController::class, 'delete_all']);
//        Route::get('/{id}', [RoomCategoryController::class, 'show']);
//    });
//
//
//
//    Route::prefix('event-period')->group(function () {
//        Route::get('/', [EventPeriodController::class, 'index']);
//        Route::post('/create', [EventPeriodController::class, 'store']);
//        Route::post('/update/{id}', [EventPeriodController::class, 'update']);
//        Route::post('/delete/{id}', [EventPeriodController::class, 'delete']);
//        Route::post('/delete-all', [EventPeriodController::class, 'delete_all']);
//        Route::get('/{id}', [EventPeriodController::class, 'show']);
//    });
//
//
//    Route::prefix('images')->group(function () {
//        Route::get('/', [ImageController::class, 'index']);
//        Route::post('/create', [ImageController::class, 'store']);
//        Route::post('/update/{id}', [ImageController::class, 'update']);
//        Route::post('/update-status/{id}', [ImageController::class, 'update_status']);
//        Route::post('/delete/{id}', [ImageController::class, 'delete']);
//        Route::post('/delete-all', [ImageController::class, 'delete_all']);
//        Route::get('/{id}', [ImageController::class, 'show']);
//    });
//
//
//    Route::prefix('emojis')->group(function () {
//        Route::get('/', [EmojiController::class, 'index']);
//        Route::post('/create', [EmojiController::class, 'store']);
//        Route::get('/pid', [EmojiController::class, 'emojiPid']);
//        Route::post('/update/{id}', [EmojiController::class, 'update']);
//        Route::post('/update-status/{id}', [EmojiController::class, 'update_status']);
//        Route::post('/delete/{id}', [EmojiController::class, 'delete']);
//        Route::post('/delete-all', [EmojiController::class, 'delete_all']);
//        Route::get('/{id}', [EmojiController::class, 'show']);
//    });
//
//
//    Route::prefix('boxes')->group(function () {
//        Route::get('/', [BoxController::class, 'index']);
//        Route::post('/create', [BoxController::class, 'store']);
//        Route::post('/update/{id}', [BoxController::class, 'update']);
//        Route::post('/delete/{id}', [BoxController::class, 'delete']);
//        Route::post('/delete-all', [BoxController::class, 'delete_all']);
//        Route::get('/{id}', [BoxController::class, 'show']);
//    });
//
//
//    Route::prefix('thrown-boxes')->group(function () {
//        Route::get('/', [BoxUserController::class, 'index']);
//        Route::post('/update/{id}', [BoxUserController::class, 'update']);
//        Route::post('/delete/{id}', [BoxUserController::class, 'delete']);
//        Route::post('/delete-all', [BoxUserController::class, 'delete_all']);
//        Route::get('/{id}', [BoxUserController::class, 'show']);
//    });
//
//
//    Route::prefix('payment-gateways')->group(function () {
//        Route::get('/', [PaymentGateWayController::class, 'index']);
//        Route::post('/create', [PaymentGateWayController::class, 'store']);
//        Route::post('/update/{id}', [PaymentGateWayController::class, 'update']);
//        Route::post('/delete/{id}', [PaymentGateWayController::class, 'delete']);
//        Route::post('/delete-all', [PaymentGateWayController::class, 'delete_all']);
//        Route::get('/{id}', [PaymentGateWayController::class, 'show']);
//    });
//
//
//    Route::prefix('withdraw-types')->group(function () {
//        Route::get('/', [WithdrawController::class, 'index']);
//        Route::post('/create', [WithdrawController::class, 'store']);
//        Route::post('/update/{id}', [WithdrawController::class, 'update']);
//        Route::post('/delete/{id}', [WithdrawController::class, 'delete']);
//        Route::post('/delete-all', [WithdrawController::class, 'delete_all']);
//        Route::get('/{id}', [WithdrawController::class, 'show']);
//    });
//
//    Route::prefix('agency-country')->group(function () {
//        Route::get('/', [ChargeAgencyController::class, 'index']);
//        Route::post('/create', [ChargeAgencyController::class, 'store']);
//        Route::post('/update/{id}', [ChargeAgencyController::class, 'update']);
//        Route::post('/delete/{id}', [ChargeAgencyController::class, 'delete']);
//        Route::post('/delete-all', [ChargeAgencyController::class, 'delete_all']);
//        Route::get('/{id}', [ChargeAgencyController::class, 'show']);
//    });
//
//    Route::prefix('cp-relations')->group(function () {
//        Route::get('/', [CpRelationController::class, 'index']);
//        Route::post('/create', [CpRelationController::class, 'store']);
//        Route::post('/update/{id}', [CpRelationController::class, 'update']);
//        Route::post('/delete/{id}', [CpRelationController::class, 'delete']);
//        Route::post('/delete-all', [CpRelationController::class, 'delete_all']);
//        Route::get('/{id}', [CpRelationController::class, 'show']);
//    });
//    Route::get('cp-types', [CpRelationController::class, 'types']);
//
//    Route::prefix('cp-levels/{relation_id}')->group(function () {
//        Route::get('/', [LevelController::class, 'index']);
//        Route::post('/create', [LevelController::class, 'store']);
//        Route::post('/update/{id}', [LevelController::class, 'update']);
//        Route::post('/delete/{id}', [LevelController::class, 'delete']);
//        Route::post('/delete-all', [LevelController::class, 'delete_all']);
//        Route::get('/{id}', [LevelController::class, 'show']);
//    });
//
//    Route::prefix('cp-level-gifts/{cp_level_id}')->group(function () {
//        Route::get('/', [LevelGiftController::class, 'index']);
//        Route::post('/create', [LevelGiftController::class, 'store']);
//        Route::get('/show/{id}', [LevelGiftController::class, 'show']);
//        Route::post('/update/{id}', [LevelGiftController::class, 'update']);
//        Route::post('/delete/{id}', [LevelGiftController::class, 'delete']);
//        Route::post('/delete-all', [LevelGiftController::class, 'delete_all']);
//    });
//
//    Route::prefix('request-agencies')->group(function () {
//        Route::get('/', [RequestAgenciesController::class, 'index']);
//        Route::post('/process-request/{id}', [RequestAgenciesController::class, 'update']);
//    });
//
//    Route::prefix('special-id-fram')->group(function () {
//        Route::get('/', [SpecialIdFramController::class, 'index']);
//        Route::get('/{id}', [SpecialIdFramController::class, 'show']);
//        Route::post('/create', [SpecialIdFramController::class, 'store']);
//        Route::post('/update/{id}', [SpecialIdFramController::class, 'update']);
//        Route::post('/delete/{id}', [SpecialIdFramController::class, 'delete']);
//    });
//
//
//    Route::prefix('special-id-requests')->group(function () {
//        Route::get('/', [SpecialIdRequestController::class, 'index']);
//        Route::get('/{id}', [SpecialIdRequestController::class, 'show']);
//        Route::post('/update/{id}', [SpecialIdRequestController::class, 'update']);
//        Route::post('/delete/{id}', [SpecialIdRequestController::class, 'delete']);
//        Route::post('/delete-all', [SpecialIdRequestController::class, 'delete_all']);
//    });
//
//    Route::prefix('special-wares')->group(function () {
//        Route::get('/', [SpecialWareController::class, 'index']);
//        Route::get('/{id}', [SpecialWareController::class, 'show']);
//        Route::post('/create', [SpecialWareController::class, 'store']);
//        Route::post('/update/{id}', [SpecialWareController::class, 'update']);
//        Route::post('/update-enable/{id}', [SpecialWareController::class, 'update_enable']);
//        Route::post('/delete/{id}', [SpecialWareController::class, 'delete']);
//        Route::post('/delete-all', [SpecialWareController::class, 'delete_all']);
//    });
//
//
//    Route::prefix('special-histories')->group(function () {
//        Route::get('/', [SpecialHistoryController::class, 'index']);
//        Route::post('/delete-all', [SpecialHistoryController::class, 'delete_all']);
//        Route::post('/delete/{id}', [SpecialHistoryController::class, 'delete']);
//    });
//
//
//
//    Route::prefix('dedicate-wares')->group(function () {
//        Route::get('/', [DedicateWareController::class, 'index']);
//        Route::post('/create', [DedicateWareController::class, 'store']);
//        Route::post('/update/{id}', [DedicateWareController::class, 'update']);
//        Route::post('/update-enable/{id}', [DedicateWareController::class, 'update_enable']);
//        Route::post('/delete-all', [DedicateWareController::class, 'delete_all']);
//        Route::post('dedicate/{id}', [DedicateWareController::class, 'dedicate']);
//    });
//
//    Route::prefix('vips-dedicate')->group(function () {
//        Route::get('/', [DedicateVipController::class, 'index']);
//        Route::post('/delete-all', [DedicateVipController::class, 'delete_all']);
//        Route::post('dedicate/{id}', [DedicateVipController::class, 'dedicate']);
//    });
//
//
//    Route::prefix('banners')->group(function () {
//        Route::get('/', [BannerController::class, 'index']);
//        Route::post('/create', [BannerController::class, 'store']);
//        Route::post('/delete-all', [BannerController::class, 'delete_all']);
//        Route::post('update/{id}', [BannerController::class, 'update']);
//        Route::post('delete/{id}', [BannerController::class, 'delete']);
//        Route::post('update-is-active/{id}', [BannerController::class, 'update_is_active']);
//        Route::get('/{id}', [BannerController::class, 'show']);
//    });
//
//    Route::prefix('home-carousels')->group(function () {
//        Route::get('/', [HomeCarouselController::class, 'index']);
//        Route::get('/type', [HomeCarouselController::class, 'type']);
//        Route::get('/event-type', [HomeCarouselController::class, 'eventType']);
//        Route::get('/form', [HomeCarouselController::class, 'form']);
//        Route::post('/create', [HomeCarouselController::class, 'store']);
//        Route::post('/delete-all', [HomeCarouselController::class, 'delete_all']);
//        Route::post('update/{id}', [HomeCarouselController::class, 'update']);
//        Route::post('delete/{id}', [HomeCarouselController::class, 'delete']);
//        Route::post('update-enable/{id}', [HomeCarouselController::class, 'update_is_active']);
//        Route::get('/{id}', [HomeCarouselController::class, 'show']);
//    });
//
//    Route::prefix('official-msgs')->group(function () {
//        Route::get('/', [OfficialMessageController::class, 'index']);
//        Route::post('/create', [OfficialMessageController::class, 'store']);
//        Route::post('/delete-all', [OfficialMessageController::class, 'delete_all']);
//        Route::post('update/{id}', [OfficialMessageController::class, 'update']);
//        Route::post('delete/{id}', [OfficialMessageController::class, 'delete']);
//        Route::get('/{id}', [OfficialMessageController::class, 'show']);
//    });
//
//    Route::prefix('charges-reports')->group(function () {
//        Route::get('/', [ChargeReportController::class, 'index']);
//        Route::get('/details', [ChargeReportController::class, 'details']);
//        Route::post('/return/{id}', [ChargeReportController::class, 'return']);
//    });
//
//    Route::prefix('sallaries')->group(function () {
//        Route::get('/', [SallariesController::class, 'index']);
//        Route::get('/details', [SallariesController::class, 'details']);
//        Route::post('/cashing', [SallariesController::class, 'cashing']);
//        Route::post('/pay', [SallariesController::class, 'pay']);
//    });
//
//    Route::prefix('report-user')->group(function () {
//        Route::get('/', [ReportUserController::class, 'index']);
//        Route::post('/create', [ReportUserController::class, 'store']);
//        Route::post('/delete-all', [ReportUserController::class, 'delete_all']);
//    });
//
//    Route::prefix('tickets')->group(function () {
//        Route::get('/', [TicketController::class, 'index']);
//        Route::get('/show/{id}', [TicketController::class, 'show']);
//        Route::post('/create', [TicketController::class, 'store']);
//        Route::post('/delete-all', [TicketController::class, 'delete_all']);
//        Route::post('update/{id}', [TicketController::class, 'update']);
//        Route::post('update-status/{id}', [TicketController::class, 'status']);
//        Route::post('delete/{id}', [TicketController::class, 'delete']);
//        Route::get('/{id}', [TicketController::class, 'show']);
//    });
//
//    Route::prefix('bans')->group(function () {
//        Route::get('/', [BanController::class, 'index']);
//        Route::post('/delete', [BanController::class, 'delete']);
//        Route::post('/ban-user', [BanController::class, 'banUser']);
//        Route::post('/remove-ban', [BanController::class, 'removeBan']);
//        Route::get('/ban-types', [BanController::class, 'banTypes']);
//    });
//
//    Route::prefix('sallaries_history')->group(function () {
//        Route::get('/', [SallariesHistoryController::class, 'index']);
//        Route::post('delete/{id}', [SallariesHistoryController::class, 'delete']);
//        Route::post('/delete-all', [SallariesHistoryController::class, 'delete_all']);
//    });
//
//    Route::prefix('daily-gift-types')->group(function () {
//        Route::get('/', [DailyGiftTypesController::class, 'index']);
//        Route::get('/{id}', [DailyGiftTypesController::class, 'show']);
//        Route::post('/create', [DailyGiftTypesController::class, 'store']);
//        Route::post('/update/{id}', [DailyGiftTypesController::class, 'update']);
//        Route::post('/delete/{id}', [DailyGiftTypesController::class, 'delete']);
//    });
//
//    Route::prefix('daily-gifts/{type}')->group(function () {
//        Route::get('/', [DailyGiftsController::class, 'index']);
//        Route::get('/{id}', [DailyGiftsController::class, 'show']);
//        Route::post('/create', [DailyGiftsController::class, 'store']);
//        Route::post('/update/{id}', [DailyGiftsController::class, 'update']);
//        Route::post('/delete/{id}', [DailyGiftsController::class, 'delete']);
//    });
//
//    //games
//    Route::prefix('games')->group(function () {
//        Route::get('/all', [AllGameController::class, 'utdGameIndex']);
//        Route::post('/create', [AllGameController::class, 'utdGameCreate']);
//        Route::post('/update', [AllGameController::class, 'utdGameUpdate']);
//        Route::post('/show', [AllGameController::class, 'showGame']);
//        Route::post('/update-switch', [AllGameController::class, 'utdGameSwitchUpdate']);
//        Route::get('/game-charge-details', [AllGameController::class, 'gameChargeDetails']);
//    });
//    // target
//    Route::prefix('targets')->group(function () {
//        Route::get('/all', [TargetController::class, 'index']);
//        Route::post('/create', [TargetController::class, 'store']);
//        Route::post('/update', [TargetController::class, 'update']);
//        Route::post('/show', [TargetController::class, 'show']);
//    });
//    //ovip
//    Route::prefix('ovips')->group(function () {
//        Route::get('/all', [OvipController::class, 'index']);
//        Route::post('/create', [OvipController::class, 'store']);
//        Route::post('/update', [OvipController::class, 'update']);
//        Route::post('/show', [OvipController::class, 'show']);
//        Route::post('/ware-vip', [VipController::class, 'createWareVip']);
//        Route::post('/show-privilege', [OvipController::class, 'showWithAllPrivileges']);
//        Route::get('/ware-vips', [VipController::class, 'getWareVip']);
//        Route::post('/delete-ware', [VipController::class, 'deleteWare']);
//    });
  // SECURITY (disabled 2026-06-20): these setting-mutation endpoints sat under
  // Route::middleware([]) with NO authentication — anyone on the internet could
  // POST to e.g. /api/utd/update-remaining-diamonds or /update-lucky-gifts and
  // change live settings. Confirmed ZERO callers in the backend, admin panel,
  // and Flutter app — they were only invoked manually. Disabled to remove the
  // attack surface. The controller methods remain (now permission-guarded); when
  // a panel toggle is needed, re-expose them under the admin-auth guard, never
  // under middleware([]). Lucky on/off is controlled server-side via stop_luckyGift.
  // Route::post('/update-room-cup', [SettingController::class, 'updateRoomCup']);
  // Route::post('/update-room-boom', [SettingController::class, 'updateRoomBoom']);
  // Route::post('/update-remaining-diamonds', [SettingController::class, 'updateRemainingDiamonds']);
  // Route::post('/update-host-level', [SettingController::class, 'updateHostLevel']);
  // Route::post('/update-pk-live', [SettingController::class, 'updatePkLive']);
  // Route::post('/update-lucky-gifts', [SettingController::class, 'updateLuckyGifts']);
  // Route::post('/update-is-theme-enabled', [SettingController::class, 'updateIsThemeEnabled']);
  // Route::post('/update-room-mode', [SettingController::class, 'updateRoomMode']);
  // Route::post('/update-charisma-format', [SettingController::class, 'updateCharismaFormat']);
  // Route::post('/update-charisma-badge', [SettingController::class, 'updateCharismaBadge']);
//
//
//
//    Route::get('all-vip-privileges', [OvipController::class, 'allVIP']);
//
//    // agency statistic
//    Route::get('agency-statistic', [AgencyStatisticController::class, 'statistic']);
//
//
//    //admin users
//    Route::prefix('admin_users')->group(function () {
//        Route::get('/all', [AdminUsersController::class, 'index']);
//        Route::post('/create', [AdminUsersController::class, 'store']);
//        Route::post('/show', [AdminUsersController::class, 'show']);
//        Route::get('/agency-user/{agencyId}', [AdminUsersController::class, 'showUserAgency']);
//    });
//    Route::prefix('gifts')->group(function () {
//        Route::get('/all', [GiftController::class, 'allGifts']);
//        Route::get('/type', [GiftController::class, 'typeGift']);
//        Route::post('/create', [GiftController::class, 'store']);
//        Route::post('/create-list', [GiftController::class, 'storeList']);
//        Route::post('/update', [GiftController::class, 'update']);
//        Route::post('/show', [GiftController::class, 'show']);
//        Route::post('/update-music-switch', [GiftController::class, 'musicSwitchUpdate']);
//        Route::post('/update-enable-switch', [GiftController::class, 'enableSwitchUpdate']);
//        Route::post('/update-play-switch', [GiftController::class, 'isPlaySwitchUpdate']);
//    });
//
//    //ware
//    Route::prefix('wares')->group(function () {
//        Route::get('/all', [WareController::class, 'index']);
//        Route::post('/create', [WareController::class, 'store']);
//        Route::post('/create-list', [WareController::class, 'storeList']);
//        Route::post('/update', [WareController::class, 'update']);
//        Route::post('/show', [WareController::class, 'show']);
//        Route::post('/update-enable-switch', [WareController::class, 'enableSwitchUpdate']);
//        Route::get('/type', [WareController::class, 'typeWare']);
//        Route::get('/get-type', [WareController::class, 'getTypeWare']);
//    });
//    Route::post('/create-paymentMethod', [PaymentMethodController::class, 'store']);
//
//
//    // roles
//    Route::resource('roles', RoleController::class);
//    Route::get('permissions', [RoleController::class, "permissions"]);
//    Route::get('permissions-category', [RoleController::class, "permissionsCategory"]);
//    Route::resource('all-permissions', PermissionController::class);
//    Route::post('all-permissions/{id}', [PermissionController::class, 'update']);
//    Route::get('http-methods', [PermissionController::class, "getHttpMethodsOptions"]);
//
//
//    // users
//    Route::resource('utd-users', UtdUserController::class);
//    Route::get('utd-users/show/{id}', [UtdUserController::class, 'show']);
//
//
//
//    Route::get('all-users', [UserController::class, 'userWithSearch']);
//
//    //mangerType
//    Route::prefix('manger-types')->group(function () {
//        Route::get('/all', [MangerTypeController::class, 'index']);
//        Route::post('/create', [MangerTypeController::class, 'store']);
//        Route::post('/update', [MangerTypeController::class, 'update']);
//        Route::post('/show', [MangerTypeController::class, 'show']);
//        Route::delete('/delete/{id}', [MangerTypesController::class, 'destroy']);
//    });
//    //target Percentage
//    Route::prefix('target-Percentage')->group(function () {
//        Route::post('/create', [AddTargetToJsonController::class, 'create']);
//        Route::get('/show', [AddTargetToJsonController::class, 'show']);
//    });
//    //setting config
//    Route::prefix('setting-config')->group(function () {
//        Route::post('/create', [addTOjesonController::class, 'create']);
//        Route::get('/show', [addTOjesonController::class, 'show']);
//    });
//    //CoreWallets
//    Route::prefix('core-wallets')->group(function () {
//        Route::get('/all', [CoreWalletsController::class, 'index']);
//        Route::post('/create', [CoreWalletsController::class, 'store']);
//        Route::post('/update', [CoreWalletsController::class, 'update']);
//        Route::post('/show/{id}', [CoreWalletsController::class, 'show']);
//        Route::delete('delete/{id}', [CoreWalletsController::class, 'delete']);
//    });
//
//    //coin
//    Route::prefix('coins')->group(function () {
//        Route::get('/all/{payment_id}', [CoinController::class, 'index']);
//        Route::post('/create/{payment_id}', [CoinController::class, 'store']);
//        Route::post('/update', [CoinController::class, 'update']);
//        Route::get('/show', [CoinController::class, 'show']);
//        Route::get('/payment-gateway', [CoinController::class, 'paymentCoin']);
//        Route::post('/payment-gateway/create', [CoinController::class, 'createPaymentGateway']);
//        Route::post('/payment-gateway/update', [CoinController::class, 'updatePaymentGateway']);
//        Route::post('/payment-gateway/show', [CoinController::class, 'showPaymentCoin']);
//    });
//    Route::get('/app-information', [AllStatisticController::class, 'appInformation']);
//
//
//    Route::post('roles/preview',  [RoleController::class, 'preview']);
//
//
//    // game report
//    Route::get('/all-players', [GameReportController::class, 'allPlayers']);
//    Route::get('/player-details/{id}', [GameReportController::class, 'playerDetails']);
//    Route::post('/game-ranking/{id}', [GameReportController::class, 'gameRanking']);
//    Route::get('/games-info/{id}', [GameReportController::class, 'gameInfo']);
//    Route::get('/user-game-play/{id}', [GameReportController::class, 'gamePlay']);
//    // end game report
//
//    Route::prefix('trashed-account')->group(function () {
//        Route::get('/', [TrashedUserController::class, 'trashedAccount']);
//        Route::post('/restore/{id}', [TrashedUserController::class, 'restore']);
//        Route::post('/delete/{id}', [TrashedUserController::class, 'softDelete']);
//    });
//
//    Route::prefix('users-level')->group(function () {
//        Route::get('/', [UserController::class, 'userLevel']);
//        Route::post('/update/{id}', [UserController::class, 'updateUserLevel']);
//        Route::get('/history', [UserController::class, 'userLevelHistory']);
//    });
//
//    Route::prefix('salary-requests')->group(function () {
//        Route::get('/', [SalaryRequestController::class, 'all']);
//        Route::post('/create', [SalaryRequestController::class, 'create']);
//    });
//
//    Route::prefix('filter')->group(function () {
//        Route::get('/agency', [AgencyController::class, 'by_agency_filter']);
//        Route::post('/agency-owner', [UserController::class, 'by_user_filter']);
//    });
//
//    Route::prefix('device-token')->group(function () {
//        Route::get('/', [UserController::class, 'usersDeviceToken']);
//        Route::delete('/delete/{id}', [UserController::class, 'deleteDeviceToken']);
//    });
//
//    Route::prefix('family-levels')->group(function () {
//        Route::get('/all', [FamilyLevelController::class, 'index']);
//        Route::post('/show/{id}', [FamilyLevelController::class, 'show']);
//        Route::post('/create', [FamilyLevelController::class, 'store']);
//        Route::post('/update/{id}', [FamilyLevelController::class, 'update']);
//        Route::post('/delete/{id}', [FamilyLevelController::class, 'destroy']);
//    });
//
//    Route::get('user-target', [UserController::class, 'usersTarget']);
//
//    Route::prefix('users')->group(function () {
//        Route::get('/', [UserController::class, 'allUsers']);
//        Route::post('kick-agency/{id}', [UserController::class, 'kickAgency']);
//        Route::post('kick-family/{id}', [UserController::class, 'kickFamily']);
//        Route::post('change-agency', [UserController::class, 'changeAgency']);
//        Route::post('update-switch', [UserController::class, 'updateSwitch']);
//        Route::post('update-user-Setting', [UserController::class, 'updateUserSetting']);
//        Route::post('create', [UserController::class, 'create']);
//        Route::get('show/{id}', [UserController::class, 'showDataUser']);
//        Route::post('update/{id}', [UserController::class, 'updateDataUser']);
//        Route::get('user-type', [UserController::class, 'userType']);
//        Route::get('my-data/{id}', [UserController::class, 'myData']);
//        Route::get('my-gifts/{id}', [GiftLogController::class, 'myGiftInfo']);
//        Route::get('coins/{id}', [ChargesController::class, 'userCharge']);
//        Route::get('salary/{id}', [UserController::class, 'userSalaryWithHisAgency']);
//        Route::get('report/{id}', [Report_userController::class, 'userReport']);
//        Route::get('exchange-diamonds/{id}', [ExchangeDiamondController::class, 'UserExchangeLogs']);
//        Route::get('charge-month/{id}', [ChargesController::class, 'userMonthCharge']);
//        Route::get('pack-vip/{id}', [UserController::class, 'userPacksAndVip']);
//        Route::get('vip/{id}', [UserController::class, 'userVip']);
//        Route::get('pack/{id}', [UserController::class, 'userPacks']);
//        Route::get('room-visit/{id}', [UserController::class, 'userVisitRooms']);
//        Route::get('cp/{id}', [UserController::class, 'allCpUser']);
//        Route::get('room/{id}', [RoomControllerVi::class, 'roomUserDetails']);
//        Route::get('room/gifts/{id}', [RoomControllerVi::class, 'roomGifts']);
//        Route::get('room-pk/{id}', [PkController::class, 'roomPk']);
//    });
//
//    Route::prefix('rooms')->group(function () {
//        Route::get('/', [RoomController::class, 'all']);
//        Route::post('/create', [RoomController::class, 'store']);
//        Route::get('/class', [RoomController::class, 'roomClass']);
//        Route::get('/type/{classId}', [RoomController::class, 'roomType']);
//        Route::post('/update/{id}', [RoomController::class, 'update']);
//        Route::get('/show/{id}', [RoomController::class, 'show']);
//        Route::post('/update-switches', [RoomController::class, 'updateSwitches']);
//        Route::delete('delete/{id}', [RoomController::class, 'destroy']);
//    });
//
//
//    Route::prefix('reels')->group(function () {
//        Route::get('/all', [ReelsController::class, 'index']);
//        Route::get('/user/{id}', [ReelsController::class, 'user']);
//        Route::post('/show/{id}', [ReelsController::class, 'show']);
//        Route::post('/search/{id}', [ReelsController::class, 'search']);
//        Route::post('/delete/{id}', [ReelsController::class, 'destroy']);
//        Route::post('/reelConfig/{id}', [ReelsController::class, 'reelConfig']);
//    });
//
//
//    Route::prefix('room-vips')->group(function () {
//        Route::get('/all', [RoomVipsController::class, 'index']);
//        Route::post('/show/{id}', [RoomVipsController::class, 'show']);
//        Route::post('/search/{key}', [RoomVipsController::class, 'search']);
//        Route::post('/delete/{id}', [RoomVipsController::class, 'destroy']);
//        Route::post('create', [RoomVipsController::class, 'store']);
//        Route::post('update/{id}', [RoomVipsController::class, 'update']);
//    });
//    Route::get('users-search', [UserController::class, 'search']);
//    Route::get('users-search2', [UserController::class, 'search2_new']);
//    Route::get('users-search3', [UserController::class, 'userAgency']);
//    Route::prefix('achievements')->group(function () {
//        Route::get('/all', [UtdAchievementController::class, 'allAchievements']);
//        Route::get('/{achievementId}/level', [UtdAchievementController::class, 'allAchievementsLevel']);
//        Route::post('/create-level', [UtdAchievementController::class, 'createAchievementLevel']);
//        Route::post('/update-level/{id}', [UtdAchievementController::class, 'updateAchievementLevel']);
//        Route::get('/show/{id}', [UtdAchievementController::class, 'showAchievementLevel']);
//        Route::get('/target-level', [UtdAchievementController::class, 'achievementTargetType']);
//        Route::get('/{achievementId}/all-users-gift-achievement', [UtdAchievementController::class, 'allUsersGiftAchievements']);
//        Route::post('/create-user-gift', [UtdAchievementController::class, 'createUserAchievementGift']);
//        Route::get('/gift-achievement', [UtdAchievementController::class, 'giftAchievement']);
//        Route::get('/user-achievement-level', [UtdAchievementController::class, 'allUserAchievementLevel']);
//        Route::post('/enable/{id}', [UtdAchievementController::class, 'isEnable']);
//        Route::post('/delete_user_level/{id}', [UtdAchievementController::class, 'deleteUserAchievementLevel']);
//        Route::post('/create/user-level', [UtdAchievementController::class, 'createUserAchievementLevel']);
//        Route::get('/gift-user-level', [UtdAchievementController::class, 'userAchievementLevelGiftIndex']);
//        Route::get('/target-user-level/{achievementId}', [UtdAchievementController::class, 'getAchievementLevelsTarget']);
//    });
//
//    Route::prefix('silvers')->group(function () {
//        Route::get('/', [SilverController::class, 'all']);
//        Route::get('/show/{id}', [SilverController::class, 'show']);
//        Route::post('/create', [SilverController::class, 'create']);
//        Route::post('/update/{id}', [SilverController::class, 'update']);
//        Route::delete('/delete/{id}', [SilverController::class, 'destroy']);
//    });
//
//    Route::prefix('exchanges')->group(function () {
//        Route::get('/', [ExchangeController::class, 'all']);
//        Route::get('/show/{id}', [ExchangeController::class, 'show']);
//        Route::post('/create', [ExchangeController::class, 'create']);
//        Route::post('/update/{id}', [ExchangeController::class, 'update']);
//        Route::delete('/delete/{id}', [ExchangeController::class, 'destroy']);
//    });
//    Route::get('codes', [UserController::class, 'allCodes']);
//
//    Route::prefix('moment')->group(function () {
//        Route::get('/', [MomentsController::class, 'all']);
//        Route::get('/show/{id}', [MomentsController::class, 'show']);
//        Route::post('/search/{uuid}', [MomentsController::class, 'search']);
//        Route::delete('/delete/{id}', [MomentsController::class, 'destroy']);
//        Route::post('/config', [MomentsController::class, 'config']);
//        Route::get('/get-user-moments/{id}', [MomentsController::class, 'get_user_moments']);
//    });
//
//    Route::prefix('offers')->group(function () {
//        Route::get('/', [OfferController::class, 'index']);
//        Route::get('/show/{id}', [OfferController::class, 'show']);
//        Route::post('/create', [OfferController::class, 'store']);
//        Route::delete('/delete/{id}', [OfferController::class, 'delete']);
//        Route::post('/update/{id}', [OfferController::class, 'update']);
//    });
//
//    Route::prefix('request-background-image')->group(function () {
//        Route::get('/', [RequestBackgroundImageController::class, 'all']);
//        Route::get('/show/{id}', [RequestBackgroundImageController::class, 'show']);
//        Route::post('/create', [RequestBackgroundImageController::class, 'create']);
//        Route::delete('/delete/{id}', [RequestBackgroundImageController::class, 'destroy']);
//        Route::post('/update/{id}', [RequestBackgroundImageController::class, 'update']);
//    });
//
//    Route::prefix('report-moment')->group(function () {
//        Route::get('/', [ReportMomentController::class, 'all']);
//        Route::get('/show/{id}', [ReportMomentController::class, 'show']);
//        Route::post('/create', [ReportMomentController::class, 'create']);
//        Route::delete('/delete/{id}', [ReportMomentController::class, 'destroy']);
//        Route::post('/update/{id}', [ReportMomentController::class, 'update']);
//        Route::post('delete-moment/{moment_id}/{id}', [ReportMomentController::class, 'destroyDash']);
//    });
//
//    // Route::prefix('request-agency')->group(function () {
//    //     Route::get('/', [AgencyController::class, 'index']);
//    //     Route::post('/action', [AgencyController::class, 'actionRequestAgency']);
//    // });
//
//    Route::prefix('agencies')->group(function () {
//        Route::get('/', [AgencyController::class, 'activeAgencies']);
//        Route::get('/all', [AgencyController::class, 'allAgencies']);
//        Route::get('/members', [AgencyController::class, 'activeAgenciesMembers']);
//        Route::post('/create', [AgencyController::class, 'create']);
//        Route::post('/update/{id}', [AgencyController::class, 'update']);
//        Route::get('/show/{id}', [AgencyController::class, 'show']);
//        Route::post('/change-agency-members', [AgencyController::class, 'changeAgencyMembers']);
//        Route::get('/all-old', [AgencyController::class, 'allAgenciesExceptOld']);
//    });
//
//    Route::prefix('agency-join-request')->group(function () {
//        Route::get('/', [AgencyController::class, 'allAgencyJoinRequest']);
//        Route::post('/update/{id}', [AgencyController::class, 'updateAgencyJoinRequest']);
//        Route::get('/show/{id}', [AgencyController::class, 'showAgencyJoinRequest']);
//    });
//
//    Route::prefix('requests-for-get-salary')->group(function () {
//        Route::get('/', [RequestTakeSalaryController::class, 'all']);
//        Route::post('/update/{id}', [RequestTakeSalaryController::class, 'update']);
//        Route::get('/show/{id}', [RequestTakeSalaryController::class, 'show']);
//        Route::get('/history', [RequestTakeSalaryController::class, 'history']);
//    });
//
//    Route::get('/reports', [ReportController::class, 'reports']);
//
//    Route::prefix('countries')->group(function () {
//        Route::get('/', [CountryController::class, 'index']);
//        Route::get('/charge', [ChargeCountryController::class, 'country']);
//        Route::post('/create', [CountryController::class, 'store']);
//        Route::post('/update/{id}', [CountryController::class, 'update']);
//        Route::post('/update-status/{id}', [CountryController::class, 'update_status']);
//        Route::post('/delete/{id}', [CountryController::class, 'delete']);
//        Route::post('/delete-all', [CountryController::class, 'delete_all']);
//        Route::get('/{id}', [CountryController::class, 'show']);
//    });
//
//    Route::prefix('colors')->group(function () {
//        Route::get('/', [ColorController::class, 'index']);
//        Route::get('/all', [ColorController::class, 'all']);
//        Route::get('/show/{id}', [ColorController::class, 'show']);
//        Route::post('/create', [ColorController::class, 'store']);
//        Route::post('/update/{id}', [ColorController::class, 'update']);
//        Route::post('/delete/{id}', [ColorController::class, 'delete']);
//        Route::post('/delete-all', [ColorController::class, 'delete_all']);
//        Route::post('/app-setting', [ColorController::class, 'app_setting']);
//    });
//
//    Route::prefix('target-events')->group(function () {
//        Route::get('/', [TargetEventController::class, 'index']);
//        Route::get('/show/{id}', [TargetEventController::class, 'show']);
//        Route::post('/create', [TargetEventController::class, 'store']);
//        Route::delete('/delete/{id}', [TargetEventController::class, 'destroy']);
//        Route::post('/update/{id}', [TargetEventController::class, 'update']);
//    });
//
//    Route::prefix('charge-country')->group(function () {
//        Route::get('/', [ChargeCountryController::class, 'all']);
//        Route::get('/show/{id}', [ChargeCountryController::class, 'show']);
//        Route::post('/create', [ChargeCountryController::class, 'store']);
//        Route::delete('/delete/{id}', [ChargeCountryController::class, 'destroy']);
//        Route::post('/update/{id}', [ChargeCountryController::class, 'update']);
//    });
//
//    Route::prefix('target-events-gift')->group(function () {
//        Route::get('/{targetId}', [TargetEventController::class, 'allGifts']);
//        Route::get('/show/{id}', [TargetEventController::class, 'showGift']);
//        Route::post('/create', [TargetEventController::class, 'storeGift']);
//        Route::delete('/delete/{id}', [TargetEventController::class, 'destroyGift']);
//        Route::post('/update/{id}', [TargetEventController::class, 'updateGift']);
//    });
//
//    Route::prefix('weekly-events')->group(function () {
//        Route::get('/', [WeeklyEventController::class, 'index']);
//        Route::get('/show/{id}', [WeeklyEventController::class, 'show']);
//        Route::post('/create', [WeeklyEventController::class, 'store']);
//        Route::delete('/delete/{id}', [WeeklyEventController::class, 'destroy']);
//        Route::post('/update/{id}', [WeeklyEventController::class, 'update']);
//        Route::get('/default-date', [WeeklyEventController::class, 'defaultDate']);
//    });
//
//    Route::prefix('weekly-events-gift')->group(function () {
//        Route::get('/', [WeeklyEventController::class, 'allGifts']);
//        Route::get('/show/{id}', [WeeklyEventController::class, 'showGift']);
//        Route::post('/create', [WeeklyEventController::class, 'storeGift']);
//        Route::delete('/delete/{id}', [WeeklyEventController::class, 'destroyGift']);
//        Route::post('/update/{id}', [WeeklyEventController::class, 'updateGift']);
//    });
//
//    Route::prefix('pk-events')->group(function () {
//        Route::get('/', [PkEventController::class, 'index']);
//        Route::get('/show/{id}', [PkEventController::class, 'show']);
//        Route::post('/create', [PkEventController::class, 'store']);
//        Route::delete('/delete/{id}', [PkEventController::class, 'destroy']);
//        Route::post('/update/{id}', [PkEventController::class, 'update']);
//        Route::get('/default-date', [PkEventController::class, 'defaultDate']);
//    });
//
//    Route::prefix('pk-events-gift')->group(function () {
//        Route::get('/', [PkEventController::class, 'allGifts']);
//        Route::get('/show/{id}', [PkEventController::class, 'showGift']);
//        Route::post('/create', [PkEventController::class, 'storeGift']);
//        Route::delete('/delete/{id}', [PkEventController::class, 'destroyGift']);
//        Route::post('/update/{id}', [PkEventController::class, 'updateGift']);
//    });
//
//    Route::prefix('event-reports')->group(function () {
//        Route::get('/', [ReportController::class, 'eventReports']);
//        Route::post('/return-reward', [ReportController::class, 'returnReward']);
//    });
//
//
//
//    Route::prefix('general-roles')->group(function () {
//        Route::get('/', [RoleEventController::class, 'all']);
//        Route::get('/show/{id}', [RoleEventController::class, 'show']);
//        Route::post('/create', [RoleEventController::class, 'create']);
//        Route::post('/update/{id}', [RoleEventController::class, 'update']);
//        Route::delete('/delete/{id}', [RoleEventController::class, 'destroy']);
//        Route::get('/details', [RoleEventController::class, 'details']);
//        Route::get('/types', [RoleEventController::class, 'types']);
//    });
//    Route::get('/wares-event', [RewardLevelIntervalController::class, 'wareInterval']);
//    Route::get('/vip-event', [RewardLevelIntervalController::class, 'vipInterval']);
//    Route::get('/gift-event', [WeeklyEventController::class, 'gifts']);
});
//
//Route::prefix('transaction-request-problem')->group(function () {
//    Route::get('/', [AdminCheckController::class, 'all']);
//    Route::post('accept/{id}', [AdminCheckController::class, 'accept']);
//    Route::post('refuse/{id}', [AdminCheckController::class, 'cancel']);
//});
//
//
//
//Route::prefix('blacks')->group(function () {
//    Route::get('/', [BlackListController::class, 'list']);
//    Route::post('/', [BlackListController::class, 'store']);
//    // Route::post('/search/{key}', [BlackListController::class, 'search']);
//    // Route::post('{user_id}/blocked-search/{key}', [BlackListController::class, 'blocked_search']);
//    Route::get('/black-lists/{user_id}', [BlackListController::class, 'black_lists']);
//    Route::post('delete/{id}', [BlackListController::class, 'delete']);
//});
