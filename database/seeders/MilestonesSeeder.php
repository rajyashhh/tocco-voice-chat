<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Milestones\Entities\Milestone;

class MilestonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Milestone::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $milestones = [
            [
                'name'        => 'Host Agency Owner',
                'slug'        => 'host-agency-owner',
                'description' => 'Milestone for host agency owners',
                'is_active'   => true,
            ],
            [
                'name'        => 'Charge Agency Owner',
                'slug'        => 'charge-agency-owner',
                'description' => 'Milestone for charge agency owners',
                'is_active'   => true,
            ],
            [
                'name'        => 'Host',
                'slug'        => 'host',
                'description' => 'Milestone for hosts',
                'is_active'   => true,
            ],
            [
                'name'        => 'Family Owner',
                'slug'        => 'family-owner',
                'description' => 'Milestone for family owners',
                'is_active'   => true,
            ],
            [
                'name'        => 'Super admin',
                'slug'        => 'country',
                'description' => 'Milestone for Super admin',
                'is_active'   => true,
            ],
            [
                'name'        => 'BD',
                'slug'        => 'bd',
                'description' => 'Milestone for BD',
                'is_active'   => true,
            ],
            [
                'name'        => 'Area Manager',
                'slug'        => 'region',
                'description' => 'Milestone for Area manager',
                'is_active'   => true,
            ],
        ];

        foreach ($milestones as $milestone) {
            Milestone::firstOrCreate(
                ['slug' => $milestone['slug']], // check by slug
                $milestone                      // create if not exist
            );
        }
    }
}
