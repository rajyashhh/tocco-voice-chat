<?php

namespace  Modules\Form\Database\Seeders;

use Illuminate\Database\Seeder;

class CustomFieldWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $widgets = [
            [
                'widget_type' => 'bd_selector',
                'widget_name' => [
                    'ar' => 'محدد مدير الأعمال',
                    'en' => 'Business Development Selector',
                    'fr' => 'Sélecteur de Développement Commercial',
                ],
                'description' => [
                    'ar' => 'اختر واحد أو أكثر من مديري تطوير الأعمال',
                    'en' => 'Select one or more Business Development managers',
                    'fr' => 'Sélectionnez un ou plusieurs responsables du développement commercial',
                ],
                'component_path' => 'components.widgets.bd-selector',
                'default_config' => [
                    'data_source' => 'users',
                    'roles' => ['admin', 'owner'], // Can filter by specific roles
                    'allow_search' => true,
                    'show_email' => true,
                    'show_role' => true,
                ],
                'allows_multiple' => true,
                'is_active' => true,
            ],
            [
                'widget_type' => 'user_picker',
                'widget_name' => [
                    'ar' => 'محدد المستخدم',
                    'en' => 'User Picker',
                    'fr' => 'Sélecteur d\'utilisateur',
                ],
                'description' => [
                    'ar' => 'اختر مستخدم من القائمة',
                    'en' => 'Pick a user from the list',
                    'fr' => 'Choisissez un utilisateur dans la liste',
                ],
                'component_path' => 'components.widgets.user-picker',
                'default_config' => [
                    'data_source' => 'users',
                    'allow_search' => true,
                ],
                'allows_multiple' => false,
                'is_active' => true,
            ],
            [
                'widget_type' => 'agency_selector',
                'widget_name' => [
                    'ar' => 'محدد الوكالة',
                    'en' => 'Agency Selector',
                    'fr' => 'Sélecteur d\'agence',
                ],
                'description' => [
                    'ar' => 'اختر وكالة من الوكالات المعتمدة',
                    'en' => 'Select an agency from approved agencies',
                    'fr' => 'Sélectionnez une agence parmi les agences approuvées',
                ],
                'component_path' => 'components.widgets.agency-selector',
                'default_config' => [
                    'data_source' => 'api',
                    'api_endpoint' => '/api/widgets/agencies',
                    'status_filter' => 'approved',
                ],
                'allows_multiple' => false,
                'is_active' => true,
            ],
            [
                'widget_type' => 'previous_experiences', 
                'widget_name' => [
                    'ar' => 'الخبرات السابقة في التطبيقات',
                    'en' => 'Previous App Experience',
                    'fr' => 'Expérience précédente dans les applications',
                ],
                'description' => [
                    'ar' => 'أضف خبرة التطبيقات السابقة مع تفاصيل الاسم والمدة',
                    'en' => 'Add previous application experience with app name and work duration',
                    'fr' => 'Ajouter une expérience précédente de l\'application avec nom et durée',
                ],
                'component_path' => 'components.widgets.previous-experiences',
                'default_config' => [
                    'fields' => [
                        ['name' => 'app_name', 'label' => ['en' => 'Application Name', 'ar' => 'اسم التطبيق'], 'type' => 'text'],
                        ['name' => 'work_duration', 'label' => ['en' => 'Work Duration (months)', 'ar' => 'مدة العمل (بالشهور)'], 'type' => 'number'],
                    ],
                    'allow_add_more' => true,
                    'min_items' => 0,
                    'max_items' => 10,
                ],
                'allows_multiple' => true,
                'is_active' => true,
            ],
        ];

        foreach ($widgets as $widgetData) {
            \Modules\Form\Entities\CustomFieldWidget::create($widgetData);
        }
    }
}
