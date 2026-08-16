<?php
/*

use App\Classes\Facades\Agency;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Route;

agency::routes();

Route::group(
    [
        'prefix'        => config('agency.route.prefix'),
        'namespace'     => config('agency.route.namespace'),
        'middleware'    => [
            'web',
            'agency',
            'multiLanguage',
        ],
        'as'            => config('agency.route.prefix') . '.',
    ],
    function () {
        Route::resource ('auth/users','AdminUserController');
        Route::resource ('auth/roles','RoleController');
        //resources
        Route::resource('users', 'UserController',[
            'names'=>[
                'index'=>'users',
                'show'=>'users.show'
            ]
        ]);
        Route::resource('profiles', 'ProfileController');
        Route::resource('vips', 'VipController');
        Route::resource('rooms', 'RoomController',[
            'names'=>[
                'index'=>'rooms'
            ]
        ]);
        Route::resource('blacks', 'BlackListController');
        Route::resource('codes', 'CodeController');
        Route::resource ('gifts','GiftController',[
            'names'=>[
                'index'=>'gifts'
            ]
        ]);
        Route::resource ('wares','WareController',[
            'names'=>[
                'index'=>'wares'
            ]
        ]);
        Route::resource ('coupons','CouponController');
        Route::resource ('configs','ConfigController');
        Route::resource ('categories','RoomCategoryController');
        Route::resource ('countries','CountryController');
        Route::resource ('backgrounds','BackgroundController');
        Route::resource ('official_msgs','OfficialMessageController');
        Route::resource ('emojis','EmojiController');
        Route::resource ('home_carousels','HomeCarouselController');
        Route::resource ('vip_prev','VipAuthController');
        Route::resource ('agencies','AgencyController');
        Route::resource ('families','FamilyController');
        Route::resource ('targets','TargetController');
        Route::resource ('charges','ChargeController',[
            'names'=>[
                'store'=>'charges.new'
            ]
        ]);
        Route::resource ('charge_values','ChargeValueController');
        Route::resource ('userTarget','UserTargetController',[
            'names'=>[
                'index'=>'user_targets'
            ]
        ]);


        //--------------------
        Route::get('/', 'HomeController@infoBox')->name('home');
        Route::get('/dev', 'HomeController@devindex')->name('dev-home');
        Route::get('/agency_home', 'HomeController@agencyInfoBox')->name('agency.home');

        Route::resource ('agency_join_requests','AgencyJoinRequestController');
        Route::resource ('family_levels','FamilyLevelController');
        Route::resource ('silver','SilverController');
        Route::resource ('coins','CoinController');
        Route::resource ('ovip','OVipController');
        Route::resource ('vip_privilege','VipPrivilegeController');
        Route::resource ('tickets','TicketController');
        Route::resource ('pages','PageController');
        Route::resource ('exchanges','ExchangeController');
        Route::resource ('boxes','BoxController');
        Route::resource ('thrown_boxes','BoxUseController');
        Route::resource ('reports','ReportController');
        Route::post ('cashing','ReportController@cashing')->name ('cashing');
        Route::resource ('trxs','CoinLogController');
    }
);

*/
