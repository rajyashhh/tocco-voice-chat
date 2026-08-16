<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Entities\FormSubmission;
use Modules\Form\Entities\FormSubmissionValue;
// use App\Models\FormSubmission;
// use App\Models\FormSubmissionValue;

class FormSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $submission = FormSubmission::create([
            'form_template_id' => 1,
            'entity_id' => 1,
            'entity_type' => 'agency',
            'submitted_by' => 2,
            'submission_status' => 'submitted',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder',
        ]);

        // Seed submission values based on FormFieldSeeder data
        $submissionValues = [
            ['field_id' => 1, 'field_value' => 'Owner User'],
            ['field_id' => 2, 'field_value' => '+1234567890'],
            ['field_id' => 3, 'field_value' => 'owner@example.com'],
            ['field_id' => 5, 'field_value' => '1'], // display_order
            ['field_id' => 9, 'field_value' => 'Tech Innovators Inc.'],
            ['field_id' => 11, 'field_value' => 'Commercial Services'], // activity_type
            ['field_id' => 12, 'field_value' => 'Leading the charge in AI and machine learning solutions.'],
            ['field_id' => 14, 'field_value' => '123 Tech Lane, Innovation City'],
            ['field_id' => 15, 'field_value' => 'Riyadh'],
        ];

        foreach ($submissionValues as $value) {
            FormSubmissionValue::create([
                'submission_id' => $submission->id,
                'field_id' => $value['field_id'],
                'field_value' => $value['field_value'],
            ]);
        }
    }
}
