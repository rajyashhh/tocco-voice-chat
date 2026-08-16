<?php

namespace App\Admin\Extensions;

use Carbon\Carbon;
use App\Models\Charge;
use App\Helpers\Common;
use App\Models\CoinLog;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;


class ExchangeChargeExport implements FromCollection, WithHeadings
{
    protected $fileName = 'exchange_diamonds.csv';
    protected $headings = [
        "id",
        "sender",
        'recipient',
        'amount',
        'Coins',
        'status',
        'date',
    ];
    public $from_date;
    public $to_date;
    public $filtering;
    public $name;
    public $type;

    public function __construct($name = null, $from_date = null, $to_date = null, $filtering = null, $type = null)
    {

        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->filtering = $filtering;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function collection()
    {
        $start = $this->from_date;
        $end =  $this->to_date;
        $filtering = $this->filtering;
        $name = $this->name;
        $type = $this->type;

        $query = Charge::with('receiver', 'receiverUser', 'receiveragency', 'admin', 'senderShippingAgency', 'senderAgency', 'senderUser');
        if ($name === 'host') {
            $query->where('charger_type', 'host_agency');
        } else {
            $query->where('charger_type', "dash");
        }


        if ($type === 'user' && isset($filtering)) {

            $query->whereHas('receiverUser', function ($q) use ($filtering) {
                $q->where('uuid',  $filtering)->orWhere('name', 'like', "%$filtering%");
            });
        } elseif ($type != 'user' && isset($filtering)) {
            $query->whereHas('receiver', function ($q) use ($filtering) {
                $q->where('id',  $filtering)->orWhere('name', 'like', "%$filtering%");
            });
        }
        if ($start && $end) {
            $query->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        $exchanges = $query->get();

        $arr = [];

        foreach ($exchanges as $exchange) {
            $receiver = Common::getReceiverInfo($exchange);
            $nameReceiver = $receiver['name'];
            $uuidReceiver = $receiver['uuid'];
            $sender = Common::getChargerInfo($exchange);
            $senderName = $sender['name'];
            $senderUuid = $sender['uuid'];
            if ($name === 'host') {
                $arr[] = [
                    'id' => $exchange->id,
                    'sender' => (@$senderName ?? ''),
                    'sender uuid ' => (@$senderUuid ?? ''),
                    'recipient' => (@$nameReceiver ?? ''),
                    'recipient uuid' => (@$uuidReceiver ?? ''),
                    'amount' => number_format($exchange->usd) . '💲',
                    'coins' => $exchange->amount,
                    'date' => $exchange->created_at,

                ];
            } else {
                $arr[] = [
                    'id' => $exchange->id,
                    'sender' => (@$senderName ?? ''),
                    'sender uuid' => (@$senderUuid ?? ''),
                    'recipient' => (@$nameReceiver ?? ''),
                    'recipient uuid'=> (@$uuidReceiver ?? ''),
                    'amount' => number_format($exchange->usd) . '💲',
                    'coins' => $exchange->amount,
                    'status' => $exchange->amount >= 1 ? __('Increment') : __('Decrement'),

                    'date' => $exchange->created_at,

                ];
            }
        }

        return collect($arr);
    }


    public function headings(): array
    {
        $headings = [
            __("id", [], 'ar'),
            __('Sender', [], 'ar'),
            __('Sender uuid', [], 'ar'),
            __('recipient', [], 'ar'),
            __('recipient uuid', [], 'ar'),
            __('amount', [], 'ar'),
            __('Coins', [], 'ar'),
        ];

        if ($this->name !== 'host') {
            $headings[] = __('status', [], 'ar');
        }

        $headings[] = __('date', [], 'ar');

        return $headings;
    }
}
