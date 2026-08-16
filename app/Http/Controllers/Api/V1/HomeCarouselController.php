<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\HomeCarouselResource;
use App\Models\HomeCarousel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeCarouselController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $displayType = $request->get('display_at');
        $timezone = getTimezone();
        $now = Carbon::now($timezone);
        $offset = $now->format('P');
        $country = request('country_id');
        
        if ($request->hasHeader('x-notification-id') && $user->notification_id !== $request->header('x-notification-id')) {
            $user->update(['notification_id' => $request->header('x-notification-id')]);
        }

        // Cache for 2 minutes (120 seconds). The version stamp is bumped by the
        // admin panel on every banner save/toggle, so a panel change invalidates
        // EVERY placement/country variant instantly — banners must appear in the
        // app the moment the admin saves, not up to 2 minutes later.
        $cacheKey = 'home_carousels_' . md5(json_encode([
            'ver' => Cache::get('home_carousels_ver', '0'),
            'display_type' => $displayType,
            'country' => $country,
            'type' => $request->type,
            'category' => $request->category,
        ]));

        $items = Cache::remember($cacheKey, 120, function () use ($displayType, $now, $offset, $country, $request) {
            return HomeCarousel::query()
                ->with([
                    'user',
                    'room',
                    'generalRole',
                    'countriesLite',
                    'displays',
                    'user.ownerAudioRoom.pks' => fn($q) =>
                    $q->latest('created_at')->limit(2),
                ])
                ->where('enable', 1)

                ->when($displayType, function ($q) use ($displayType, $now, $offset) {
                    $q->whereHas('displays', function ($sub) use ($displayType, $now, $offset) {
                        $sub->where('display_type', $displayType)->where('status', 1)
                            ->where(function ($inner) use ($now, $offset) {
                                $inner->whereRaw("
                                    CONVERT_TZ(end_at, '+00:00', ?) > ?
                                    ", [$offset, $now])
                                    ->orWhere('duration', 0)
                                    ->orWhereNull('end_at');
                            });
                    });
                })
                ->when($country, function ($q) use ($country, $now, $offset) {
                    $q->whereHas('countries', function ($sub) use ($country) {
                        $sub->where('country_id', $country);
                    })
                        ->whereHas('displays', function ($sub) use ($now, $offset) {
                            $sub->where('display_type', 'country')
                                ->where(function ($inner) use ($now, $offset) {
                                    $inner->whereRaw("
                                    CONVERT_TZ(end_at, '+00:00', ?) > ?
                                ", [$offset, $now])
                                        ->orWhere('duration', 0)
                                        ->orWhereNull('end_at');
                                });
                        });
                })
                ->when($request->type, fn($q) => $q->where('type', $request->type))
                ->when($request->category === 'charge_event', fn($q) => $q->where('event_type', 'charge_event'))
                ->orderBy('sort')
                ->get();
        });

        return Common::apiResponse(1, '', HomeCarouselResource::collection($items));
    }
}
