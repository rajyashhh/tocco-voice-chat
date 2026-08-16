<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\UsersWallet\Helpers\WalletHelper;

class UpdateUserWalletBalances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;
    protected array $newData;
    protected array $oldData;
    protected ?int $agencyId;
    protected string $type;
    protected   $target_id;
    // Untyped + default null so any job already serialized in the queue before this
    // deploy unserializes safely (missing property falls back to the declared default).
    protected $operationId = null;


    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, array $newData, array $oldData, ?int $agencyId = null, string $type = 'system' , $target_id = null, $operationId = null)
    {
        $this->userId   = $userId;
        $this->newData  = $newData;
        $this->oldData  = $oldData;
        $this->agencyId = $agencyId;
        $this->type     = $type;
        $this->target_id = $target_id;
        // Stable across retries: set once at dispatch, so a retried job credits at most once.
        $this->operationId = $operationId;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        WalletHelper::addAllBalancesByDiffs(
            $this->userId,
            $this->newData,
            $this->oldData,
            $this->agencyId,
            $this->type,
            $this->target_id,
            $this->operationId ?? null
        );
    }
}
