<?php

namespace App\Exports;

use App\Models\Agency;
use App\Models\Charge;
use App\Models\ShippingAgency;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AgencyChargeTransactions implements FromView
{
    protected $agency_id;

    public function __construct($agency_id)
    {
        $this->agency_id = $agency_id;
    }

    public function view(): View
    {
        try {
            $agencyId = $this->agency_id;
            $agency   = ShippingAgency::findOrFail($agencyId);

            $received = Charge::query()
                ->when(request('receiver_type'), fn($q) => 
                    $q->where('user_type', request('receiver_type'))
                )
                ->when(request('receiver_id'), fn($q) => 
                    $q->where('user_id', request('receiver_id'))
                )
                ->where('user_id', $agencyId)
                ->where('user_type', 'agency')
                ->orderByDesc('id')
                ->get();

            $sent = Charge::query()
                ->when(request('sender_type'), fn($q) => 
                    $q->where('charger_type', request('sender_type'))
                )
                ->when(request('sender_id'), fn($q) => 
                    $q->where('charger_id', request('sender_id'))
                )
                ->where('charger_id', $agencyId)
                ->where('charger_type', 'agency')
                ->orderByDesc('id')
                ->get();

            return view('admin.excel.chargeAgencyTransactions', [
                'agency'   => $agency,
                'received' => $received,
                'sent'     => $sent,
            ]);

        } catch (\Exception $e) {
            // return view('admin.excel.chargeAgencyTransactions', [
            //     'agency'   => null,
            //     'received' => collect(),
            //     'sent'     => collect(),
            //     'error'    => $e->getMessage(),
            // ]);
        }
    }
}