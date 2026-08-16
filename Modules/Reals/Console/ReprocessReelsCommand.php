<?php

namespace Modules\Reals\Console;

use Illuminate\Console\Command;
use Modules\Reals\Entities\Real;
use Modules\Reals\Jobs\ProcessReelVideoJob;

class ReprocessReelsCommand extends Command
{
    protected $signature = 'reels:reprocess
        {--id=* : Only these reel ids}
        {--all : Re-queue every reel regardless of status}
        {--sync : Run the pipeline inline instead of queueing}';

    protected $description = 'Re-run the reel transcode pipeline (backfill legacy rows / repair broken reels)';

    public function handle(): int
    {
        $query = Real::query();

        $ids = array_filter((array) $this->option('id'));
        if ($ids) {
            $query->whereIn('id', $ids);
        } elseif (!$this->option('all')) {
            // Default: everything not confirmed playable (legacy rows have the
            // migration default 'processing'; failed rows get retried too).
            $query->where('status', '!=', Real::STATUS_READY);
        }

        $count = 0;
        foreach ($query->orderBy('id')->pluck('id') as $realId) {
            Real::query()->whereKey($realId)->update(['status' => Real::STATUS_PROCESSING, 'fail_reason' => null]);

            if ($this->option('sync')) {
                try {
                    (new ProcessReelVideoJob($realId))->handle();
                    $status = Real::query()->whereKey($realId)->value('status');
                    $this->line("reel {$realId}: {$status}");
                } catch (\Throwable $e) {
                    Real::query()->whereKey($realId)->update([
                        'status'      => Real::STATUS_FAILED,
                        'fail_reason' => mb_substr($e->getMessage(), 0, 500),
                    ]);
                    $this->error("reel {$realId}: failed — {$e->getMessage()}");
                }
            } else {
                ProcessReelVideoJob::dispatch($realId)->onQueue('optimization-images');
                $this->line("reel {$realId}: queued");
            }
            $count++;
        }

        $this->info("Dispatched {$count} reel(s).");

        return self::SUCCESS;
    }
}