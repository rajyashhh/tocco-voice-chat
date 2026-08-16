<?php

namespace App\Admin\Extensions;

use App\Models\ShippingAgency;
use App\Models\User;
use App\Models\Agency;
use App\Models\AgencySallary;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\FromCollection;
use Modules\SalaryTransaction\Entities\ChargeAgency;

class ChargeAgencyExporter implements FromCollection, WithHeadings
{
    public $id;

    public $uiid;

    public function __construct($id = null,  $uuid = null)
    {
        $this->id = $id;

        $this->uuid = $uuid;
    }

    public function collection()
    {
       
                $agencies = ShippingAgency::with(['owner.profile'])
            ->when($this->id, function ($query) {
                $query->where('id', $this->id);
            })
            ->when($this->uuid, function ($query) {
                $search = $this->uuid;
                $query->whereHas('owner', function ($q) use ($search) {
                    $q->Where('uuid', 'like', "%$search%");
                });
            })
            ->get();

        $arr = [];

        foreach ($agencies as $agency) {
            $arr[] = [
                'id' => $agency->id,
                'name' => $agency->name,
                'owner_name' => optional($agency->owner)->name ?? '-',
                'owner_uuid' => optional($agency->owner)->uuid ?? '-',
                'owner_phone' => '"' . optional($agency->owner)->phone . '"',
                'charge_agency' => ChargeAgency::where('agency_id', $agency->id)->exists() ? 'Yes' : 'No',
                'appear_charger_agency' => optional($agency->owner)->appear_charger_agency ? 'Yes' : 'No',
                'is_frozen' => $agency->is_frozen ? 'Yes' : 'No',
            ];
        }

        return collect($arr);
    }

    public function headings(): array
    {
        return [
            __('ID'),
            __('Agency Name'),
            __('Owner Name'),
            __('Owner UUID'),
            __('Owner Phone'),
            __('Charge Agency_'),
            __('Appear Charger Agency'),
            __('Is Frozen'),
        ];
    }
}
