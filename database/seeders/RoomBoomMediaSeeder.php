<?php

namespace Database\Seeders;



use App\Helpers\Common;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;



class RoomBoomMediaSeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Starting Boom Images Seeder...');

        $localPath = public_path('images/room boom');

        DB::transaction(function () use ($localPath) {

            for ($level = 0; $level <= 101; $level++) {

                $this->log("Processing Level: {$level}");

                /*
                |--------------------------------------------------------------------------
                | 1️⃣ Upload bom_{level}.svga → boom_percentages
                |--------------------------------------------------------------------------
                */

                $fileName1 = "bom_{$level}.svga";
                $newPath1 = $this->uploadIfExists($localPath, $fileName1);

                if ($newPath1) {

                    DB::table('boom_percentages')->updateOrInsert(
                        ['percentage' => $level],
                        [
                            'image' => $newPath1,
                            'image_type' => 'svga',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $this->log("✔ Saved {$fileName1}");
                }

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ Upload bom_lv_{level}.svga → room_boom_levels
                |--------------------------------------------------------------------------
                */

                $fileName2 = "bom_lv_{$level}.svga";
                $newPath2 = $this->uploadIfExists($localPath, $fileName2);

                if ($newPath2) {

                    DB::table('room_boom_levels')
                        ->where('level', $level)
                        ->update([
                            'boom_image' => $newPath2,
                            'image_type_boom' => 'svga',
                            'updated_at' => now(),
                        ]);
                    if ($level == 1 || $level == 3 || $level == 4 || $level == 5) {
                        $fileName2 = "bomb_background.jpeg";
                        $newPath2 = $this->uploadIfExists($localPath, $fileName2);
                        DB::table('room_boom_levels')
                            ->where('level', $level)
                            ->update([
                                'background_image' => $newPath2,
                                'image_type_background' => 'png',
                                'updated_at' => now(),
                            ]);
                    } elseif ($level == 2) {
                        $fileName2 = "bomb_background_waka.png";
                        $newPath2 = $this->uploadIfExists($localPath, $fileName2);
                        DB::table('room_boom_levels')
                            ->where('level', $level)
                            ->update([
                                'background_image' => $newPath2,
                                'image_type_background' => 'png',
                                'updated_at' => now(),
                            ]);
                    }

                    $this->log("✔ Saved {$fileName2}");
                }
            }
        });

        $this->log('Boom Images Seeder Completed Successfully 🚀');
    }

    /**
     * Log message to command output or Laravel log
     */
    private function log(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
        }
    }

    /**
     * Log warning message
     */
    private function warn(string $message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        } else {
            Log::warning($message);
        }
    }

    /**
     * Upload file if exists
     */
    private function uploadIfExists(string $localPath, string $fileName): ?string
    {
        $fullPath = $localPath . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($fullPath)) {
            $this->warn("⚠ File not found: {$fileName}");
            return null;
        }

        $file = new UploadedFile(
            $fullPath,
            basename($fullPath),
            mime_content_type($fullPath),
            null,
            true
        );

        return Common::upload('images', $file);
    }
}
