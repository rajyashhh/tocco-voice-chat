<?php

namespace App\Console\Commands;

use App\Classes\Gifts\RoomJobFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HandlingRoomStreamRequests extends Command
{
    /**$instanceId
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:handling-room-stream-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roomFactory = new RoomJobFactory();
        while (true) {
            $this->withRedis($roomFactory);
            DB::disconnect();
        }
    }



    /**
     * @param RoomJobFactory $roomFactory
     * @return void
     */
    public function withRedis(RoomJobFactory $roomFactory): void
    {
        // Prevent multiple instances from processing the same Redis keys simultaneously
        $lock = \Illuminate\Support\Facades\Cache::lock('charisma_handler_lock', 30);

        $instanceId = gethostname() . ':' . getmypid();

        if (!$lock->get()) {
            sleep(5);
            return;
        }


        try {
            $this->processRedisKeys($roomFactory, $instanceId);
        } finally {
            $lock->release();
        }

        sleep(5);
    }

    private function processRedisKeys(RoomJobFactory $roomFactory, string $instanceId = 'unknown'): void
    {
        $data = Redis::keys('*CharismaGift*');

        $totalKeys      = count($data);
        $processedCount = 0;
        $failedCount    = 0;


        $allData = [];

        foreach ($data as $rKey) {
            $item = $rKey;
            $cleanKey = null;
            try {
                $cleanKey = str_replace(config('database.redis.options.prefix'), "", $item);

                // Drop stale backlog unprocessed. The consumer was offline for a
                // while (the legacy-provider cleanup removed it), so on the first run after redeploy
                // a large backlog of CharismaGift_* keys would otherwise be replayed
                // at once (DB increments + UTD-Stream sends) = a thundering-herd spike.
                // The key ends with "_<microtime>_<rand>" (see dispatchRoomsRedis()).
                $kp = explode('_', $cleanKey);
                if (count($kp) >= 2) {
                    $micro = (float) $kp[count($kp) - 2];
                    if ($micro > 0 && (microtime(true) - $micro) > 180) {
                        Redis::del($cleanKey);
                        continue;
                    }
                }

                $rawValue = Redis::get($cleanKey);


                $item = @unserialize($rawValue, ['allowed_classes' => false]);

                // Ensure $item is an array
                if ($item === false || !is_array($item)) {
                    Redis::del($cleanKey);
                    continue;
                }



                $item = new \App\Tik\DTO\RoomJobClass($item ?? []);

                $data_ne                = $roomFactory->setType($item->type)->work($item);
                $allData[$item->type][] = $data_ne;
                $processedCount++;


                echo 'Done ' . $item->type . ' to room ' . $item->room_id . PHP_EOL;
            } catch (\Throwable $e) {
                $failedCount++;
                $type = is_object($item) ? ($item->type ?? '?') : 'unknown';
                $room = is_object($item) ? ($item->room_id ?? '?') : 'unknown';



                echo 'Fail ' . $type . ' to room ' . $room . ': ' . $e->getMessage() . PHP_EOL;
            }
            Redis::del($cleanKey);

        }


        try {
            $streamCallCount = 0;
            foreach ($allData as $key => $allDatum) {
                $roomFactory->setType($key)->sendToStream($allData);
                $streamCallCount++;



                echo 'Done stream  ' . $key . ' to room ' . PHP_EOL;
            }
        } catch (\Exception $e) {
         
            echo 'Fail stream ' . $e->getMessage() . PHP_EOL;
        }
    }
}
