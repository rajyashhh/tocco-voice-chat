<?php

namespace Database\Seeders;

use App\Models\MomentGallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteOrphanedMomentGalleriesSeeder extends Seeder
{
    /**
     * Check all moment_galleries records and delete those whose image
     * file does not exist in Google Cloud Storage.
     */
    public function run()
    {
        $disk = Storage::disk(config('filesystems.default'));
        $deletedCount = 0;
        $checkedCount = 0;

        $this->command->info('Starting cleanup of moment_galleries...');

        MomentGallery::query()
            ->select(['id', 'image', 'moment_id'])
            ->chunkById(100, function ($galleries) use ($disk, &$deletedCount, &$checkedCount) {
                foreach ($galleries as $gallery) {
                    $checkedCount++;

                    // Skip records with empty image path
                    if (empty($gallery->image)) {
                        $gallery->delete();
                        $deletedCount++;
                        $this->command->warn("Deleted record ID {$gallery->id} (empty image path)");
                        continue;
                    }

                    // Check if file exists on storage disk (GCS)
                    if (!$disk->exists($gallery->image)) {
                        $gallery->delete();
                        $deletedCount++;
                        $this->command->warn("Deleted record ID {$gallery->id}, image not found: {$gallery->image}");
                    }
                }

                $this->command->info("Checked {$checkedCount} records so far, deleted {$deletedCount}...");
            });

        $this->command->info("Completed. Total checked: {$checkedCount}, Total deleted: {$deletedCount}");
        Log::info("DeleteOrphanedMomentGalleriesSeeder: Checked {$checkedCount}, Deleted {$deletedCount}");
    }
}
