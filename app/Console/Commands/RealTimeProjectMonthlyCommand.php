<?php

namespace App\Console\Commands;

use App\Models\RealtimeProject;
use App\Models\User;
use App\Traits\Salaries\UserSalaryTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealTimeProjectMonthlyCommand extends Command
{
    use UserSalaryTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:realtime-project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update realtime project after 30 days';

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

        // Get projects for current month and year
        $realtimeProjects = RealtimeProject::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->get();


        if ($realtimeProjects->isEmpty()) {

            $previousMonth = Carbon::now()->subMonth();

            $realtimeProjects = RealtimeProject::whereBetween('created_at', [$previousMonth->copy()->startOfMonth(), $previousMonth->copy()->endOfMonth()])
                ->get();

            $realtimeAudio = $realtimeProjects->where('type', 'audio')->first();
            $realtimeVideo = $realtimeProjects->where('type', 'video')->first();

            if ($realtimeAudio) {
                RealtimeProject::create([
                    'type' => 'audio',
                    'balance' => $realtimeAudio->balance - $realtimeAudio->used,
                ]);
            }else{
                RealtimeProject::create([
                    'type' => 'audio',
                ]);
            }


            if ($realtimeVideo) {
                RealtimeProject::create([
                    'type' => 'video',
                    'balance' => $realtimeVideo->balance - $realtimeVideo->used,
                ]);
            }else{
                RealtimeProject::create([
                    'type' => 'video',
                ]);
            }
        }
    }
}
