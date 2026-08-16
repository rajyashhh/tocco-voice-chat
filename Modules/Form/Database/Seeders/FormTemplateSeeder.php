<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Entities\FormTemplate;


class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = new FormTemplate();
        
        // Set translations properly using the HasTranslations trait methods
        $template->setTranslation('title', 'ar', 'نموذج تسجيل الوكالة');
        $template->setTranslation('title', 'en', 'Agency Registration Form');
        $template->setTranslation('title', 'fr', 'Formulaire d\'inscription d\'agence');
        
        $template->setTranslation('description', 'ar', 'نموذج شامل لتسجيل الوكالات التجارية');
        $template->setTranslation('description', 'en', 'Comprehensive form for registering commercial agencies');
        $template->setTranslation('description', 'fr', 'Formulaire complet pour l\'enregistrement des agences commerciales');

        $template->setTranslation('admin_notice', 'ar', 'يرجى ملء جميع الحقول المطلوبة بدقة. سيتم مراجعة طلبك خلال 48 ساعة.');
        $template->setTranslation('admin_notice', 'en', 'Please fill in all required fields accurately. Your request will be reviewed within 48 hours.');
        $template->setTranslation('admin_notice', 'fr', 'Veuillez remplir tous les champs obligatoires avec précision. Votre demande sera examinée dans les 48 heures.');
        
        $template->form_type = 'agency';
        $template->created_by = 1;
        $template->is_active = true;
        
        $template->save();
    }
}
