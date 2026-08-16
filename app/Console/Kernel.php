<?php

namespace App\Console;

use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\TaskStream\Jobs\PkSessionJob;
use Illuminate\Console\Scheduling\Schedule;
use Modules\CP\Console\WeeklyCpWinnerConsole;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\UpdateRoomUserNowCron::class,
        Commands\OpenStatusAppFeature::class,
        Commands\CloseStatusAppFeature::class,
        Commands\DeleteTrashedUsers::class,
        Commands\FreezeUsersCommand::class,
        Commands\SyncRoomOccupancy::class,
        Commands\UpdateAgencyMonthlyActivityCommand::class,
        WeeklyCpWinnerConsole::class
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('remove-background:cron')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/remove-background-cron.log'))
            ->runInBackground();

        $schedule->command('users:reset-monthly-diamond')
            ->monthly()
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/users-reset-monthly-diamond.log'))
            ->runInBackground();

        $schedule->command('users:reset-monthly-days')
            ->monthlyOn(1, '00:00')
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/users-reset-monthly-days.log'))
            ->runInBackground();

        $schedule->command('users:reset-today-days')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/users-reset-today-days.log'))
            ->runInBackground();

        $schedule->command('update-gift-weakly:cron')
            ->weeklyOn(0, '00:00')
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/update-gift-weekly-cron.log'))
            ->runInBackground();

        $schedule->command('app:reset-top-room-rank')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/app-reset-top-room-rank.log'))
            ->runInBackground();

        $schedule->command('redis:get_data')
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/redis-get-data.log'))
            ->runInBackground();

        $schedule->command('update-room-ban')
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/update-room-ban'))
            ->runInBackground();


        $schedule->command('weekly-star-winner')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/weekly-star-winner.log'))
            ->runInBackground();

        $schedule->command('weekly-star-update')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/weekly-star-update.log'))
            ->runInBackground();

        $schedule->command('pk-event-winner')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pk-event-winner.log'))
            ->runInBackground();

        $schedule->command('pk-event-update')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pk-event-update.log'))
            ->runInBackground();

        $schedule->command('charge-king-winner')
            ->monthlyOn(1, '00:10')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/charge-king-winner.log'))
            ->runInBackground();


        $schedule->command('app:update-game-wallet')
            ->monthlyOn(1, '00:00')
            ->timezone('UTC')
            ->appendOutputTo(storage_path('logs/app-update-game-wallet.log'))
            ->runInBackground();

        $schedule->command('users:update-salaries')
            ->everyTenMinutes()
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/update-user-salaries.log'))
            ->runInBackground();


        // $schedule->command('game:user-calc')
        //     ->monthly()
        //     ->timezone(getTimezone())
        //     ->appendOutputTo(storage_path('logs/game-user-calc.log'))
        //     ->runInBackground();


        // Unified ranking safety-net: rebuild the CURRENT bucket of every
        // (section, period) Redis sorted set from the DB source-of-truth, so the
        // real-time RankingScoreService::add() path self-heals after a Redis
        // flush/deploy and never drifts for long. Atomic temp-key + RENAME per
        // bucket (no double-count). Also covers the games section.
        $schedule->command('ranking:backfill')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/ranking-backfill.log'))
            ->runInBackground();

        $schedule->command('weekly-cp-winner')
            ->weekly()
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/weekly-cp-winner.log'))
            ->runInBackground();

        $schedule->command('coin_game:archive')
            ->dailyAt('07:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('coin-game:aggregate')
            ->dailyAt('07:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->runInBackground();

        $this->scheduleRoomCupRewards($schedule);


        $schedule->command('queue:prune-failed --hours=720')
            ->daily()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/prune-failed-jobs.log'))
            ->runInBackground();

        $schedule->command('users:update-offline')
            ->everyThirtyMinutes()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->runInBackground();

        // Retention: trim high-churn log/visitor tables nightly so they stop
        // accumulating unbounded (admin_operation_log grew to 528K/112MB).
        $schedule->command('data:prune-stale')
            ->dailyAt('03:30')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/data-prune-stale.log'))
            ->runInBackground();

        // Drift guard for the O(1) daily report counters: rebuild today's Redis
        // hash + players SET from SQL every 10 min so any best-effort bump miss
        // self-heals (and is logged) within the window. Off-:00 to avoid the mark.
        $schedule->command('lucky:rebuild-day-counters --date=today')
            ->cron('3,13,23,33,43,53 * * * *')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lucky-rebuild-day-counters.log'))
            ->runInBackground();


        // $schedule->command('roomcup:calculate-rewards')->dailyAt('23:59');

        $schedule->call(function () {
            dispatch(new PkSessionJob());
        })
            ->name('pk-session-job')
            ->everyMinute()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pk_session_job.log'));

        $schedule->command('remaining-diamonds')
            ->monthly()
            ->timezone(getTimezone())
            ->appendOutputTo(storage_path('logs/remaining-diamonds.log'))
            ->runInBackground();

        $schedule->command('monthly-ranking')
            ->monthly()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/monthly-ranking.log'))
            ->runInBackground();

        $schedule->command('daily-ranking')
            ->dailyAt('00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/daily-ranking.log'))
            ->runInBackground();
        $weekEnd = Common::getSettingValue('week_start') ?? 'monday';

        // Convert string to Carbon constant
        $carbonDay = constant('Carbon\\Carbon::' . strtoupper($weekEnd));
        $schedule->command('weekly-ranking')
            ->weeklyOn($carbonDay, '00:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/weekly-ranking.log'))
            ->runInBackground();

        // Refresh the denormalized "most active agency this month" signal that the
        // agency listing orders by. Hourly keeps it fresh cheaply; the month-start
        // run rolls it over to the new month (last month's leaders reset to 0).
        $schedule->command('agency:update-monthly-activity')
            ->hourly()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/agency-monthly-activity.log'))
            ->runInBackground();

        $schedule->command('agency:update-monthly-activity')
            ->monthlyOn(1, '00:01')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/agency-monthly-activity-rollover.log'))
            ->runInBackground();

        $schedule->command('rooms:sync-occupancy')
            ->everyThirtySeconds()
            // 2-minute mutex expiry: the default (24h) let ONE hung run (UTD
            // Stream ListRooms stall) block every later run — stale "live"
            // rooms then accumulated for hours (125 found on 2026-06-11).
            ->withoutOverlapping(2)
            ->appendOutputTo(storage_path('logs/rooms-sync-occupancy.log'))
            ->runInBackground();

        $schedule->command('fairluck:sync-wallets')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/fairluck-sync-wallets.log'))
            ->runInBackground();

        $schedule->command('fairluck:reconcile')
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/fairluck-reconcile.log'))
            ->runInBackground();

        $schedule->command('lucky:reconcile-intents')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lucky-reconcile-intents.log'))
            ->runInBackground();

       $schedule->command('cleanup:fair-luck-transactions --days=30 --chunk=5000')
            ->dailyAt('03:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cleanup-fair-luck-transactions.log'))
            ->runInBackground();

        $schedule->command('cleanup:fair-luck-wallet-histories --days=30 --chunk=5000')
            ->dailyAt('04:00')
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cleanup-fair-luck-wallet-histories.log'))
            ->runInBackground();

        // Mark expired room bans as inactive every hour
        $schedule->job(new \App\Jobs\ExpireRoomBans)
            ->hourly()
            ->timezone(getTimezone())
            ->withoutOverlapping()
            ->name('expire-room-bans')
            ->appendOutputTo(storage_path('logs/expire-room-bans.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    private function scheduleRoomCupRewards(Schedule $schedule): void
    {
        $settings = $this->getRoomCupSettings();

        if (empty($settings['enabled'])) {
            return;
        }

        $type = $settings['type'] ?? 'daily';
        $time = '00:00';

        $weekStartDay = \App\helper\TimeHelper::startOfWeekConst();

        $command = $schedule->command('roomcup:calculate-rewards')
            ->timezone(getTimezone());

        match ($type) {
            'daily' => $command->dailyAt($time),
            'weekly' => $command->weeklyOn($weekStartDay, $time),
            'monthly' => $command->monthlyOn(1, $time),
            default => $command->dailyAt($time),
        };
    }

    private function getRoomCupSettings(): array
    {

        $default = [
            'enabled' => true,
            'interval_minutes' => 60,
            'type' => 'daily',
            'time' => '00:00',
        ];

        $settings = [];

        foreach ($default as $key => $defaultValue) {
            $cacheKey = 'roomcup_' . $key;

            $value = Cache::get($cacheKey);

            if ($value === null) {
                // Cold-boot safety: settings table is absent before migrations.
                $setting = Schema::hasTable('settings')
                    ? Setting::where('key', $cacheKey)->first()
                    : null;
                $value = $setting ? $setting->value : $defaultValue;

                Cache::put($cacheKey, $value, now()->addDays(30));
            }

            if ($key === 'enabled') {
                $value = (bool) $value;
            } elseif ($key === 'interval_minutes') {
                $value = (int) $value;
            }

            $settings[$key] = $value;
        }

        return $settings;
    }
}
