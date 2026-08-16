<?php

namespace App\Helpers;

use App\Models\ShippingAgency;
use App\Models\ShippingSuperAdmin;
use Modules\Country\Entities\SuperAdmin;

/**
 * Server-side, fail-closed scope checks for the shipping super admin hierarchy:
 *
 *     Country Manager  ->  Shipping Super Admin  ->  Shipping Agency
 *
 * Every check returns the in-scope target model or null. Callers MUST treat null
 * as "forbidden" and abort the money move — never fall back to an unscoped
 * lookup. All resolutions are keyed off the AUTHENTICATED actor passed in by the
 * caller; nothing here is read from the request.
 */
class ShippingScopeHelper
{
    /**
     * A Country Manager may fund a Shipping Super Admin only when that target is
     * its own child (parent_id) AND sits in the same country. Fail-closed: a
     * country manager with no country, or a target outside it, yields null.
     */
    public static function fundableShippingSuperAdmin(SuperAdmin $countryManager, $targetId): ?ShippingSuperAdmin
    {
        $countryId = (int) $countryManager->country_id;
        if ($countryId <= 0) {
            return null;
        }

        $target = ShippingSuperAdmin::find($targetId);
        if (!$target) {
            return null;
        }

        if ((int) $target->parent_id !== (int) $countryManager->id) {
            return null;
        }

        if ((int) $target->country_id !== $countryId) {
            return null;
        }

        return $target;
    }

    /**
     * A Shipping Super Admin may charge a Shipping Agency only when the agency is
     * a shipping agency (type=2, enforced by the ShippingAgency model scope) that
     * sits in the same country. Fail-closed on a missing country or mismatch.
     */
    public static function chargeableAgency(ShippingSuperAdmin $shippingSuperAdmin, $agencyId): ?ShippingAgency
    {
        $countryId = (int) $shippingSuperAdmin->country_id;
        if ($countryId <= 0) {
            return null;
        }

        $agency = ShippingAgency::find($agencyId);
        if (!$agency) {
            return null;
        }

        if ((int) $agency->country_id !== $countryId) {
            return null;
        }

        return $agency;
    }
}