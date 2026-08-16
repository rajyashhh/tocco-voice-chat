<?php

namespace Database\Seeders;


use App\Helpers\Common;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class VipBackgroundImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $localPath = public_path('images/Vip');

        for ($level = 1; $level <= 7; $level++) {

            $fileName = "vip_background{$level}.png";
            $fullPath = $localPath . DIRECTORY_SEPARATOR . $fileName;

            if (!file_exists($fullPath)) {
                $this->command->warn("File not found: {$fileName}");
                continue;
            }

            // Convert local file to Laravel UploadedFile object
            $file = new UploadedFile(
                $fullPath,
                basename($fullPath),
                mime_content_type($fullPath),
                null,
                true
            );

            // Upload to Google Storage using your function
            $newPath = Common::upload('images', $file);
            // 'gcs' = google cloud disk name (change if different)

            // Update ovip record
            DB::table('o_vips')
                ->where('level', $level)
                ->update([
                    'background_img' => $newPath
                ]);

            $this->command->info("Uploaded & updated level {$level}");
        }

        $this->command->info('VIP background images uploaded to Google Storage successfully.');
    }
}
