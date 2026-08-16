<?php

namespace Database\Seeders;

use App\Models\Background;
use Illuminate\Database\Seeder;

/**
 * White-label baseline: ship 4 default room backgrounds so a fresh client
 * install is never empty and the default room background is valid (not a broken
 * placeholder). The image files live in the client's object storage under the
 * same relative paths (copied during white-label provisioning).
 *
 * Idempotent (firstOrCreate by img).
 */
class DefaultBackgroundSeeder extends Seeder
{
    public function run(): void
    {
        $imgs = [
            'images/5e1afbe2-4e5f-4f89-ae95-a2eddcf3c371.jpg',
            'images/8db64299-1294-4219-bee6-bcbb2cb69672.jpg',
            'images/9af8f208-8bc8-43ba-9d09-b0da5d75510e.jpg',
            'images/821cb2b4-f7e9-424c-9634-6ab92aca423a.jpg',
        ];

        foreach ($imgs as $img) {
            Background::firstOrCreate(
                ['img' => $img],
                ['enable' => 1, 'sort' => 0, 'use_count' => 0]
            );
        }
    }
}
