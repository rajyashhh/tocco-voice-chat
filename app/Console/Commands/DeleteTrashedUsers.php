<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteTrashedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-trashed-users';

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
        $trashedUsers = User::onlyTrashed()
            ->where('deleted_at', '<=', Carbon::now()->subMonth())
            ->get();

        $trashedUsers->each(function ($item){
           $item->forceDelete() ;
        });

       $this->info("Permanently deleted {$trashedUsers} user(s).");
    }
}
