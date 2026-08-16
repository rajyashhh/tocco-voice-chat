<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Vip\Entities\Vip;

class LevelCurveService
{
    public const TYPES = [1, 2, 3, 4, 5];

    /** Signed bigint headroom guard for the vips.exp column. */
    private const MAX_EXP = 9_000_000_000_000_000_000;

    /**
     * Build a cumulative exp curve: level 1 = $firstExp, every next threshold
     * grows by $growthPct percent ($lateGrowthPct after the midpoint of the
     * ladder). Values are rounded to clean numbers while staying strictly
     * ascending (the vips lookup is `where exp <= total orderByDesc(exp)`).
     *
     * @return array<int,int> level => cumulative exp threshold
     */
    public function generate(int $levels, int $firstExp, float $growthPct, ?float $lateGrowthPct = null): array
    {
        $lateGrowthPct = $lateGrowthPct ?? $growthPct;
        $midpoint = (int) ceil($levels / 2);

        $curve = [];
        $raw = (float) $firstExp;
        $prev = 0;

        for ($level = 1; $level <= $levels; $level++) {
            if ($level > 1) {
                $rate = $level <= $midpoint ? $growthPct : $lateGrowthPct;
                $raw *= 1 + $rate / 100;
            }

            if ($raw > self::MAX_EXP) {
                throw ValidationException::withMessages([
                    'growth' => __('The curve overflows at level :level — lower the growth rate or the level count.', ['level' => $level]),
                ]);
            }

            $value = max($prev + 1, $this->roundClean($raw));
            $curve[$level] = $value;
            $prev = $value;
        }

        return $curve;
    }

    /**
     * Write the generated curve into vips rows of one type.
     * Existing rows keep their images; missing levels are created with an
     * empty image the admin uploads later. Levels above the curve length are
     * left untouched but must stay consistent with the new curve.
     *
     * @return array{updated:int,created:int}
     */
    public function apply(int $type, array $curve): array
    {
        $result = DB::transaction(function () use ($type, $curve) {
            $existing = DB::table('vips')->where('type', $type)->lockForUpdate()->orderBy('level')->get();

            $byLevel = [];
            foreach ($existing as $row) {
                if (isset($byLevel[$row->level])) {
                    throw ValidationException::withMessages([
                        'type' => __('Duplicate rows exist for level :level of this type — clean them from the levels grid first.', ['level' => $row->level]),
                    ]);
                }
                $byLevel[$row->level] = $row;
            }

            // Rows outside the curve range (a level-0 row, or levels above the
            // curve length) are kept as-is but must stay consistent with the
            // new thresholds.
            $merged = $curve;
            foreach ($byLevel as $level => $row) {
                if (! isset($curve[$level])) {
                    $merged[$level] = (int) $row->exp;
                }
            }
            ksort($merged);
            $this->assertStrictlyAscending($merged);

            // Phase 1: park the affected rows on collision-free negative values,
            // because (type, exp) is unique and the new thresholds can interleave
            // with the old ones mid-rewrite.
            $rewriteIds = array_map(
                fn ($level) => $byLevel[$level]->id,
                array_filter(array_keys($curve), fn ($level) => isset($byLevel[$level]))
            );
            if ($rewriteIds) {
                DB::table('vips')->whereIn('id', $rewriteIds)->update(['exp' => DB::raw('-id')]);
            }

            $now = now();
            $updated = 0;
            $created = 0;

            foreach ($curve as $level => $exp) {
                if (isset($byLevel[$level])) {
                    DB::table('vips')->where('id', $byLevel[$level]->id)
                        ->update(['exp' => $exp, 'updated_at' => $now]);
                    $updated++;
                } else {
                    DB::table('vips')->insert([
                        'type'       => $type,
                        'level'      => $level,
                        'exp'        => $exp,
                        'img'        => '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $created++;
                }
            }

            return ['updated' => $updated, 'created' => $created];
        });

        Vip::flushLevelCaches();

        return $result;
    }

    /**
     * Guard shared by the generator: thresholds must be strictly ascending by
     * level within a type, otherwise level resolution becomes ambiguous.
     *
     * @param array<int,int> $expByLevel level => exp, ksorted
     */
    public function assertStrictlyAscending(array $expByLevel): void
    {
        $prevLevel = null;
        $prevExp = null;

        foreach ($expByLevel as $level => $exp) {
            if ($prevExp !== null && $exp <= $prevExp) {
                throw ValidationException::withMessages([
                    'exp' => __('Exp must be strictly ascending: level :b (:y) does not exceed level :a (:x). Regenerate with more levels or remove the conflicting rows.', [
                        'a' => $prevLevel,
                        'x' => number_format($prevExp),
                        'b' => $level,
                        'y' => number_format($exp),
                    ]),
                ]);
            }
            $prevLevel = $level;
            $prevExp = $exp;
        }
    }

    /** Round to ~3 significant digits so thresholds read clean inside the app. */
    private function roundClean(float $value): int
    {
        if ($value < 1000) {
            return (int) round($value);
        }

        $magnitude = 10 ** ((int) floor(log10($value)) - 2);

        return (int) (round($value / $magnitude) * $magnitude);
    }
}
