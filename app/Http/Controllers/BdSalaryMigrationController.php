<?php
namespace App\Http\Controllers;

use App\Jobs\MigrateOldBdSalariesJob;
use App\Models\Bd;
use App\Models\BdAgencyHostSallary;
use App\Models\BdSalary;
use App\Models\UserSallary;
use Illuminate\Support\Facades\DB;

class BdSalaryMigrationController extends Controller
{
       public function migrate()
    {
            MigrateOldBdSalariesJob::dispatch()->onQueue('migrations_bd_salaries');
    
            return response()->json([
                'status' => true,
                'message' => 'done',
            ]);
        
    }
}
