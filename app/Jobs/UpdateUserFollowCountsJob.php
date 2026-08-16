<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateUserFollowCountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updatedCount = 0;
    
        User::withCount(['followers', 'followeds', 'friendRelations'])
            ->chunk(200, function ($users) use (&$updatedCount) {
                foreach ($users as $user) {
                    $user->number_of_fans       = $user->followers_count;
                    $user->number_of_followings = $user->followeds_count; 
                    $user->number_of_friends    = $user->friend_relations_count; 
                    $user->save();
    
                    $updatedCount++;
                }
            });
    
    }
    
    
}
