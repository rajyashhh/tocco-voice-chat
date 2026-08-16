<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixImageCacheHeaders extends Command
{
    protected $signature = 'images:fix-cache-headers';
    protected $description = 'Fix cache headers for existing GCS images';

    public function handle()
    {
        $disk = Storage::disk('admin');
        $directories = ['profile', 'agencies', 'banners', 'images'];
        $totalFiles = 0;
        $updated = 0;
        $errors = 0;

        $this->info('Starting to fix image cache headers...');

        foreach ($directories as $dir) {
            try {
                $this->info("Processing directory: $dir");
                $files = $disk->allFiles($dir);
                $totalFiles += count($files);

                $bar = $this->output->createProgressBar(count($files));
                $bar->start();

                foreach ($files as $file) {
                    try {
                        $content = $disk->get($file);
                        $disk->put($file, $content, [
                            'CacheControl' => 'public, max-age=31536000',
                            'visibility' => 'public'
                        ]);
                        $updated++;
                        $bar->advance();
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("\nError updating $file: " . $e->getMessage());
                    }
                }

                $bar->finish();
                $this->newLine();
            } catch (\Exception $e) {
                $this->error("Error reading directory $dir: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Total files: $totalFiles");
        $this->info("Updated: $updated");
        $this->info("Errors: $errors");
        $this->info("Done!");

        return 0;
    }
}
