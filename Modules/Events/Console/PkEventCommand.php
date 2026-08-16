<?php

namespace Modules\Events\Console;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Console\Command;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;

class PkEventCommand extends Command
{
    protected $signature = 'pk-event-update';

    protected $description = 'Command description';

    public function handle()
    {
        $pkEvent = PkEvent::endToday()->first();

        if ($pkEvent) {
            $newStartDate = Carbon::parse($pkEvent->getRawOriginal('end_date'))->toDateString();
            $today = Carbon::now(getTimezone())->toDateString();

            if ($newStartDate <= $today && !PkEvent::whereDate('start_date', $newStartDate)->exists()) {
                $this->createRound($pkEvent, $newStartDate);
            }
        }

        $this->catchUpMissedRounds();

        //        $this->info(now()->toDateTimeString() . ' ' . $this->signature . ' Run successful...');
    }

    protected function catchUpMissedRounds(): void
    {
        if (PkEvent::currentEvent()->exists()) {
            return;
        }

        $timezone = getTimezone();
        $today = Carbon::now($timezone)->startOfDay();

        $lastEvent = PkEvent::orderByDesc('end_date')->first();
        if (!$lastEvent) {
            return;
        }

        $source = $lastEvent;
        $cursorEnd = Carbon::parse($lastEvent->getRawOriginal('end_date'))->startOfDay();

        while ($cursorEnd->lt($today)) {
            $newStartDate = $cursorEnd->toDateString();

            $existing = PkEvent::whereDate('start_date', $newStartDate)->first();
            if ($existing) {
                $source = $existing;
                $cursorEnd = Carbon::parse($existing->getRawOriginal('end_date'))->startOfDay();
                continue;
            }

            $newRound = $this->createRound($source, $newStartDate);
            $source = $newRound;
            $cursorEnd = Carbon::parse($newRound->getRawOriginal('end_date'))->startOfDay();
        }
    }

    protected function createRound(PkEvent $sourceEvent, string $newStartDate): PkEvent
    {
        $newPkEvent = new PkEvent([
            'admin_id' => $sourceEvent->admin_id,
            'start_date' => $newStartDate,
            'end_date' => Carbon::parse($newStartDate)->addWeek()->toDateString(),
            'editor_id' => $sourceEvent->editor_id,
        ]);
        $newPkEvent->save();

        $this->repeatRewards($sourceEvent, $newPkEvent->id);

        return $newPkEvent;
    }

    public function repeatRewards(PkEvent $pkEvent, int $pkEventNewId)
    {
        $columns         = [
            "pk_event_id",
            "type",
            "level",
            "target",
            "pk_type",
            "expire",
            "created_at",
            "updated_at",
        ];
        $previousRewards = $pkEvent->rewards()->get($columns)->toArray();

        $reward  = new PkReward;
        $appends = $reward->getAppends();
        foreach ($previousRewards as &$previousReward) {
            $previousReward['pk_event_id'] = $pkEventNewId;
            $previousReward['created_at'] = now();
            $previousReward['updated_at'] = now();

            foreach ($appends as $append) {
                unset($previousReward[$append]);
            }
        }

        PkReward::query()->insert($previousRewards);
        //        $this->info($this->signature . ' Run successfully');
    }
}