<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\AgencySallary;

class FirstImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private $data;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $user = User::where("uuid", $row['uuid'])->first();
                $check = UserSallary::where(["user_id" => $user->id, "month" => 11])->first();

                if ($check != null) {
                    $check->cut_amount -= $row['type'];
                    $check->save();
                } else {
                    $newSallary = new UserSallary();
                    $newSallary->user_id = $user->id;
                    $newSallary->cut_amount = -$row['type'];
                    $newSallary->agency_sallary = $row['value'];
                    $newSallary->month = 11;
                    $newSallary->user_agency_id = $row['agency_id'];
                    $newSallary->created_at = "2023-11-05 16:31:34";
                    $newSallary->save();
                }

                $checkAgency = AgencySallary::where(["agency_id" => $row['agency_id'], "month" => 11])->first();
                if ($checkAgency != null) {
                    $checkAgency->cut_amount -= $row['value'];
                    $checkAgency->save();
                } else {
                    $newSallaryAgency = new AgencySallary();
                    $newSallaryAgency->agency_id = $row['agency_id'];
                    $newSallaryAgency->cut_amount = -$row['value'];
                    $newSallaryAgency->month = 11;
                    $newSallaryAgency->created_at = "2023-11-05 16:31:34";
                    $newSallaryAgency->save();
                }
            } catch (\Throwable $th) {
                //dd($th->getMessage());
            }
        }
    }
}
