<?php

namespace App\Console\Commands;

use App\Jobs\OptimizeMediaJob;
use App\Models\Profile;
use App\Models\Room;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OptimizeImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:optimize
                            {--type=all : Type to optimize: all, rooms, profiles}
                            {--batch=100 : Batch size for chunking}
                            {--skip-webp : Skip images already in WebP format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize all existing room covers and user profile avatars from Google Cloud Storage';

    private int $dispatched = 0;
    private int $skipped = 0;
    private int $errors = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type     = $this->option('type');
        $batch    = (int) $this->option('batch');
        $skipWebp = $this->option('skip-webp');

        $disk = Storage::disk(config('filesystems.default'));

        $this->info('🚀 Starting image optimization from Google Cloud Storage...');
        $this->info("   Type: {$type} | Batch: {$batch} | Skip WebP: " . ($skipWebp ? 'Yes' : 'No'));
        $this->newLine();

        if (in_array($type, ['all', 'profiles'])) {
            $this->optimizeProfiles($disk, $batch, $skipWebp);
        }

        if (in_array($type, ['all', 'rooms'])) {
            $this->optimizeRoomCovers($disk, $batch, $skipWebp);
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info("✅ Finished! Dispatched: {$this->dispatched} | Skipped: {$this->skipped} | Errors: {$this->errors}");
        $this->info('═══════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Optimize all user profile avatars from GCS
     */
    private function optimizeProfiles($disk, int $batch, bool $skipWebp): void
    {
        $this->info('👤 Processing user profile avatars...');

        $query = Profile::query()
            ->whereNotNull('avatar')
            ->where('avatar', '!=', '');

        $total = $query->count();
        $this->info("   Found {$total} profiles with avatars");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->select(['id', 'avatar'])
            ->chunkById($batch, function ($profiles) use ($disk, $skipWebp, $bar) {
                foreach ($profiles as $profile) {
                    $this->processImage(
                        disk: $disk,
                        path: $profile->avatar,
                        folder: 'profile',
                        context: 'profile',
                        modelClass: Profile::class,
                        modelId: $profile->id,
                        column: 'avatar',
                        skipWebp: $skipWebp,
                        label: "Profile #{$profile->id}",
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Optimize all room covers from GCS
     */
    private function optimizeRoomCovers($disk, int $batch, bool $skipWebp): void
    {
        $this->info('🏠 Processing room covers...');

        $query = Room::query()
            ->whereNotNull('room_cover')
            ->where('room_cover', '!=', '');

        $total = $query->count();
        $this->info("   Found {$total} rooms with covers");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->select(['id', 'room_cover'])
            ->chunkById($batch, function ($rooms) use ($disk, $skipWebp, $bar) {
                foreach ($rooms as $room) {
                    $this->processImage(
                        disk: $disk,
                        path: $room->room_cover,
                        folder: 'rooms',
                        context: 'room',
                        modelClass: Room::class,
                        modelId: $room->id,
                        column: 'room_cover',
                        skipWebp: $skipWebp,
                        label: "Room #{$room->id}",
                    );
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Process a single image: validate → dispatch OptimizeMediaJob to queue
     * The job will download from GCS, optimize, convert to WebP, and re-upload
     */
    private function processImage(
        mixed   $disk,
        ?string $path,
        string  $folder,
        string  $context,
        string  $modelClass,
        int     $modelId,
        string  $column,
        bool    $skipWebp,
        string  $label,
    ): void {
        try {
            // Skip empty paths
            if (empty($path)) {
                $this->skipped++;
                return;
            }

            // Skip already optimized WebP files if requested
            if ($skipWebp && str_ends_with(strtolower($path), '.webp')) {
                $this->skipped++;
                return;
            }

            // Skip if file doesn't exist on GCS
            if (!$disk->exists($path)) {
                $this->skipped++;
                return;
            }

            // Dispatch the optimization job to queue
            // The job downloads from GCS → optimizes → converts to WebP → re-uploads → updates model
            OptimizeMediaJob::dispatch(
                storagePath: $path,
                folder: $folder,
                context: $context,
                modelClass: $modelClass,
                modelId: $modelId,
                column: $column,
            )->onQueue('optimization-images');

            $this->dispatched++;
        } catch (\Throwable $e) {
            $this->errors++;
            Log::error("OptimizeImage command failed for {$label}: {$e->getMessage()}");
        }
    }
}
