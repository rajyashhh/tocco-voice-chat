<?php

namespace Modules\Form\Http\Controllers\Api;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Form\Entities\FormTemplate;

class DataSourceController extends Controller
{
    /**
     * Get predefined data by source type
     */
    public function getData(Request $request, $source)
    {
        $locale = $request->input('locale', app()->getLocale());
        
        switch ($source) {
            case 'countries':
                return response()->json($this->getCountries($locale));
            
            case 'cities':
                $country = $request->input('country');
                return response()->json($this->getCities($country, $locale));
            
            case 'languages':
                return response()->json($this->getLanguages($locale));
            
            case 'currencies':
                return response()->json($this->getCurrencies($locale));
            
            default:
                return response()->json(['error' => 'Invalid data source'], 400);
        }
    }

    /**
     * Get list of countries
     */
    private function getCountries($locale)
    {
        $countries = [
            'US' => ['en' => 'United States', 'ar' => 'الولايات المتحدة', 'fr' => 'États-Unis'],
            'GB' => ['en' => 'United Kingdom', 'ar' => 'المملكة المتحدة', 'fr' => 'Royaume-Uni'],
            'CA' => ['en' => 'Canada', 'ar' => 'كندا', 'fr' => 'Canada'],
            'AU' => ['en' => 'Australia', 'ar' => 'أستراليا', 'fr' => 'Australie'],
            'DE' => ['en' => 'Germany', 'ar' => 'ألمانيا', 'fr' => 'Allemagne'],
            'FR' => ['en' => 'France', 'ar' => 'فرنسا', 'fr' => 'France'],
            'IT' => ['en' => 'Italy', 'ar' => 'إيطاليا', 'fr' => 'Italie'],
            'ES' => ['en' => 'Spain', 'ar' => 'إسبانيا', 'fr' => 'Espagne'],
            'NL' => ['en' => 'Netherlands', 'ar' => 'هولندا', 'fr' => 'Pays-Bas'],
            'BE' => ['en' => 'Belgium', 'ar' => 'بلجيكا', 'fr' => 'Belgique'],
            'CH' => ['en' => 'Switzerland', 'ar' => 'سويسرا', 'fr' => 'Suisse'],
            'AT' => ['en' => 'Austria', 'ar' => 'النمسا', 'fr' => 'Autriche'],
            'SE' => ['en' => 'Sweden', 'ar' => 'السويد', 'fr' => 'Suède'],
            'NO' => ['en' => 'Norway', 'ar' => 'النرويج', 'fr' => 'Norvège'],
            'DK' => ['en' => 'Denmark', 'ar' => 'الدنمارك', 'fr' => 'Danemark'],
            'FI' => ['en' => 'Finland', 'ar' => 'فنلندا', 'fr' => 'Finlande'],
            'PL' => ['en' => 'Poland', 'ar' => 'بولندا', 'fr' => 'Pologne'],
            'PT' => ['en' => 'Portugal', 'ar' => 'البرتغال', 'fr' => 'Portugal'],
            'GR' => ['en' => 'Greece', 'ar' => 'اليونان', 'fr' => 'Grèce'],
            'TR' => ['en' => 'Turkey', 'ar' => 'تركيا', 'fr' => 'Turquie'],
            'SA' => ['en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية', 'fr' => 'Arabie Saoudite'],
            'AE' => ['en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة', 'fr' => 'Émirats Arabes Unis'],
            'EG' => ['en' => 'Egypt', 'ar' => 'مصر', 'fr' => 'Égypte'],
            'MA' => ['en' => 'Morocco', 'ar' => 'المغرب', 'fr' => 'Maroc'],
            'DZ' => ['en' => 'Algeria', 'ar' => 'الجزائر', 'fr' => 'Algérie'],
            'TN' => ['en' => 'Tunisia', 'ar' => 'تونس', 'fr' => 'Tunisie'],
            'JO' => ['en' => 'Jordan', 'ar' => 'الأردن', 'fr' => 'Jordanie'],
            'LB' => ['en' => 'Lebanon', 'ar' => 'لبنان', 'fr' => 'Liban'],
            'KW' => ['en' => 'Kuwait', 'ar' => 'الكويت', 'fr' => 'Koweït'],
            'QA' => ['en' => 'Qatar', 'ar' => 'قطر', 'fr' => 'Qatar'],
            'BH' => ['en' => 'Bahrain', 'ar' => 'البحرين', 'fr' => 'Bahreïn'],
            'OM' => ['en' => 'Oman', 'ar' => 'عمان', 'fr' => 'Oman'],
            'IQ' => ['en' => 'Iraq', 'ar' => 'العراق', 'fr' => 'Irak'],
            'SY' => ['en' => 'Syria', 'ar' => 'سوريا', 'fr' => 'Syrie'],
            'PS' => ['en' => 'Palestine', 'ar' => 'فلسطين', 'fr' => 'Palestine'],
            'JP' => ['en' => 'Japan', 'ar' => 'اليابان', 'fr' => 'Japon'],
            'CN' => ['en' => 'China', 'ar' => 'الصين', 'fr' => 'Chine'],
            'KR' => ['en' => 'South Korea', 'ar' => 'كوريا الجنوبية', 'fr' => 'Corée du Sud'],
            'IN' => ['en' => 'India', 'ar' => 'الهند', 'fr' => 'Inde'],
            'PK' => ['en' => 'Pakistan', 'ar' => 'باكستان', 'fr' => 'Pakistan'],
            'BD' => ['en' => 'Bangladesh', 'ar' => 'بنغلاديش', 'fr' => 'Bangladesh'],
            'ID' => ['en' => 'Indonesia', 'ar' => 'إندونيسيا', 'fr' => 'Indonésie'],
            'MY' => ['en' => 'Malaysia', 'ar' => 'ماليزيا', 'fr' => 'Malaisie'],
            'SG' => ['en' => 'Singapore', 'ar' => 'سنغافورة', 'fr' => 'Singapour'],
            'TH' => ['en' => 'Thailand', 'ar' => 'تايلاند', 'fr' => 'Thaïlande'],
            'VN' => ['en' => 'Vietnam', 'ar' => 'فيتنام', 'fr' => 'Vietnam'],
            'PH' => ['en' => 'Philippines', 'ar' => 'الفلبين', 'fr' => 'Philippines'],
            'BR' => ['en' => 'Brazil', 'ar' => 'البرازيل', 'fr' => 'Brésil'],
            'MX' => ['en' => 'Mexico', 'ar' => 'المكسيك', 'fr' => 'Mexique'],
            'AR' => ['en' => 'Argentina', 'ar' => 'الأرجنتين', 'fr' => 'Argentine'],
            'CL' => ['en' => 'Chile', 'ar' => 'تشيلي', 'fr' => 'Chili'],
            'CO' => ['en' => 'Colombia', 'ar' => 'كولومبيا', 'fr' => 'Colombie'],
            'ZA' => ['en' => 'South Africa', 'ar' => 'جنوب أفريقيا', 'fr' => 'Afrique du Sud'],
            'NG' => ['en' => 'Nigeria', 'ar' => 'نيجيريا', 'fr' => 'Nigéria'],
            'KE' => ['en' => 'Kenya', 'ar' => 'كينيا', 'fr' => 'Kenya'],
            'RU' => ['en' => 'Russia', 'ar' => 'روسيا', 'fr' => 'Russie'],
        ];

        // Format for select dropdown
        $options = [];
        foreach ($countries as $code => $names) {
            $options[] = [
                'value' => $code,
                'label' => $names[$locale] ?? $names['en'],
            ];
        }

        return $options;
    }

