<?php

namespace App\Observers;

use Modules\Vip\Entities\Vip;
use Illuminate\Validation\ValidationException;

class VipObserver
{
    /**
     * Strict-ascending guard on the shared Eloquent save point (grid inline
     * edits + create/edit forms). Level resolution is `where exp <= total
     * orderByDesc(exp)`, so within a type a higher level must always hold a
     * strictly higher exp.
     */
    public function saving(Vip $vip): void
    {
        if (! $vip->isDirty(['exp', 'level', 'type'])) {
            return;
        }

        $exp = (int) $vip->exp;
        $level = (int) $vip->level;
        $type = (int) $vip->type;

        $below = Vip::where('type', $type)->where('level', '<', $level)
            ->when($vip->exists, fn ($q) => $q->where('id', '!=', $vip->id))
            ->orderByDesc('level')->first();
        if ($below && (int) $below->exp >= $exp) {
            throw ValidationException::withMessages([
                'exp' => __('Exp must be strictly ascending: level :a already holds :x, so level :b needs more than that.', [
                    'a' => $below->level, 'x' => number_format($below->exp), 'b' => $level,
                ]),
            ]);
        }

        $above = Vip::where('type', $type)->where('level', '>', $level)
            ->when($vip->exists, fn ($q) => $q->where('id', '!=', $vip->id))
            ->orderBy('level')->first();
        if ($above && (int) $above->exp <= $exp) {
            throw ValidationException::withMessages([
                'exp' => __('Exp must be strictly ascending: level :a holds :x, so level :b must stay below that.', [
                    'a' => $above->level, 'x' => number_format($above->exp), 'b' => $level,
                ]),
            ]);
        }
    }

    public function saved(Vip $vip): void
    {
        Vip::flushLevelCaches();
    }

    public function deleted(Vip $vip): void
    {
        Vip::flushLevelCaches();
    }
}