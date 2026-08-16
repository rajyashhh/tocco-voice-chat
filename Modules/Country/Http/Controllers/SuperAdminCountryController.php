<?php

namespace Modules\Country\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\Bd;
use App\Models\Charge;
use App\Models\CoinGameUser;
use App\Models\CoinGameUserMergedMonthly;
use App\Models\Country;
use App\Models\GiftLog;
use App\Models\GiftRanking;
use App\Models\Room;
use Modules\Country\Entities\SuperAdmin;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Request;
use KevinSoft\MultiLanguage\MultiLanguage;

class SuperAdminCountryController extends Controller
{
    /**
     * Fail-closed country-scope guard for the /superadmin portal.
     *
     * Unlike Common::isCountryInAdminScope (which treats an empty scope as
     * "unrestricted"), this derives the allowed scope from the authenticated
     * admin's role explicitly. Country managers (superadmin / sub_super_admin)
     * are excluded from the area-manager models, so their area scope is always
     * empty — relying on the empty-scope shortcut would let any country manager
     * read every country. Here the ONLY role that passes all countries is an
     * explicitly unrestricted admin (administrator or the '*' permission).
     */
    private function assertCountryInScope($id): void
    {
        $admin = \Encore\Admin\Facades\Admin::user();

        if ($admin && ($admin->isAdministrator() || $admin->can('*'))) {
            return;
        }

        $scope = match ($admin->type ?? null) {
            'region', 'sub_region'  => Common::areaCountries(),
            'country', 'sub_country' => [(int) $admin->country_id],
            default                 => [],
        };

        abort_unless(in_array((int) $id, $scope, true), 403);
    }

    public function index($id)
    {
        $this->assertCountryInScope($id);

        $country = Country::findOrFail($id);
        $countryID = $country->id;

        // Only load essential data for instant page render
        $superAdmin = SuperAdmin::with('appUser.profile')->where('country_id', $countryID)->first();
        $onlineUsers = $this->getOnlineUsers($countryID);

        return view('super_admin_country', compact('country', 'superAdmin', 'onlineUsers'));
    }

    public function locale()
    {
        $locale = Request::input('locale');
        $languages = MultiLanguage::config('languages');

        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        if (array_key_exists($locale, $languages)) {

            return response('ok')->cookie($cookie_name, $locale);
        }
    }


    public function index2($id)
    {
        $this->assertCountryInScope($id);

        $country = Country::findOrFail($id);

        return view('superAdmin.super_admin_country', compact('country'));
    }

    public function getStats(\Illuminate\Http\Request $request, $id)
    {
        $this->assertCountryInScope($id);

        $country = Country::findOrFail($id);
        $cacheKey = "country_stats_{$id}";

        // Cache for 5 minutes
        $stats = \Cache::remember($cacheKey, 300, function () use ($country) {
            return $this->fetchCountryStats($country);
        });

        return response()->json($stats);
    }

    private function fetchCountryStats(Country $country)
    {
        $countryID = $country->id;
        $timezone = Common::timeZone();
        $from = Carbon::now($timezone)->subDays(30)->startOfDay();
        $to = Carbon::now($timezone)->endOfDay();

        $defaultAvatar = asset('images/businessman-icon.jpg');
        $defaultRoom = asset('images/background_room.jpg');
        $defaultAgency = asset('images/icon-agency.jpg');

        return [
            'topRooms' => $this->getTopRooms($countryID, $from, $to)->map(fn($r) => [
                'name'  => $r->room_name ?? '-',
                'image' => getImagePath($r->room_cover) ?? $defaultRoom,
                'value' => number_format($r->room_visitors_count ?? 0),
            ])->values(),

            'topSenders' => $this->getTopSenders($countryID, $from, $to)->map(fn($s) => [
                'name'  => $s->sender?->name ?? '-',
                'image' => getImagePath($s->sender?->profile?->avatar) ?? $defaultAvatar,
                'value' => number_format(($s->total_sent ?? 0) / 1000, 1) . 'K',
            ])->values(),

            'topReceivers' => $this->getTopReceivers($countryID, $from, $to)->map(fn($r) => [
                'name'  => $r->receiver?->name ?? '-',
                'image' => getImagePath($r->receiver?->profile?->avatar) ?? $defaultAvatar,
                'value' => number_format(($r->total_sent ?? 0) / 1000, 1) . 'K',
            ])->values(),

            'topAgencies' => $this->getTopAgencies($countryID)->map(fn($a) => [
                'name'  => $a->name ?? '-',
                'image' => getImagePath($a->img) ?? $defaultAgency,
                'value' => number_format($a->members_count ?? 0),
            ])->values(),

            'topChargeAgencies' => $this->getTopChargeAgencies($countryID, $from, $to)
                ->filter(fn($c) => $c->senderShippingAgency)
                ->map(fn($c) => [
                    'name'  => $c->senderShippingAgency->name ?? '-',
                    'image' => getImagePath($c->senderShippingAgency->img ?? null) ?? $defaultAgency,
                    'value' => number_format($c->amount ?? 0, 2),
                ])->values(),

            'topBds' => $this->getTopBds($countryID)->map(fn($b) => [
                'name'  => $b->name ?? '-',
                'image' => null,
                'value' => number_format($b->total_members ?? 0),
            ])->values(),

            'topGamers' => $this->getTopGamers($countryID, $from, $to)->map(fn($g) => [
                'name'  => $g->user?->name ?? '-',
                'image' => getImagePath($g->user?->profile?->avatar) ?? $defaultAvatar,
                'value' => number_format(($g->coins ?? 0) / 1000, 1) . 'K',
            ])->values(),
        ];
    }

