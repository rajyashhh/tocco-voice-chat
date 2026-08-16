<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\UsersJoinedAgency;
use Illuminate\Database\Seeder;

class UserJoinAgency extends Seeder
{

    public function run(): void
    {
        $agencies = Agency::whereHas('owner', function ($q) {
            $q->whereDoesntHave('latestJoin');
        })->get();

        foreach ($agencies as $agency) {
            $owner = $agency->owner;
            if ($owner) {
                $exists = UsersJoinedAgency::where([
                    'user_id' =>  $owner->id,
                    'agency_id' => $agency->id,
                    'type' => 1,
                ])->whereNull('leave_date')->exists();

                if (!$exists) {
                    UsersJoinedAgency::create([
                        'user_id' => $owner->id,
                        'agency_id' => $agency->id,
                        'type' => 1,
                        'join_date' => $agency->created_at ?? now(),
                        'status' => 'Joined',
                    ]);
                }
            }
        }
    }
}
