<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Entities\FormField;
//use App\Models\FormField;

class FormFieldSeeder extends Seeder
{
    private function createField($data)
    {
        $field = new FormField();
        $field->section_id = $data['section_id'];
        $field->field_name = $data['field_name'];
        $field->field_type = $data['field_type'];
        $field->is_required = $data['is_required'] ?? false;
        $field->field_order = $data['field_order'];
        $field->options = $data['options'] ?? null;
        
        // Set translations
        foreach (['field_label', 'placeholder', 'help_text'] as $transField) {
            if (isset($data['translations'][$transField])) {
                foreach ($data['translations'][$transField] as $locale => $text) {
                    $field->setTranslation($transField, $locale, $text);
                }
            }
        }
        
        $field->save();
        return $field;
    }

    public function run(): void
    {
        $fieldsData = [
            // Section 1: Personal Information
            [
                'section_id' => 1,
                'translations' => [
                    'field_label' => [
                        'ar' => 'الاسم بالكامل',
                        'en' => 'Full Name',
                        'fr' => 'Nom complet'
                    ],
                    'placeholder' => [
                        'ar' => 'أدخل الاسم الكامل',
                        'en' => 'Enter full name',
                        'fr' => 'Entrez le nom complet'
                    ]
                ],
                'field_name' => 'full_name',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 1
            ],
            [
                'section_id' => 1,
                'translations' => [
                    'field_label' => [
                        'ar' => 'رقم الهاتف',
                        'en' => 'Phone Number',
                        'fr' => 'Numéro de téléphone'
                    ],
                    'placeholder' => [
                        'ar' => '+966XXXXXXXXX',
                        'en' => '+1XXXXXXXXXX',
                        'fr' => '+33XXXXXXXXX'
                    ]
                ],
                'field_name' => 'phone_number',
                'field_type' => 'tel',
                'is_required' => true,
                'field_order' => 2
            ],
            [
                'section_id' => 1,
                'translations' => [
                    'field_label' => [
                        'ar' => 'البريد الإلكتروني',
                        'en' => 'Email Address',
                        'fr' => 'Adresse e-mail'
                    ],
                    'placeholder' => [
                        'ar' => 'example@domain.com',
                        'en' => 'example@domain.com',
                        'fr' => 'exemple@domaine.com'
                    ]
                ],
                'field_name' => 'email_address',
                'field_type' => 'email',
                'is_required' => true,
                'field_order' => 3
            ],
            [
                'section_id' => 1,
                'translations' => [
                    'field_label' => [
                        'ar' => 'تاريخ الميلاد',
                        'en' => 'Date of Birth',
                        'fr' => 'Date de naissance'
                    ]
                ],
                'field_name' => 'birth_date',
                'field_type' => 'date',
                'is_required' => false,
                'field_order' => 4
            ],

            // Section 2: المستندات / Documents
            [
                'section_id' => 2,
                'translations' => [
                    'field_label' => [
                        'ar' => 'ادخل ترتيب الظهور',
                        'en' => 'Enter Display Order',
                        'fr' => 'Entrez l\'ordre d\'affichage'
                    ],
                    'placeholder' => [
                        'ar' => 'رقم الترتيب',
                        'en' => 'Order number',
                        'fr' => 'Numéro de commande'
                    ],
                    'help_text' => [
                        'ar' => 'حدد ترتيب ظهور الوكالة',
                        'en' => 'Specify the agency display order',
                        'fr' => 'Spécifiez l\'ordre d\'affichage de l\'agence'
                    ]
                ],
                'field_name' => 'display_order',
                'field_type' => 'number',
                'is_required' => false,
                'field_order' => 1
            ],
            [
                'section_id' => 2,
                'translations' => [
                    'field_label' => [
                        'ar' => 'صورة الهوية الوطنية',
                        'en' => 'National ID Image',
                        'fr' => 'Image de la carte d\'identité nationale'
                    ],
                    'help_text' => [
                        'ar' => 'صورة واضحة للهوية الوطنية',
                        'en' => 'Clear image of national ID',
                        'fr' => 'Image claire de la carte d\'identité'
                    ]
                ],
                'field_name' => 'national_id_image',
                'field_type' => 'file',
                'is_required' => true,
                'field_order' => 2
            ],
            [
                'section_id' => 2,
                'translations' => [
                    'field_label' => [
                        'ar' => 'السجل التجاري',
                        'en' => 'Commercial Registration',
                        'fr' => 'Registre du commerce'
                    ],
                    'help_text' => [
                        'ar' => 'نسخة من السجل التجاري',
                        'en' => 'Copy of commercial registration',
                        'fr' => 'Copie du registre du commerce'
                    ]
                ],
                'field_name' => 'commercial_registration',
                'field_type' => 'file',
                'is_required' => true,
                'field_order' => 3
            ],
            [
                'section_id' => 2,
                'translations' => [
                    'field_label' => [
                        'ar' => 'الترخيص',
                        'en' => 'License',
                        'fr' => 'Licence'
                    ],
                    'help_text' => [
                        'ar' => 'ترخيص مزاولة النشاط',
                        'en' => 'Business activity license',
                        'fr' => 'Licence d\'activité commerciale'
                    ]
                ],
                'field_name' => 'license_file',
                'field_type' => 'file',
                'is_required' => false,
                'field_order' => 4
            ],

            // Section 3: معلومات الوكالة / Agency Information
            [
                'section_id' => 3,
                'translations' => [
                    'field_label' => [
                        'ar' => 'اسم الوكالة',
                        'en' => 'Agency Name',
                        'fr' => 'Nom de l\'agence'
                    ],
                    'placeholder' => [
                        'ar' => 'الاسم التجاري للوكالة',
                        'en' => 'Commercial name of the agency',
                        'fr' => 'Nom commercial de l\'agence'
                    ]
                ],
                'field_name' => 'agency_name',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 1
            ],
            [
                'section_id' => 3,
                'translations' => [
                    'field_label' => [
                        'ar' => 'شعار الوكالة',
                        'en' => 'Agency Logo',
                        'fr' => 'Logo de l\'agence'
                    ]
                ],
                'field_name' => 'agency_logo',
                'field_type' => 'file',
                'is_required' => true,
                'field_order' => 2
            ],
            [
                'section_id' => 3,
                'translations' => [
                    'field_label' => [
                        'ar' => 'نوع النشاط',
                        'en' => 'Activity Type',
                        'fr' => 'Type d\'activité'
                    ]
                ],
                'field_name' => 'activity_type',
                'field_type' => 'select',
                'is_required' => true,
                'field_order' => 3,
                'options' => [
                    [
                        'ar' => 'خدمات تجارية',
                        'en' => 'Commercial Services',
                        'fr' => 'Services commerciaux'
                    ],
                    [
                        'ar' => 'خدمات تسويقية',
                        'en' => 'Marketing Services',
                        'fr' => 'Services de marketing'
                    ],
                    [
                        'ar' => 'خدمات استشارية',
                        'en' => 'Consulting Services',
                        'fr' => 'Services de conseil'
                    ],
                    [
                        'ar' => 'خدمات تقنية',
                        'en' => 'Technical Services',
                        'fr' => 'Services techniques'
                    ],
                    [
                        'ar' => 'أخرى',
                        'en' => 'Other',
                        'fr' => 'Autre'
                    ]
                ]
            ],
            [
                'section_id' => 3,
                'translations' => [
                    'field_label' => [
                        'ar' => 'وصف النشاط',
                        'en' => 'Activity Description',
                        'fr' => 'Description de l\'activité'
                    ],
                    'placeholder' => [
                        'ar' => 'وصف تفصيلي للنشاط',
                        'en' => 'Detailed description of activity',
                        'fr' => 'Description détaillée de l\'activité'
                    ]
                ],
                'field_name' => 'activity_description',
                'field_type' => 'textarea',
                'is_required' => true,
                'field_order' => 4
            ],
            [
                'section_id' => 3,
                'translations' => [
                    'field_label' => [
                        'ar' => 'عدد الموظفين',
                        'en' => 'Number of Employees',
                        'fr' => 'Nombre d\'employés'
                    ],
                    'placeholder' => [
                        'ar' => '0',
                        'en' => '0',
                        'fr' => '0'
                    ]
                ],
                'field_name' => 'employees_count',
                'field_type' => 'number',
                'is_required' => false,
                'field_order' => 5
            ],

            // Section 4: بيانات الاتصال / Contact Information
            [
                'section_id' => 4,
                'translations' => [
                    'field_label' => [
                        'ar' => 'عنوان الوكالة',
                        'en' => 'Agency Address',
                        'fr' => 'Adresse de l\'agence'
                    ],
                    'placeholder' => [
                        'ar' => 'الشارع، الحي، المدينة',
                        'en' => 'Street, District, City',
                        'fr' => 'Rue, Quartier, Ville'
                    ]
                ],
                'field_name' => 'agency_address',
                'field_type' => 'text',
                'is_required' => true,
                'field_order' => 1
            ],
            [
                'section_id' => 4,
                'translations' => [
                    'field_label' => [
                        'ar' => 'المدينة',
                        'en' => 'City',
                        'fr' => 'Ville'
                    ],
                    'options' => [
                        [
                            'ar' => 'الرياض',
                            'en' => 'Riyadh',
                            'fr' => 'Riyad'
                        ],
                        [
                            'ar' => 'جدة',
                            'en' => 'Jeddah',
                            'fr' => 'Djeddah'
                        ],
                        [
                            'ar' => 'الدمام',
                            'en' => 'Dammam',
                            'fr' => 'Dammam'
                        ],
                        [
                            'ar' => 'مكة المكرمة',
                            'en' => 'Makkah',
                            'fr' => 'La Mecque'
                        ],
                        [
                            'ar' => 'المدينة المنورة',
                            'en' => 'Madinah',
                            'fr' => 'Médine'
                        ]
                    ]
                ],
                'field_name' => 'city',
                'field_type' => 'select',
                'is_required' => true,
                'field_order' => 2,
                
            ],
            [
                'section_id' => 4,
                'translations' => [
                    'field_label' => [
                        'ar' => 'الموقع الإلكتروني',
                        'en' => 'Website',
                        'fr' => 'Site Web'
                    ],
                    'placeholder' => [
                        'ar' => 'https://example.com',
                        'en' => 'https://example.com',
                        'fr' => 'https://exemple.com'
                    ]
                ],
                'field_name' => 'website',
                'field_type' => 'url',
                'is_required' => false,
                'field_order' => 3
            ],
            [
                'section_id' => 4,
                'translations' => [
                    'field_label' => [
                        'ar' => 'رقم الهاتف الثابت',
                        'en' => 'Landline Number',
                        'fr' => 'Numéro de téléphone fixe'
                    ],
                    'placeholder' => [
                        'ar' => '011XXXXXXX',
                        'en' => '011XXXXXXX',
                        'fr' => '011XXXXXXX'
                    ]
                ],
                'field_name' => 'landline',
                'field_type' => 'tel',
                'is_required' => false,
                'field_order' => 4
            ],
        ];


        foreach ($fieldsData as $data) {
            $this->createField($data);
        }
    }
}
