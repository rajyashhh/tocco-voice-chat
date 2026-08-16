<?php

namespace Modules\SalaryTransaction\Console;

use App\Models\AgencySallary;
use DB;
use Illuminate\Console\Command;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Modules\SalaryTransaction\Entities\AgencyTransferSalary;
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PendingRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'SalaryTransaction:pending-requests';

    /**
     * The console command description.
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestSalaries = SalaryRequest::with("host")->where("status",2)->where("updated_at", "<=", now()->subHours(48))->get();
        if ($requestSalaries != null) {
            foreach ($requestSalaries as $requestSalary) {
                $requestSalary->status = 3;
                $requestSalary->host_check = 0;
                $requestSalary->save();

                PendingSalaryRequest::where([
                    "user_id" => $requestSalary->host_id,
                    "type" => 'salary_transaction',
                ])->delete();
    
                AgencyTransferSalary::updateOrCreate([
                    'agency_id' => $requestSalary->agency_id,
                    "month" => date("m"),
                    "year" => date("Y"),
                ],[
                    'salary' => DB::raw('salary + ' . $requestSalary->usd),
                ]);
                // add salary to agency
                // AgencySallary::updateOrCreate([
                //     'agency_id' => $requestSalary->agency_id,
                //     "month" => date("m"),
                //     "year" => date("Y"),
                // ],[
                //     'sallary' => DB::raw('sallary + ' . $requestSalary->usd),
                // ]);

                AdminCheck::create([
                    'request_id'    =>  $requestSalary->id,
                    'admin_check'   =>  0,
                    'type'          =>  "confirmation",
                ]);
            }
        }

    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
