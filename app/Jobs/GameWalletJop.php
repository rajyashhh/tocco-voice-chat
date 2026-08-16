<?php

namespace App\Jobs;

use App\Models\GameWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GameWalletJop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $currency;
    public function __construct($currency)
    {
        $this->currency = $currency;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        GameWallet::currentMonth()->increment('used', (int) ($this->currency * (-1)));
    }
}