    /**
     * Get list of cities (simplified - you can expand this)
     */
    private function getCities($country, $locale)
    {
        $cities = [
            'US' => [
                'NYC' => ['en' => 'New York', 'ar' => 'نيويورك'],
                'LA' => ['en' => 'Los Angeles', 'ar' => 'لوس أنجلوس'],
                'CHI' => ['en' => 'Chicago', 'ar' => 'شيكاغو'],
            ],
            'SA' => [
                'RUH' => ['en' => 'Riyadh', 'ar' => 'الرياض'],
                'JED' => ['en' => 'Jeddah', 'ar' => 'جدة'],
                'DAM' => ['en' => 'Dammam', 'ar' => 'الدمام'],
            ],
            'EG' => [
                'CAI' => ['en' => 'Cairo', 'ar' => 'القاهرة'],
                'ALX' => ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
                'GIZ' => ['en' => 'Giza', 'ar' => 'الجيزة'],
            ],
        ];

        $countryСities = $cities[$country] ?? [];
        $options = [];
        
        foreach ($countryСities as $code => $names) {
            $options[] = [
                'value' => $code,
                'label' => $names[$locale] ?? $names['en'],
            ];
        }

        return $options;
    }

    /**
     * Get list of languages
     */
    private function getLanguages($locale)
    {
        $languages = [
            'en' => ['en' => 'English', 'ar' => 'الإنجليزية', 'fr' => 'Anglais'],
            'ar' => ['en' => 'Arabic', 'ar' => 'العربية', 'fr' => 'Arabe'],
            'fr' => ['en' => 'French', 'ar' => 'الفرنسية', 'fr' => 'Français'],
            'es' => ['en' => 'Spanish', 'ar' => 'الإسبانية', 'fr' => 'Espagnol'],
            'de' => ['en' => 'German', 'ar' => 'الألمانية', 'fr' => 'Allemand'],
            'it' => ['en' => 'Italian', 'ar' => 'الإيطالية', 'fr' => 'Italien'],
            'pt' => ['en' => 'Portuguese', 'ar' => 'البرتغالية', 'fr' => 'Portugais'],
            'ru' => ['en' => 'Russian', 'ar' => 'الروسية', 'fr' => 'Russe'],
            'zh' => ['en' => 'Chinese', 'ar' => 'الصينية', 'fr' => 'Chinois'],
            'ja' => ['en' => 'Japanese', 'ar' => 'اليابانية', 'fr' => 'Japonais'],
            'ko' => ['en' => 'Korean', 'ar' => 'الكورية', 'fr' => 'Coréen'],
            'hi' => ['en' => 'Hindi', 'ar' => 'الهندية', 'fr' => 'Hindi'],
        ];

        $options = [];
        foreach ($languages as $code => $names) {
            $options[] = [
                'value' => $code,
                'label' => $names[$locale] ?? $names['en'],
            ];
        }

        return $options;
    }

