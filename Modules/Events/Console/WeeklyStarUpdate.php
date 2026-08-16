<?php

namespace Modules\Events\Console;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\Reward;
use Modules\Events\Entities\WeeklyStar;

class WeeklyStarUpdate extends Command
{
    protected $signature = 'weekly-star-update';

    protected $description = 'Command description';

    public function handle()
    {
        $timezone = Common::timeZone();
        $today = Carbon::now($timezone)->toDateString();

        $lastEndDate = WeeklyStar::weeklyStar()->max('end_date');
        if (!$lastEndDate) {
            return '';
        }

        $cursor = Carbon::parse($lastEndDate)->toDateString();
        if ($cursor > $today) {
            return '';
        }

        $template = WeeklyStar::weeklyStar()
            ->with('gifts')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        while ($cursor <= $today) {
            $exists = WeeklyStar::weeklyStar()->whereDate('start_date', $cursor)->exists();

            if (!$exists) {
                $newWeeklyStar = WeeklyStar::create([
                    'admin_id'   => $template->admin_id,
                    'start_date' => $cursor,
                    'end_date'   => Carbon::parse($cursor)->addWeek()->toDateString(),
                    'editor_id'  => $template->editor_id,
                    'type'       => $template->type,
                ]);

                $gifts = $template->gifts->map(function ($gift) use ($newWeeklyStar) {
                    return [
                        'weekly_star_id' => $newWeeklyStar->id,
                        'gift_id'        => $gift->id,
                    ];
                })->toArray();

                if (!empty($gifts)) {
                    DB::table('weekly_star_gifts')->insert($gifts);
                }
                $this->repeatRewards($template, $newWeeklyStar->id);
            }

            $cursor = Carbon::parse($cursor)->addWeek()->toDateString();
        }
    }

    public function repeatRewards(WeeklyStar $weeklyStar, int $weeklyStarNewId)
    {
        $columns         = [
            "weekly_star_id",
            "type",
            "level",
            "target",
            "created_at",
            "updated_at",
            "expire",
        ];
        $previousRewards = $weeklyStar->rewards()->get($columns)->toArray();

        $reward  = new Reward;
        $appends = $reward->getAppends();
        foreach ($previousRewards as &$previousReward) {
            $previousReward['weekly_star_id'] = $weeklyStarNewId;
            $previousReward['created_at']     = now();
            $previousReward['updated_at']     = now();

            foreach ($appends as $append) {
                unset($previousReward[$append]);
            }
        }

        Reward::query()->insert($previousRewards);
    }
}