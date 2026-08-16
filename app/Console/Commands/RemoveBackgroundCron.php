<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Room;
use App\Models\Background;
use App\Models\RequestBackgroundImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class RemoveBackgroundCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove-background:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove background personal room after 30 day';

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

        $bgfirst = Background::first();
        $RequestBackgroundImage = RequestBackgroundImage::whereIn('status',[1,3])->where(function ($q) {
                    $q->where('expair', '<', now()->timestamp)
                        ->orWhere('expair', 0);
                })->get();
        $owner_ids =  $RequestBackgroundImage->pluck('owner_room_id');
        $rooms = Room::whereIn('uid',$owner_ids)->update([
            'room_background' => $bgfirst ? $bgfirst->id : null
        ]);
        foreach($RequestBackgroundImage as $img){
            $path = $img->img;
            if(Storage::exists(Storage::disk('images')->path($path))){
                unlink(Storage::disk('images')->path($path));
            }
            $img->delete();
        }
//        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');

        $this->info('update-room-user-now:cron Command Run Successfully !');
    }
}