    /**
     * Get list of currencies
     */
    private function getCurrencies($locale)
    {
        $currencies = [
            'USD' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي', 'symbol' => '$'],
            'EUR' => ['en' => 'Euro', 'ar' => 'يورو', 'symbol' => '€'],
            'GBP' => ['en' => 'British Pound', 'ar' => 'جنيه إسترليني', 'symbol' => '£'],
            'SAR' => ['en' => 'Saudi Riyal', 'ar' => 'ريال سعودي', 'symbol' => 'ر.س'],
            'AED' => ['en' => 'UAE Dirham', 'ar' => 'درهم إماراتي', 'symbol' => 'د.إ'],
            'EGP' => ['en' => 'Egyptian Pound', 'ar' => 'جنيه مصري', 'symbol' => 'ج.م'],
            'JPY' => ['en' => 'Japanese Yen', 'ar' => 'ين ياباني', 'symbol' => '¥'],
            'CNY' => ['en' => 'Chinese Yuan', 'ar' => 'يوان صيني', 'symbol' => '¥'],
            'INR' => ['en' => 'Indian Rupee', 'ar' => 'روبية هندية', 'symbol' => '₹'],
            'CAD' => ['en' => 'Canadian Dollar', 'ar' => 'دولار كندي', 'symbol' => 'C$'],
            'AUD' => ['en' => 'Australian Dollar', 'ar' => 'دولار أسترالي', 'symbol' => 'A$'],
        ];

        $options = [];
        foreach ($currencies as $code => $data) {
            $label = ($data[$locale] ?? $data['en']) . ' (' . $data['symbol'] . ')';
            $options[] = [
                'value' => $code,
                'label' => $label,
            ];
        }

        return $options;
    }


    public function formList(Request $request)
    {
        $type = $request->query('type');

        $query = FormTemplate::query()
            ->where('is_active', true);

        if ($type) {
            $query->where('form_type', $type);
        }

        $forms = $query->get(['id', 'title', 'form_type']);

        $formLinks = $forms->map(function ($form) use ($forms) {
            return [
                'form_type' => $form->form_type,
                'link' => "/forms?type={$form->form_type}&token=&lang=",
            ];
        });
        return Common::apiResponse(true, 'Success', $formLinks);

    
    }
}
