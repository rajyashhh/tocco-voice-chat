<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\UserCoinLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogUserCoinProfit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $amountBefore;
    protected $amount;
    protected $helperAmount;
    protected $type;
    protected $subType;
    protected $itemName;
    protected $createdAt;

    public function __construct(
        int $userId,
        float $amountBefore,
        float $amount,
        float $helperAmount = 0,
        string $type,
        string $subType,
        ?string $itemName = '',
        $createdAt = null
    ) {
        $this->userId = $userId;
        $this->amountBefore = $amountBefore;
        $this->amount = $amount;
        $this->helperAmount = $helperAmount;
        $this->type = $type;
        $this->subType = $subType;
        $this->itemName = $itemName;
        $this->createdAt = $createdAt ?? now();
    }

    public function handle()
    {
        UserCoinLog::create([
            'user_id'       => $this->userId,
            'type'          => $this->type,
            'sub_type'      => $this->subType,
            'amount_before' => $this->amountBefore,
            'amount'        => $this->amount,
            'helper_amount'        => $this->helperAmount ?? 0,
            'item_name'     => $this->itemName,
            'from_date'     => $this->createdAt,
            'to_date'       => $this->createdAt,
        ]);
    
    }
}
