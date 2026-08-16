<?php

namespace Database\Seeders;

use App\Models\RoomCategory;
use Illuminate\Database\Seeder;

/**
 * White-label baseline: ship at least one usable room class + room type so a
 * fresh client install is NEVER empty — the user can create a room out of the
 * box without the client first having to add a type from the control panel.
 *
 * Idempotent (firstOrCreate): safe to run on every deploy / re-seed.
 */
class DefaultRoomCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Parent "class" (parent_id = null) — room types are its children.
        $class = RoomCategory::firstOrCreate(
            ['name_en' => 'General', 'parent_id' => null],
            ['name' => 'عام', 'name_ar' => 'عام', 'img' => '', 'enable' => 1, 'sort' => 1]
        );

        // Default room "type" (a child of the class) — what the user picks when
        // creating a room. Clients can rename/add more from the panel.
        RoomCategory::firstOrCreate(
            ['name_en' => 'Party', 'parent_id' => $class->id],
            ['name' => 'بارتي', 'name_ar' => 'بارتي', 'img' => '', 'enable' => 1, 'sort' => 1]
        );
    }
}
