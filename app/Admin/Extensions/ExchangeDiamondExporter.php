<?php

namespace App\Admin\Extensions;

use Carbon\Carbon;

use App\Models\ExchangeLog;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;


class ExchangeDiamondExporter  implements FromCollection, WithHeadings
{

    protected $fileName = 'exchange_diamonds.csv';
    protected $headings = [
        "id",
        "charger",
        'uuid',
        'diamonds',
        'amount',
        'status',
        'date',
    ];
    public $from_date;
    public $to_date;
    public $uuid;

    public function __construct($uuid = null, $from_date = null, $to_date = null)
    {
        $this->uuid = $uuid;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $query = ExchangeLog::with('user');
        $uuid  = $this->uuid;
        $start = $this->from_date;
        $end =  $this->to_date;

        if ($this->uuid) {

            $query->whereHas('user', function ($q) use ($uuid) {
                $q->where('uuid',  $uuid);
            });
        }
        if ($start && $end) {
            $query->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        $exchanges = $query->get();

        $arr = [];

        foreach ($exchanges as $exchange) {


            $arr[] = [
                'id' => $exchange->id,
                'charger' => (@$exchange->user->name ?? ''),
                'uuid' => (@$exchange->user->uuid ?? ''),
                'diamonds' => $exchange->diamonds . ' 💎',
                'amount' => $exchange->value,
                'status' => $exchange->status == 1 ? __('Success') : __('Failed'),
                'date' => $exchange->created_at,

            ];
        }

        return collect($arr);
    }


    public function headings(): array
    {
        return [
            __("id", [], 'ar'),
            __('charger', [], 'ar'),
            __('charger uuid', [], 'ar'),
            __('diamonds', [], 'ar'),
            __('amount', [], 'ar'),
            __('status', [], 'ar'),
            __('date', [], 'ar'),

        ];
    }
}
