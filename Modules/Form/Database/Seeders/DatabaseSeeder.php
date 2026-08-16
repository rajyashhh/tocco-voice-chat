<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Database\Seeders\CustomFieldWidgetSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CustomFieldWidgetSeeder::class, // Add widgets before form templates
            FormTemplateSeeder::class,
            FormSectionSeeder::class,
            FormFieldSeeder::class,
            AgencySeeder::class,
            FormSubmissionSeeder::class,
        ]);
    }
}
