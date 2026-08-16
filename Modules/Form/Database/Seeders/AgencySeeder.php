<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        Agency::create([
            'agency_name' => 'Tech Innovators Inc.',
            'agency_logo' => 'logos/tech_innovators.png',
            'agency_image' => 'images/tech_innovators.jpg',
            'description' => 'Leading the charge in AI and machine learning solutions.',
            'status' => 'approved',
            'created_by' => 2, // Corresponds to owner@example.com
            'reviewed_by' => 1, // Corresponds to admin@example.com
            'review_notes' => 'Initial approved agency.',
            'reviewed_at' => now(),
        ]);
    }
}