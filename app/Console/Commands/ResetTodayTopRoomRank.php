<?php

namespace App\Console\Commands;

use App\Models\RoomTopUser;
use Illuminate\Console\Command;

class ResetTodayTopRoomRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-top-room-rank';

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
        RoomTopUser::query()->update(['coins' => 0]);

        \DB::statement("
            UPDATE rooms
            SET  top_user_id = null
        ");

       $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }
}