    private function getOnlineUsers($countryID)
    {
        return User::where('country_id', $countryID)
            ->where('online', 1)
            ->count();
    }

    private function getSuperAdmin($countryID)
    {
        return Admin::where('country_id', $countryID)
            ->where('type', 'country')
            ->select('id', 'name', 'country_id')
            ->with('user:id,name')
            ->first();
    }

    private function getTopRooms($countryID, $from, $to)
    {
        return Room::select('rooms.id', 'rooms.room_name', 'rooms.uid', 'rooms.room_cover')
            ->join('users', 'rooms.uid', '=', 'users.id')
            ->where('users.country_id', $countryID)
            ->with('owner:id,name,country_id')
            ->withCount(['roomVisitors' => function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            }])
            ->orderByDesc('room_visitors_count')
            ->limit(3)
            ->get();
    }

    private function getTopSenders($countryID, $from, $to)
    {
        return GiftLog::select('sender_id')
            ->selectRaw('SUM(giftPrice) as total_sent')
            ->join('users', 'gift_logs.sender_id', '=', 'users.id')
            ->where('users.country_id', $countryID)
            ->whereBetween('gift_logs.created_at', [$from, $to])
            ->groupBy('sender_id')
            ->having('total_sent', '>', 0)
            ->orderByDesc('total_sent')
            ->limit(3)
            ->with([
                'sender:id,name,country_id',
                'sender.profile:id,user_id,avatar'
            ])
            ->get();
    }

    private function getTopReceivers($countryID, $from, $to)
    {
        return GiftLog::select('gift_logs.receiver_id')
            ->selectRaw('SUM(gift_logs.giftPrice) as total_sent')
            ->join('users', 'gift_logs.receiver_id', '=', 'users.id')
            ->where('users.country_id', $countryID)
            ->whereBetween('gift_logs.created_at', [$from, $to])
            ->groupBy('gift_logs.receiver_id')
            ->having('total_sent', '>', 0)
            ->orderByDesc('total_sent')
            ->limit(3)
            ->get()
            ->load([
                'receiver:id,name,country_id',
                'receiver.profile:id,user_id,avatar'
            ]);
    }

    private function getTopAgencies($countryID)
    {
        return Agency::select('id', 'name', 'img')
            ->where('country_id', $countryID)
            ->withCount('members')
            ->orderByDesc('members_count')
            ->limit(3)
            ->get();
    }

    private function getTopChargeAgencies($countryID, $from, $to)
    {
        return Charge::select('charges.*')
            ->where('charger_type', 'agency')
            ->join('agencies', 'charges.charger_id', '=', 'agencies.id')
            ->where('agencies.country_id', $countryID)
            ->whereBetween('charges.created_at', [$from, $to])
            ->with('senderShippingAgency:id,name,country_id,img')
            ->orderByDesc('amount')
            ->limit(3)
            ->get();
    }

    private function getTopBds($countryID)
    {
        return Bd::whereHas('agencies', function ($a) use ($countryID) {
            $a->where('country_id', $countryID)
                ->whereHas('members');
        })
            ->withCount(['agencies as total_members' => function ($agency) use ($countryID) {
                $agency->where('country_id', $countryID)
                    ->withCount('members');
            }])
            ->orderByDesc('total_members')
            ->take(3)
            ->get(['id', 'name']);
    }

    private function getTopGamers($countryID, $from, $to)
    {
        return CoinGameUser::select('coin_game_users.*')
            ->join('users', 'coin_game_users.user_id', '=', 'users.id')
            ->where('users.country_id', $countryID)
            ->where('coin_game_users.type', 1)
            ->whereBetween('coin_game_users.created_at', [$from, $to])
            ->with([
                'user:id,name,country_id',
                'user.profile:id,user_id,avatar'
            ])
            ->orderByDesc('coins')
            ->limit(3)
            ->get();
    }
}
