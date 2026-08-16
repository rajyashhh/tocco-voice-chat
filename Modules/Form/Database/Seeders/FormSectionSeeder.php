<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Entities\FormSection;
//use App\Models\FormSection;

class FormSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sectionsData = [
            [
                'translations' => [
                    'title' => [
                        'ar' => 'البيانات الشخصية',
                        'en' => 'Personal Information',
                        'fr' => 'Informations personnelles'
                    ],
                    'description' => [
                        'ar' => 'المعلومات الشخصية للمسؤول',
                        'en' => 'Personal information of the responsible person',
                        'fr' => 'Informations personnelles du responsable'
                    ]
                ],
                'order' => 1
            ],
            [
                'translations' => [
                    'title' => [
                        'ar' => 'المستندات',
                        'en' => 'Documents',
                        'fr' => 'Documents'
                    ],
                    'description' => [
                        'ar' => 'المستندات المطلوبة للتسجيل',
                        'en' => 'Required documents for registration',
                        'fr' => 'Documents requis pour l\'inscription'
                    ]
                ],
                'order' => 2
            ],
            [
                'translations' => [
                    'title' => [
                        'ar' => 'معلومات الوكالة',
                        'en' => 'Agency Information',
                        'fr' => 'Informations sur l\'agence'
                    ],
                    'description' => [
                        'ar' => 'التفاصيل الخاصة بالوكالة',
                        'en' => 'Details about the agency',
                        'fr' => 'Détails sur l\'agence'
                    ]
                ],
                'order' => 3
            ],
            [
                'translations' => [
                    'title' => [
                        'ar' => 'بيانات الاتصال',
                        'en' => 'Contact Information',
                        'fr' => 'Informations de contact'
                    ],
                    'description' => [
                        'ar' => 'معلومات التواصل',
                        'en' => 'Communication information',
                        'fr' => 'Informations de communication'
                    ]
                ],
                'order' => 4
            ],
        ];

        foreach ($sectionsData as $sectionData) {
            $section = new FormSection();
            $section->form_template_id = 1;
            $section->section_order = $sectionData['order'];
            
            // Set translations for each locale
            foreach ($sectionData['translations'] as $field => $translations) {
                foreach ($translations as $locale => $text) {
                    $section->setTranslation($field, $locale, $text);
                }
            }
            
            $section->save();
        }
    }
}
