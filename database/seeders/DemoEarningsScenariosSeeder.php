<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\User;
use App\Models\UserSallary;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo-only earnings history for the "أرباح المستخدمين" / salary pages.
 *
 * Deliberately NOT registered in DatabaseSeeder so a sold clone stays clean.
 * Run it by hand against a demo database:
 *
 *   php artisan db:seed --class=DemoEarningsScenariosSeeder --force
 *
 * It reads REAL demo hosts and their agencies from the DB (never invents ids,
 * so no FK dangles), then writes internally-consistent rows:
 *   user_sallaries.sallary        = host gross for the month (USD)
 *   user_sallaries.cut_amount     = already paid out; net = sallary - cut_amount
 *   user_sallaries.agency_sallary = the agency's obtain on that host's gross
 *   agency_sallaries.sallary      = SUM of that agency's per-host agency_sallary
 *                                   for the same month/year (kept in sync here)
 *
 * Idempotent: every write is firstOrCreate keyed on the natural composite
 * (host+agency+month+year for hosts, agency+month+year for agencies), so a
 * re-run is a no-op and it never overwrites a real salary row the demo may
 * already have for the current month.
 */
class DemoEarningsScenariosSeeder extends Seeder
{
    /** cap so a large demo user table can't explode the generated history. */
    private const MAX_HOSTS = 15;

    public function run(): void
    {
        // Real demo hosts that actually belong to an agency.
        $hosts = User::query()
            ->where('is_host', 1)
            ->whereNotNull('agency_id')
            ->where('agency_id', '>', 0)
            ->orderBy('id')
            ->limit(self::MAX_HOSTS)
            ->get(['id', 'agency_id']);

        if ($hosts->isEmpty()) {
            $this->command?->warn('DemoEarningsScenariosSeeder: no demo hosts with an agency found — nothing seeded.');
            return;
        }

        // Keep only hosts whose agency really exists (Agency carries a global
        // host-agency scope, type=1); otherwise agency_sallaries would point at
        // a missing parent.
        $existingAgencyIds = array_flip(
            Agency::query()
                ->whereIn('id', $hosts->pluck('agency_id')->unique()->all())
                ->pluck('id')
                ->all()
        );

        // Current month, previous months, and prior years — a real timeline.
        $now = Carbon::now();
        $periods = [
            $now->copy(),
            $now->copy()->subMonthNoOverflow(),
            $now->copy()->subMonthsNoOverflow(2),
            $now->copy()->subYearNoOverflow(),
            $now->copy()->subYearsNoOverflow(2),
        ];

        // gross USD, share already paid out, and the agency's commission ratio.
        // net = gross - (gross * paidRatio); agency_sallary = gross * agencyRatio.
        $scenarios = [
            ['gross' => '1200.00', 'paidRatio' => '1.00', 'agencyRatio' => '0.20', 'is_paid' => 1], // hit target, settled
            ['gross' => '860.50',  'paidRatio' => '0.50', 'agencyRatio' => '0.15', 'is_paid' => 0], // gifts/support, half paid
            ['gross' => '430.00',  'paidRatio' => '0.00', 'agencyRatio' => '0.12', 'is_paid' => 0], // steady, unpaid
            ['gross' => '150.25',  'paidRatio' => '1.00', 'agencyRatio' => '0.10', 'is_paid' => 1], // below target, settled
            ['gross' => '2050.75', 'paidRatio' => '0.80', 'agencyRatio' => '0.18', 'is_paid' => 0], // top earner, mostly paid
        ];
        $scenarioCount = count($scenarios);

        DB::transaction(function () use ($hosts, $existingAgencyIds, $periods, $scenarios, $scenarioCount) {
            // agency:month:year => running agency_sallary sum + a period timestamp.
            $agencyTotals = [];

            foreach ($hosts as $hostIndex => $host) {
                if (!isset($existingAgencyIds[$host->agency_id])) {
                    continue;
                }

                foreach ($periods as $periodIndex => $period) {
                    $scenario = $scenarios[($hostIndex + $periodIndex) % $scenarioCount];

                    $gross      = $scenario['gross'];
                    $cutAmount  = bcmul($gross, $scenario['paidRatio'], 2);
                    $agencyGain = bcmul($gross, $scenario['agencyRatio'], 2);

                    $month = (int) $period->month;
                    $year  = (int) $period->year;
                    // A timestamp inside the period so created_at-based reports match.
                    $ts = $period->copy()->startOfMonth()
                        ->addDays(min(14, max(0, $period->day - 1)))
                        ->setTime(12, 0, 0);

                    UserSallary::firstOrCreate(
                        [
                            'user_id'        => $host->id,
                            'user_agency_id' => $host->agency_id,
                            'month'          => $month,
                            'year'           => $year,
                        ],
                        [
                            'hours'          => '160 / 160',
                            'days'           => '26 / 26',
                            'sallary'        => (float) $gross,
                            'agency_sallary' => (float) $agencyGain,
                            'cut_amount'     => (float) $cutAmount,
                            'is_paid'        => $scenario['is_paid'],
                            'created_at'     => $ts,
                            'updated_at'     => $ts,
                        ]
                    );

                    $key = $host->agency_id . ':' . $month . ':' . $year;
                    if (!isset($agencyTotals[$key])) {
                        $agencyTotals[$key] = [
                            'agency_id' => $host->agency_id,
                            'month'     => $month,
                            'year'      => $year,
                            'sallary'   => '0.00',
                            'ts'        => $ts,
                        ];
                    }
                    $agencyTotals[$key]['sallary'] = bcadd($agencyTotals[$key]['sallary'], $agencyGain, 2);
                }
            }

            // One agency_sallaries row per agency/month/year, sallary = SUM of the
            // per-host agency_sallary above (consistent), with part already cut.
            foreach ($agencyTotals as $total) {
                $agencyCut = bcmul($total['sallary'], '0.30', 2);
                AgencySallary::firstOrCreate(
                    [
                        'agency_id' => $total['agency_id'],
                        'month'     => $total['month'],
                        'year'      => $total['year'],
                    ],
                    [
                        'sallary'    => (float) $total['sallary'],
                        'cut_amount' => (float) $agencyCut,
                        'is_paid'    => 0,
                        'created_at' => $total['ts'],
                        'updated_at' => $total['ts'],
                    ]
                );
            }
        });

        $this->command?->info('DemoEarningsScenariosSeeder: seeded demo earnings for ' . $hosts->count() . ' host(s).');
    }
}
