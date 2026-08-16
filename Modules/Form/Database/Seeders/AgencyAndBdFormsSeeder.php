<?php

namespace  Modules\Form\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Form\Entities\CustomFieldWidget;
use Modules\Form\Entities\FormField;
use Modules\Form\Entities\FormSection;
use Modules\Form\Entities\FormTemplate;


class AgencyAndBdFormsSeeder extends Seeder
{
    public function run(): void
    {
   
    
    $shippingAgency = [
        [
            'label' => ['en' => 'Agency Name', 'ar' => 'اسم الوكالة', 'hi' => 'एजेंसी नाम', 'tr' => 'Ajans Adı'],
            'name' => 'agency_name',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter Agency Name', 'ar' => 'أدخل اسم الوكالة', 'hi' => 'एजेंसी नाम दर्ज करें', 'tr' => 'Ajans Adı girin'],
            'order' => 1,
            'data_source' => null,

        ],
        [
            'label' => ['en' => 'WhatsApp Number', 'ar' => 'رقم الواتساب', 'hi' => 'व्हाट्सएप नंबर', 'tr' => 'WhatsApp Numarası'],
            'name' => 'whatsapp_number',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter WhatsApp number', 'ar' => 'أدخل رقم الواتساب', 'hi' => 'व्हाट्सएप नंबर दर्ज करें', 'tr' => 'WhatsApp Numarası girin'],
            'order' => 2,
            'data_source' => null,

        ],
    ];
    

    
    $this->createForm(
        ['en' => 'Shipping Agency Form', 'ar' => 'نموذج وكالة شحن', 'hi' => 'शिपिंग एजेंसी फ़ॉर्म', 'tr' => 'Nakliye Ajansı Formu'],
        'shipping_agency',
        'agency',
        ['en' => 'Form to register a shipping agency', 'ar' => 'نموذج لتسجيل وكالة شحن', 'hi' => 'शिपिंग एजेंसी पंजीकरण फ़ॉर्म', 'tr' => 'Nakliye ajansı kaydı için form'],
        $shippingAgency
    );
    
    $this->createForm(
        ['en' => 'BD Registration Form', 'ar' => 'نموذج تسجيل BD', 'hi' => 'BD पंजीकरण फ़ॉर्म', 'tr' => 'BD Kayıt Formu'],
        'bd_form',
        'bd',
        ['en' => 'Form to add BD information', 'ar' => 'نموذج لإضافة معلومات BD', 'hi' => 'BD जानकारी जोड़ने का फ़ॉर्म', 'tr' => 'BD bilgilerini eklemek için form'],
        [
            [
                'label' => ['en' => 'BD Name', 'ar' => 'اسم BD', 'hi' => 'BD नाम', 'tr' => 'BD Adı'],
                'name' => 'bd_name',
                'type' => 'text',
                'placeholder' => ['en' => 'Enter BD name', 'ar' => 'أدخل اسم BD', 'hi' => 'BD नाम दर्ज करें', 'tr' => 'BD Adı girin'],
                'order' => 1,
                'data_source' => null,

            ],
            [
                'label' => ['en' => 'Country', 'ar' => 'الدولة', 'hi' => 'देश', 'tr' => 'Ülke'],
                'name' => 'country',
                'type' => 'select',
                'data_source' => 'countries',
                'placeholder' => ['en' => 'Select country', 'ar' => 'اختر الدولة', 'hi' => 'देश चुनें', 'tr' => 'Ülke seçin'],
                'order' => 2,
            ],
        ]
    );



      // ====== إنشاء نموذج وكالة المضيفين (Host Agency Form) ======
      $template = FormTemplate::create([
        'title' => [
            'en' => 'Host Agency Form',
            'ar' => 'نموذج وكالة مضيفين',
            'hi' => 'होस्ट एजेंसी फ़ॉर्म',
            'tr' => 'Ev Sahibi Ajans Formu'
        ],
        'form_type' => 'host_agency',
        'description' => [
            'ar' => "💡 ليس لديك شركة رسمية؟\nيُنصح بتأسيس شركة قانونية في دولتك لضمان عمل احترافي\nالأنشطة المقترحة للتسجيل:\n- خدمات التسويق الإلكتروني والإعلان الرقمي\n- إدارة المحتوى والوسائط المتعددة\n- خدمات الاستشارات الإدارية والتسويقية\n- التجارة الإلكترونية وإدارة المنصات الرقمية\n- خدمات العلاقات العامة والتواصل الاجتماعي\nتوجه لمكتب محاماة أو محاسب قانوني للمساعدة في التأسيس",
            
            'en' => "💡 Don't have a registered company?\nIt is recommended to establish a legal company in your country to ensure professional work.\nSuggested registration activities:\n- Digital marketing and advertising services\n- Content and multimedia management\n- Administrative and marketing consulting services\n- E-commerce and digital platform management\n- Public relations and social communication services\nConsult a lawyer or accountant for assistance in registration.",
            
            'hi' => "💡 क्या आपकी कोई आधिकारिक कंपनी नहीं है?\nपेशेवर काम सुनिश्चित करने के लिए अपने देश में एक कानूनी कंपनी स्थापित करने की सिफारिश की जाती है।\nपंजीकरण के लिए सुझाई गई गतिविधियाँ:\n- डिजिटल मार्केटिंग और विज्ञापन सेवाएँ\n- सामग्री और मल्टीमीडिया प्रबंधन\n- प्रशासनिक और विपणन परामर्श सेवाएँ\n- ई-कॉमर्स और डिजिटल प्लेटफ़ॉर्म प्रबंधन\n- जनसंपर्क और सामाजिक संचार सेवाएँ\nपंजीकरण में सहायता के लिए किसी वकील या लेखाकार से परामर्श करें।",
            
            'tr' => "💡 Resmi bir şirketiniz yok mu?\nProfesyonel çalışma sağlamak için ülkenizde yasal bir şirket kurmanız önerilir.\nÖnerilen kayıt faaliyetleri:\n- Dijital pazarlama ve reklam hizmetleri\n- İçerik ve multimedya yönetimi\n- İdari ve pazarlama danışmanlık hizmetleri\n- E-ticaret ve dijital platform yönetimi\n- Halkla ilişkiler ve sosyal iletişim hizmetleri\nKayıt konusunda yardım almak için bir avukata veya muhasebeciye danışın.",
        ],
    
        'is_active' => true,
        'can_not_delete' => true
    ]);

    /*
    |--------------------------------------------------------------------------
    | القسم الأول: البيانات الشخصية
    |--------------------------------------------------------------------------
    */
    $personalSection = FormSection::create([
        'form_template_id' => $template->id,
        'title' => [
            'en' => 'Personal Information',
            'ar' => 'البيانات الشخصية',
            'hi' => 'व्यक्तिगत जानकारी',
            'tr' => 'Kişisel Bilgiler'
        ],
        'section_order' => 1,
        'is_visible' => true,
        'can_not_delete' => false
    ]);

    $personalFields = [
        [
            'label' => ['en' => 'Full Name', 'ar' => 'الاسم الكامل'],
            'name' => 'full_name',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter your full name', 'ar' => 'أدخل اسمك الكامل'],
            'order' => 1,
        ],
        [
            'label' => ['en' => 'Phone Number', 'ar' => 'رقم الهاتف'],
            'name' => 'phone_number',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter phone number', 'ar' => 'أدخل رقم الهاتف'],
            'order' => 2,
        ],
        [
            'label' => ['en' => 'Email Address', 'ar' => 'البريد الإلكتروني'],
            'name' => 'email',
            'type' => 'email',
            'placeholder' => ['en' => 'Enter email', 'ar' => 'أدخل البريد الإلكتروني'],
            'order' => 3,
        ],
        [
            'label' => ['en' => 'National ID Front', 'ar' => 'صورة البطاقة (الوجه)'],
            'name' => 'id_front',
            'type' => 'file',
            'placeholder' => ['en' => 'Upload ID front image', 'ar' => 'قم برفع صورة الوجه للبطاقة'],
            'order' => 4,
        ],
        [
            'label' => ['en' => 'National ID Back', 'ar' => 'صورة البطاقة (الظهر)'],
            'name' => 'id_back',
            'type' => 'file',
            'placeholder' => ['en' => 'Upload ID back image', 'ar' => 'قم برفع صورة الظهر للبطاقة'],
            'order' => 5,
        ],
        [
            'label' => ['en' => 'Company Document', 'ar' => 'مستند الشركة'],
            'name' => 'company_document',
            'type' => 'file',
            'placeholder' => ['en' => 'Upload company registration document', 'ar' => 'قم برفع مستند الشركة'],
            'order' => 6,
        ],
   
    ];

    foreach ($personalFields as $field) {
        FormField::create([
            'section_id' => $personalSection->id,
            'field_label' => $field['label'],
            'field_name' => $field['name'],
            'field_type' => $field['type'],
            'placeholder' => $field['placeholder'] ?? null,
            'options' => $field['options'] ?? null,
            'is_required' => true,
            'is_enabled' => true,
            'can_not_delete' => false,
            'field_order' => $field['order'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | القسم الثاني: بيانات الوكالة الأساسية
    |--------------------------------------------------------------------------
    */
    $agencySection = FormSection::create([
        'form_template_id' => $template->id,
        'title' => [
            'en' => 'Agency Basic Information',
            'ar' => 'بيانات الوكالة الأساسية',
            'hi' => 'एजेंसी की मूल जानकारी',
            'tr' => 'Ajans Temel Bilgileri'
        ],
        'section_order' => 2,
        'is_visible' => true,
        'can_not_delete' => true
    ]);

    $agencyFields = [
        [
            'label' => ['en' => 'Agency Name', 'ar' => 'اسم الوكالة'],
            'name' => 'agency_name',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter agency name', 'ar' => 'أدخل اسم الوكالة'],
            'can_not_delete' => true,
            'order' => 1,
        ],
        [
            'label' => ['en' => 'WhatsApp Number', 'ar' => 'رقم الواتساب'],
            'name' => 'whatsapp_number',
            'type' => 'text',
            'placeholder' => ['en' => 'Enter WhatsApp number', 'ar' => 'أدخل رقم الواتساب'],
            'can_not_delete' => true,
            'order' => 2,
        ],
        [
            'label' => ['en' => 'Total Salaries', 'ar' => 'مجموع الرواتب في التطبيق الأخير'],
            'name' => 'total_salaries',
            'type' => 'number',
            'placeholder' => ['en' => 'Enter total salaries', 'ar' => 'أدخل مجموع الرواتب'],
            'can_not_delete' => false,
            'order' => 3,
        ],
        [
            'label' => ['en' => 'Expected Hosts This Month', 'ar' => 'عدد المضيفين المتوقع تسجيلهم خلال شهر'],
            'name' => 'expected_hosts',
            'type' => 'number',
            'placeholder' => ['en' => 'Enter expected host count', 'ar' => 'أدخل عدد المضيفين المتوقع'],
            'can_not_delete' => false,
            'order' => 4,
        ],
    ];

    foreach ($agencyFields as $field) {
        FormField::create([
            'section_id' => $agencySection->id,
            'field_label' => $field['label'],
            'field_name' => $field['name'],
            'field_type' => $field['type'],
            'placeholder' => $field['placeholder'] ?? null,
            'is_required' => true,
            'is_enabled' => true,
            'can_not_delete' => $field['can_not_delete'] ?? false,
            'field_order' => $field['order'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | القسم الثالث: الخبرات السابقة في التطبيقات
    |--------------------------------------------------------------------------
    */
    $widget = CustomFieldWidget::where('widget_type','previous_experiences'  )->first();
    $experienceSection = FormSection::create([
        'form_template_id' => $template->id,
        'title' => [
        'en' => 'Previous App Experience',
        'ar' => 'الخبرات السابقة في التطبيقات',
        'hi' => 'एप्लिकेशन में पिछला अनुभव',
        'tr' => 'Önceki Uygulama Deneyimleri'
        ],
        'section_order' => 3,
        'is_visible' => true,
        'can_not_delete' => false
        ]);
        
        FormField::create([
        'section_id' => $experienceSection->id,
        'widget_id' => $widget->id,
        'field_label' => [
        'en' => 'Application Experience',
        'ar' => 'الخبرات السابقة',
        'hi' => 'पिछला अनुभव',
        'tr' => 'Uygulama Deneyimi'
        ],
        'field_name' => 'previous_experiences',
        'field_type' => 'custom', // نوع الحقل custom
        'placeholder' => [
        'en' => 'Add application experience',
        'ar' => 'أضف خبرة تطبيق',
        'hi' => 'अनुभव जोड़ें',
        'tr' => 'Deneyim ekle'
        ],
        'is_required' => false,
        'is_enabled' => true,
        'can_not_delete' => false,
        'field_order' => 1,
        'field_options' => json_encode([ 
        'fields' => [
        ['name' => 'app_name', 'label' => ['en' => 'Application Name', 'ar' => 'اسم التطبيق'], 'type' => 'text'],
        ['name' => 'work_duration', 'label' => ['en' => 'Work Duration (months)', 'ar' => 'مدة العمل (بالشهور)'], 'type' => 'number'],
        ],
        'allow_add_more' => true,
        'min_items' => 0,
        'max_items' => 10
        ])
        ]);
        
    
    }
    
    private function createForm(array $title, string $formType, string $category, array $desc, array $fields)
    {
    $template = FormTemplate::create([
    'title' => $title,
    'form_type' => $formType,
    'description' => $desc,
    'is_active' => true,
    'can_not_delete'=>true
    ]);
    
    $section = FormSection::create([
        'form_template_id' => $template->id,
        'title' => ['en' => 'Main Section', 'ar' => 'القسم الرئيسي', 'hi' => 'मुख्य अनुभाग', 'tr' => 'Ana Bölüm'],
        'section_order' => 1,
        'is_visible' => true,
        'can_not_delete' =>true
    ]);
    
    foreach ($fields as $field) {
        FormField::create([
            'section_id' => $section->id,
            'field_label' => $field['label'],
            'field_name' => $field['name'],
            'field_type' => $field['type'],
            'placeholder' => $field['placeholder'] ?? null,
            'options' => $field['options'] ?? null,
            'is_required' => true,
            'is_enabled' => true,
            'can_not_delete' => true,
            'field_order' => $field['order'],
            'data_source'=>$field['data_source']
        ]);
    }
            
    }
    
    
}



