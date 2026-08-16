<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\MomentGallery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Moment\Entities\Moment;

class UploadMomentImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [1, 1, 1, 1, 1];
    
    protected $momentId;
    protected $filePath;

    public function __construct($momentId, $filePath)
    {
        $this->momentId = $momentId;
        $this->filePath = $filePath;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {

        $moment = Moment::find($this->momentId);

        if (!$moment) {
            return;
        }

        $fullPath = storage_path('app/' . $this->filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Temp file not found: {$fullPath}");
        }

        $file = new UploadedFile($fullPath, basename($fullPath), null, null, true);

        $path = Common::upload('profile', $file);

        if (!Storage::disk(config('filesystems.default'))->exists($path)) {
            throw new \Exception("File not found on disk after upload: {$path}");
        }

        MomentGallery::create([
            'moment_id' => $moment->id,
            'image' => $path,
        ]);

        @unlink($this->filePath);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('UploadMomentImageJob failed permanently', [
            'moment_id' => $this->momentId,
            'file' => $this->filePath,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        $fullPath = storage_path('app/' . $this->filePath);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        // Deliberately keep the moment: a failed image upload must never
        // destroy the user's post (text stays, image can be retried/re-added).
        // Deleting here silently erased owner posts on infra failures.
    }
}
