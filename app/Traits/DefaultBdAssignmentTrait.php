<?php

namespace App\Traits;

use App\Models\Bd;

trait DefaultBdAssignmentTrait
{
    public static function bootDefaultBdAssignmentTrait()
    {
        static::creating(function ($model) {
            self::assignDefaultBd($model);
        });

        static::updating(function ($model) {
            self::assignDefaultBd($model);
        });
    }

    /**
     * Resolve the default BD for the model's country when no BD is set.
     *
     * A country is only "assigned" to a tenancy once it has a country_id; pending
     * agency applications created via the app (AgencyService::create) legitimately
     * have country_id = null and no BD yet, so those are left untouched. Once a
     * country IS set but has no default BD, we fail loudly instead of leaving the
     * agency orphaned — matching the panel (AgencyController), which throws
     * dashboard.no_default_bd_to_transfer_agencies in the same situation. This
     * closes the silent-orphan path that the API previously allowed.
     */
    protected static function assignDefaultBd($model): void
    {
        if (!empty($model->bd_id)) {
            return;
        }

        if (empty($model->country_id)) {
            return;
        }

        $defaultBd = Bd::where('type', 'bd')
            ->where('default', true)
            ->where('country_id', $model->country_id)
            ->first();

        if ($defaultBd) {
            $model->bd_id = $defaultBd->id;
            return;
        }

        throw new \Exception(__('dashboard.no_default_bd_to_transfer_agencies'));
    }
}
