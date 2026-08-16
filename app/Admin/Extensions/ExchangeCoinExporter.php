<?php

namespace App\Admin\Extensions;

use Carbon\Carbon;

use App\Models\CoinLog;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;


class ExchangeCoinExporter implements FromCollection, WithHeadings
{

    protected $fileName = 'exchange_diamonds.csv';
    protected $headings = [
        "id",
        "charger",
        'dollar',
        'amount',
        'trx',
        'type',
        'status',
        'date',
    ];
    public $from_date;
    public $to_date;
    public $uuid;
    public $trx;
    public $status;
    public $method;

    public function __construct($uuid = null, $from_date = null, $to_date = null, $trx = null, $status = null, $method = null)
    {
        $this->uuid = $uuid;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->trx = $trx;
        $this->status = $status;
        $this->method = $method;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $uuid  = $this->uuid;
        $start = $this->from_date;
        $end =  $this->to_date;
        $trx =  $this->trx;
        $status = $this->status;
        $method = $this->method;

        $query = CoinLog::with('user', 'coin')
            ->when(isset($trx), function ($query) use ($trx) {
                $query->where('trx', $trx);
            })->when(isset($status), function ($query) use ($status) {
                $query->where('status', $status);
            })->when(isset($method), function ($query) use ($method) {
                $query->where('method', $method);
            });


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
                'uuid' =>(@$exchange->user->uuid ?? ''),
                'dollar' =>  number_format(@$exchange->coin->usd ?? 0) . '💲',
                'amount' => number_format($exchange->obtained_coins),
                'trx' => $exchange->trx,
                'type' => $exchange->coin->paymentCoin->title ?? '',
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
            __('dollar', [], 'ar'),
            __('amount', [], 'ar'),
            __('trx', [], 'ar'),
            __('type', [], 'ar'),
            __('status', [], 'ar'),
            __('date', [], 'ar'),

        ];
    }
}
