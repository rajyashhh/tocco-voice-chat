<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCountry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     $countryId = $request->get('filter_country_id');

    //     if ($request->has('filter_country_id')) {
    //         if ($countryId === null || $countryId === '' || $countryId === 'null') {
    //             session()->forget('filter_country_id');
    //         } else {
    //             session(['filter_country_id' => $countryId]);
    //         }
    //     }

    //     return $next($request);
    // }


      public function handle(Request $request, Closure $next): Response
    {
        $countryId = $request->get('filter_country_id');
        $shouldClearCountry = $request->get('clear_country');

        $areaManagerCountryId = $request->get('area_manager_country_id');
        $shouldClearAreaManagerCountry = $request->get('clear_area_manager_country');

        $areaManagerId = $request->get('area_manager_id');
        $shouldClear = $request->get('clear_area_manager');

        // A raw client value may never widen the authenticated admin's scope.
        // Each key is written only after it is confirmed to be inside that scope
        // (fail-closed): an out-of-scope value is ignored, not persisted.
        if ($shouldClearCountry == 1) {
            session()->forget('filter_country_id');
        } elseif ($countryId !== null && $countryId !== '' && $countryId !== 'null') {
            if (Common::isCountryInAdminScope($countryId)) {
                session(['filter_country_id' => $countryId]);
            } else {
                session()->forget('filter_country_id');
            }
        }

        if ($shouldClearAreaManagerCountry == 1) {
            session()->forget('area_manager_country_id');
        } elseif ($areaManagerCountryId !== null && $areaManagerCountryId !== '' && $areaManagerCountryId !== 'null') {
            if (Common::isCountryInAdminScope($areaManagerCountryId)) {
                session(['area_manager_country_id' => $areaManagerCountryId]);
            } else {
                session()->forget('area_manager_country_id');
            }
        }

        // Previewing another manager's scope is a super-admin-only capability.
        // A real (scoped) manager is always pinned to their own identity, so we
        // never persist a client-supplied area_manager_id for them.
        if ($shouldClear == 1) {
            session()->forget('area_manager_id');
        } elseif ($areaManagerId !== null && $areaManagerId !== '' && $areaManagerId !== 'null') {
            if (Common::canPreviewAreaManager()) {
                session(['area_manager_id' => $areaManagerId]);
            } else {
                session()->forget('area_manager_id');
            }
        }

        return $next($request);
    }
}
