<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateRoomUserNowCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-room-user-now:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // No-op: room occupancy was previously reconciled from the Pusher presence
        // channel. Pusher has been removed; live room counts are now maintained by
        // the UTD-Stream participant webhooks and RoomOccupancyReconciler.
    }
}
