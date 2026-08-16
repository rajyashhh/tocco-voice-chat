<?php

namespace App\Admin\Controllers\Concerns;

use App\Helpers\Common;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confines the single-record admin routes (show/edit/update/destroy) and the
 * create path of a country-scoped resource to the authenticated admin's real
 * country scope.
 *
 * Read/modify guard uses the EXACT same predicate as the grid — Common::
 * filterCountryIds() fed to the ->when($ids, fn($q) => $q->whereIn('country_id',
 * $ids)) idiom — so the list and the per-row actions can never diverge: a row a
 * scoped manager cannot see in the grid resolves to a 404 on /edit, /update,
 * /show and /destroy. Fail-closed by construction (findOrFail on an empty match).
 *
 * Create guard uses Common::isCountryInAdminScope(), the same authority check
 * SetCountry uses at the session source, so a scoped manager cannot mint a
 * record in a country outside their scope regardless of what the form posts.
 */
trait ScopesCountryRecords
{
    /**
     * Resolve a single record confined to the caller's country scope. Mirrors the
     * grid predicate exactly; throws ModelNotFoundException (404) when the id is
     * outside scope.
     */
    protected function findInCountryScope(string $modelClass, $id)
    {
        $ids = Common::filterCountryIds();

        return $modelClass::when($ids, fn ($query) => $query->whereIn('country_id', $ids))
            ->findOrFail($id);
    }

    /**
     * Country ids the caller may create records in. Empty means unrestricted
     * (super / non-manager staff), matching the grid's [] = no restriction.
     */
    protected function creatableCountryIds(): array
    {
        return Common::areaCountries();
    }

    /**
     * Reject a create/update that targets a country outside the caller's scope.
     * Returns a redirect-back Response to be returned from a Form::saving()
     * callback (which short-circuits the save), or null when in scope.
     */
    protected function rejectCountryOutOfScope($countryId): ?Response
    {
        if (Common::isCountryInAdminScope($countryId)) {
            return null;
        }

        return back()->withInput()->withErrors(new MessageBag([
            'country_id' => [__('not allowed')],
        ]));
    }
}
