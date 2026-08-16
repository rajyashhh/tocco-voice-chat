<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Agency;
use App\Models\GiftLog;
use App\Models\UserSallary;
use App\Models\UserTarget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgencyDataSeeder extends Seeder
{
    public function run()
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $agency = Agency::find(43);

        // Update UserSallary and related user
        $userSalaries = UserSallary::with('user')->take(10)->get();
        foreach ($userSalaries as $salary) {
            $salary->update([
                'user_agency_id' => $agency->id,
                'month' => $month,
                'year' => $year,
            ]);

            $salary->user?->update([
                'agency_id' => $agency->id,
            ]);
        }

        // Update GiftLogs and related sender/receiver
        $gifts = GiftLog::with('sender', 'receiver')->take(10)->get();
        foreach ($gifts as $gift) {
            $gift->update([
                'agency_id' => $agency->id,
                'created_at' => now(),
            ]);

            $gift->sender?->update(['agency_id' => $agency->id]);
            $gift->receiver?->update(['agency_id' => $agency->id]);
        }

        // Update UserTargets and related users
        $newTargets = UserTarget::with('user')->orderByDesc('id')->take(20)->get();

        // Get older 20 targets (old)
        $oldTargets = UserTarget::with('user')->orderBy('id')->take(20)->get();

        foreach ($newTargets as $index => $newTarget) {
            $oldTarget = $oldTargets[$index] ?? null;

            if ($oldTarget) {
                // Update newTarget with oldTarget's user_id, set agency and created_at
                $newTarget->update([
                    'user_id' => $oldTarget->user_id,
                    'agency_id' => $agency->id,
                    'created_at' => Carbon::now()->subMonth(),
                ]);

                // Optionally also update the related user's agency
                $newTarget->user?->update([
                    'agency_id' => $agency->id,
                ]);
            }
        }
    }
}


