<?php 
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MigrateOldSallaryFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $salaries = DB::table('user_sallaries')
            ->where(function ($query) use ($currentMonth, $currentYear) {
                $query->where('year', '<', $currentYear)
                      ->orWhere(function ($q) use ($currentMonth, $currentYear) {
                          $q->where('year', $currentYear)
                            ->where('month', '<', $currentMonth);
                      });
            })
            ->get();

        foreach ($salaries as $salary) {
            DB::table('user_sallaries')
                ->where('id', $salary->id)
                ->update([
                    'achieved_hours'   => self::extractBeforeSlash($salary->hours),
                    'achieved_days'    => self::extractBeforeSlash($salary->days),
                    'achieved_diamond' => self::extractBeforeSlash($salary->diamond),
                ]);
        }
    }

    private static function extractBeforeSlash($value): int
    {
        if (!$value) return 0;
        $parts = explode('/', $value);
        return (int) trim($parts[0] ?? 0);
    }
}
