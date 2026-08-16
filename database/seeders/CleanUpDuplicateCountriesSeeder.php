<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Storage;
use Str;

class CleanUpDuplicateCountriesSeeder extends Seeder
{
    public function run()
    {
        $flagDir = public_path('images/flags');
        $flagFiles = glob($flagDir . '/*.svg');

        foreach ($flagFiles as $localPath) {
            $filename = basename($localPath);

            $gcsPath = 'images/flags/' . $filename;

            if (file_exists($localPath)) {
                Storage::disk('gcs')->put($gcsPath, file_get_contents($localPath), 'public');
            }
        }

        $this->command->info("=== Step 1: Cleaning up duplicates ===");

        $handledIds = [];

        $countries = Country::orderBy('id')->get();

        foreach ($countries as $base) {
            if (in_array($base->id, $handledIds)) {
                continue;
            }

            $similarOnes = Country::where('id', '!=', $base->id)
                ->where(function ($query) use ($base) {
                    $query->where('e_name', 'like', '%' . $base->e_name . '%');
                })
                ->orderBy('id')
                ->get();

            if ($similarOnes->isEmpty()) {
                continue;
            }

            $group = collect([$base])->merge($similarOnes)->sortBy('id');
            $keep = $group->first();
            $remove = $group->slice(1);

            foreach ($remove as $country) {
                if ($country->id == $keep->id) {
                    $this->command->error("⚠️ Tried to remove kept record ID {$keep->id}!");
                    continue;
                }

                $keep->iso        = $keep->iso ?: $country->iso;
                $keep->iso3       = $keep->iso3 ?: $country->iso3;
                $keep->name       = $keep->name ?: $country->name;
                $keep->e_name     = strlen($keep->e_name) < strlen($country->e_name) ? $country->e_name : $keep->e_name;
                $keep->status     = $keep->status ?: $country->status;
                $keep->phone_code = $keep->phone_code ?: $country->phone_code;
                $keep->save();

                $country->delete();
                $this->command->warn("🗑️ Removed duplicate: {$country->e_name} (ID {$country->id})");

                $handledIds[] = $country->id;
            }

            $handledIds[] = $keep->id;
            $this->command->info("✅ Kept oldest: {$keep->e_name} (ID {$keep->id})");
        }

        $this->command->info("🎉 Duplicate cleanup complete! All older records preserved.");

        $this->command->info("=== Step 2: Applying known fixes ===");

        $countries = array(
            array('iso'=>'AF','name'=>'Afghanistan','name_ar'=>'أفغانستان','iso3'=>'AFG','numcode'=>'4','phonecode'=>'93','iso_numeric'=>'004','currency_numeric'=>'971'),
            array('iso'=>'AL','name'=>'Albania','name_ar'=>'ألبانيا','iso3'=>'ALB','numcode'=>'8','phonecode'=>'355','iso_numeric'=>'008','currency_numeric'=>'008'),
            array('iso'=>'DZ','name'=>'Algeria','name_ar'=>'الجزائر','iso3'=>'DZA','numcode'=>'12','phonecode'=>'213','iso_numeric'=>'012','currency_numeric'=>'012'),
            array('iso'=>'AS','name'=>'American Samoa','name_ar'=>'ساموا الأمريكية','iso3'=>'ASM','numcode'=>'16','phonecode'=>'1684','iso_numeric'=>'016','currency_numeric'=>'840'),
            array('iso'=>'AD','name'=>'Andorra','name_ar'=>'أندورا','iso3'=>'AND','numcode'=>'20','phonecode'=>'376','iso_numeric'=>'020','currency_numeric'=>'978'),
            array('iso'=>'AO','name'=>'Angola','name_ar'=>'أنغولا','iso3'=>'AGO','numcode'=>'24','phonecode'=>'244','iso_numeric'=>'024','currency_numeric'=>'973'),
            array('iso'=>'AI','name'=>'Anguilla','name_ar'=>'أنغيلا','iso3'=>'AIA','numcode'=>'660','phonecode'=>'1264','iso_numeric'=>'660','currency_numeric'=>'951'),
            array('iso'=>'AQ','name'=>'Antarctica','name_ar'=>'أنتاركتيكا','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'0','iso_numeric'=>NULL,'currency_numeric'=>NULL),
            array('iso'=>'AG','name'=>'Antigua and Barbuda','name_ar'=>'أنتيغوا وبربودا','iso3'=>'ATG','numcode'=>'28','phonecode'=>'1268','iso_numeric'=>'028','currency_numeric'=>'951'),
            array('iso'=>'AR','name'=>'Argentina','name_ar'=>'الأرجنتين','iso3'=>'ARG','numcode'=>'32','phonecode'=>'54','iso_numeric'=>'032','currency_numeric'=>'032'),
            array('iso'=>'AM','name'=>'Armenia','name_ar'=>'أرمينيا','iso3'=>'ARM','numcode'=>'51','phonecode'=>'374','iso_numeric'=>'051','currency_numeric'=>'051'),
            array('iso'=>'AW','name'=>'Aruba','name_ar'=>'أروبا','iso3'=>'ABW','numcode'=>'533','phonecode'=>'297','iso_numeric'=>'533','currency_numeric'=>'533'),
            array('iso'=>'AU','name'=>'Australia','name_ar'=>'أستراليا','iso3'=>'AUS','numcode'=>'36','phonecode'=>'61','iso_numeric'=>'036','currency_numeric'=>'036'),
            array('iso'=>'AT','name'=>'Austria','name_ar'=>'النمسا','iso3'=>'AUT','numcode'=>'40','phonecode'=>'43','iso_numeric'=>'040','currency_numeric'=>'978'),
            array('iso'=>'AZ','name'=>'Azerbaijan','name_ar'=>'أذربيجان','iso3'=>'AZE','numcode'=>'31','phonecode'=>'994','iso_numeric'=>'031','currency_numeric'=>'944'),
            array('iso'=>'BS','name'=>'Bahamas','name_ar'=>'جزر البهاما','iso3'=>'BHS','numcode'=>'44','phonecode'=>'1242','iso_numeric'=>'044','currency_numeric'=>'044'),
            array('iso'=>'BH','name'=>'Bahrain','name_ar'=>'البحرين','iso3'=>'BHR','numcode'=>'48','phonecode'=>'973','iso_numeric'=>'048','currency_numeric'=>'048'),
            array('iso'=>'BD','name'=>'Bangladesh','name_ar'=>'بنغلاديش','iso3'=>'BGD','numcode'=>'50','phonecode'=>'880','iso_numeric'=>'050','currency_numeric'=>'050'),
            array('iso'=>'BB','name'=>'Barbados','name_ar'=>'بربادوس','iso3'=>'BRB','numcode'=>'52','phonecode'=>'1246','iso_numeric'=>'052','currency_numeric'=>'052'),
            array('iso'=>'BY','name'=>'Belarus','name_ar'=>'بيلاروسيا','iso3'=>'BLR','numcode'=>'112','phonecode'=>'375','iso_numeric'=>'112','currency_numeric'=>'933'),
            array('iso'=>'BE','name'=>'Belgium','name_ar'=>'بلجيكا','iso3'=>'BEL','numcode'=>'56','phonecode'=>'32','iso_numeric'=>'056','currency_numeric'=>'978'),
            array('iso'=>'BZ','name'=>'Belize','name_ar'=>'بليز','iso3'=>'BLZ','numcode'=>'84','phonecode'=>'501','iso_numeric'=>'084','currency_numeric'=>'084'),
            array('iso'=>'BJ','name'=>'Benin','name_ar'=>'بنين','iso3'=>'BEN','numcode'=>'204','phonecode'=>'229','iso_numeric'=>'204','currency_numeric'=>'952'),
            array('iso'=>'BM','name'=>'Bermuda','name_ar'=>'برمودا','iso3'=>'BMU','numcode'=>'60','phonecode'=>'1441','iso_numeric'=>'060','currency_numeric'=>'060'),
            array('iso'=>'BT','name'=>'Bhutan','name_ar'=>'بوتان','iso3'=>'BTN','numcode'=>'64','phonecode'=>'975','iso_numeric'=>'064','currency_numeric'=>'064'),
            array('iso'=>'BO','name'=>'Bolivia','name_ar'=>'بوليفيا','iso3'=>'BOL','numcode'=>'68','phonecode'=>'591','iso_numeric'=>'068','currency_numeric'=>'068'),
            array('iso'=>'BA','name'=>'Bosnia and Herzegovina','name_ar'=>'البوسنة والهرسك','iso3'=>'BIH','numcode'=>'70','phonecode'=>'387','iso_numeric'=>'070','currency_numeric'=>'977'),
            array('iso'=>'BW','name'=>'Botswana','name_ar'=>'بوتسوانا','iso3'=>'BWA','numcode'=>'72','phonecode'=>'267','iso_numeric'=>'072','currency_numeric'=>'072'),
            array('iso'=>'BV','name'=>'Bouvet Island','name_ar'=>'جزيرة بوفيه','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'0','iso_numeric'=>NULL,'currency_numeric'=>NULL),
            array('iso'=>'BR','name'=>'Brazil','name_ar'=>'البرازيل','iso3'=>'BRA','numcode'=>'76','phonecode'=>'55','iso_numeric'=>'076','currency_numeric'=>'986'),
            array('iso'=>'IO','name'=>'British Indian Ocean Territory','name_ar'=>'إقليم المحيط الهندي البريطاني','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'246','iso_numeric'=>NULL,'currency_numeric'=>'826'),
            array('iso'=>'BN','name'=>'Brunei Darussalam','name_ar'=>'بروناي دار السلام','iso3'=>'BRN','numcode'=>'96','phonecode'=>'673','iso_numeric'=>'096','currency_numeric'=>'096'),
            array('iso'=>'BG','name'=>'Bulgaria','name_ar'=>'بلغاريا','iso3'=>'BGR','numcode'=>'100','phonecode'=>'359','iso_numeric'=>'100','currency_numeric'=>'975'),
            array('iso'=>'BF','name'=>'Burkina Faso','name_ar'=>'بوركينا فاسو','iso3'=>'BFA','numcode'=>'854','phonecode'=>'226','iso_numeric'=>'854','currency_numeric'=>'952'),
            array('iso'=>'BI','name'=>'Burundi','name_ar'=>'بوروندي','iso3'=>'BDI','numcode'=>'108','phonecode'=>'257','iso_numeric'=>'108','currency_numeric'=>'108'),
            array('iso'=>'KH','name'=>'Cambodia','name_ar'=>'كمبوديا','iso3'=>'KHM','numcode'=>'116','phonecode'=>'855','iso_numeric'=>'116','currency_numeric'=>'116'),
            array('iso'=>'CM','name'=>'Cameroon','name_ar'=>'الكاميرون','iso3'=>'CMR','numcode'=>'120','phonecode'=>'237','iso_numeric'=>'120','currency_numeric'=>'950'),
            array('iso'=>'CA','name'=>'Canada','name_ar'=>'كندا','iso3'=>'CAN','numcode'=>'124','phonecode'=>'1','iso_numeric'=>'124','currency_numeric'=>'124'),
            array('iso'=>'CV','name'=>'Cape Verde','name_ar'=>'الرأس الأخضر','iso3'=>'CPV','numcode'=>'132','phonecode'=>'238','iso_numeric'=>'132','currency_numeric'=>'132'),
            array('iso'=>'KY','name'=>'Cayman Islands','name_ar'=>'جزر كايمان','iso3'=>'CYM','numcode'=>'136','phonecode'=>'1345','iso_numeric'=>'136','currency_numeric'=>'136'),
            array('iso'=>'CF','name'=>'Central African Republic','name_ar'=>'جمهورية أفريقيا الوسطى','iso3'=>'CAF','numcode'=>'140','phonecode'=>'236','iso_numeric'=>'140','currency_numeric'=>'950'),
            array('iso'=>'TD','name'=>'Chad','name_ar'=>'تشاد','iso3'=>'TCD','numcode'=>'148','phonecode'=>'235','iso_numeric'=>'148','currency_numeric'=>'950'),
            array('iso'=>'CL','name'=>'Chile','name_ar'=>'تشيلي','iso3'=>'CHL','numcode'=>'152','phonecode'=>'56','iso_numeric'=>'152','currency_numeric'=>'152'),
            array('iso'=>'CN','name'=>'China','name_ar'=>'الصين','iso3'=>'CHN','numcode'=>'156','phonecode'=>'86','iso_numeric'=>'156','currency_numeric'=>'156'),
            array('iso'=>'CX','name'=>'Christmas Island','name_ar'=>'جزيرة كريسماس','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'61','iso_numeric'=>NULL,'currency_numeric'=>'036'),
            array('iso'=>'CC','name'=>'Cocos (Keeling) Islands','name_ar'=>'جزر كوكوس (كيلينغ)','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'672','iso_numeric'=>NULL,'currency_numeric'=>'036'),
            array('iso'=>'CO','name'=>'Colombia','name_ar'=>'كولومبيا','iso3'=>'COL','numcode'=>'170','phonecode'=>'57','iso_numeric'=>'170','currency_numeric'=>'170'),
            array('iso'=>'KM','name'=>'Comoros','name_ar'=>'جزر القمر','iso3'=>'COM','numcode'=>'174','phonecode'=>'269','iso_numeric'=>'174','currency_numeric'=>'174'),
            array('iso'=>'CG','name'=>'Congo','name_ar'=>'الكونغو','iso3'=>'COG','numcode'=>'178','phonecode'=>'242','iso_numeric'=>'178','currency_numeric'=>'950'),
            array('iso'=>'CD','name'=>'Congo, the Democratic Republic of the','name_ar'=>'جمهورية الكونغو الديمقراطية','iso3'=>'COD','numcode'=>'180','phonecode'=>'242','iso_numeric'=>'180','currency_numeric'=>'976'),
            array('iso'=>'CK','name'=>'Cook Islands','name_ar'=>'جزر كوك','iso3'=>'COK','numcode'=>'184','phonecode'=>'682','iso_numeric'=>'184','currency_numeric'=>'554'),

            array('iso'=>'CR','name'=>'Costa Rica','name_ar'=>'كوستاريكا','iso3'=>'CRI','numcode'=>'188','phonecode'=>'506','iso_numeric'=>'188','currency_numeric'=>'188'),
            array('iso'=>'CI','name'=>'Cote D\'Ivoire','name_ar'=>'ساحل العاج','iso3'=>'CIV','numcode'=>'384','phonecode'=>'225','iso_numeric'=>'384','currency_numeric'=>'952'),
            array('iso'=>'HR','name'=>'Croatia','name_ar'=>'كرواتيا','iso3'=>'HRV','numcode'=>'191','phonecode'=>'385','iso_numeric'=>'191','currency_numeric'=>'978'),
            array('iso'=>'CU','name'=>'Cuba','name_ar'=>'كوبا','iso3'=>'CUB','numcode'=>'192','phonecode'=>'53','iso_numeric'=>'192','currency_numeric'=>'192'),
            array('iso'=>'CY','name'=>'Cyprus','name_ar'=>'قبرص','iso3'=>'CYP','numcode'=>'196','phonecode'=>'357','iso_numeric'=>'196','currency_numeric'=>'978'),
            array('iso'=>'CZ','name'=>'Czech Republic','name_ar'=>'جمهورية التشيك','iso3'=>'CZE','numcode'=>'203','phonecode'=>'420','iso_numeric'=>'203','currency_numeric'=>'978'),
            array('iso'=>'DK','name'=>'Denmark','name_ar'=>'الدنمارك','iso3'=>'DNK','numcode'=>'208','phonecode'=>'45','iso_numeric'=>'208','currency_numeric'=>'208'),
            array('iso'=>'DJ','name'=>'Djibouti','name_ar'=>'جيبوتي','iso3'=>'DJI','numcode'=>'262','phonecode'=>'253','iso_numeric'=>'262','currency_numeric'=>'262'),
            array('iso'=>'DM','name'=>'Dominica','name_ar'=>'دومينيكا','iso3'=>'DMA','numcode'=>'212','phonecode'=>'1767','iso_numeric'=>'212','currency_numeric'=>'951'),
            array('iso'=>'DO','name'=>'Dominican Republic','name_ar'=>'جمهورية الدومينيكان','iso3'=>'DOM','numcode'=>'214','phonecode'=>'1809','iso_numeric'=>'214','currency_numeric'=>'214'),
            array('iso'=>'EC','name'=>'Ecuador','name_ar'=>'الإكوادور','iso3'=>'ECU','numcode'=>'218','phonecode'=>'593','iso_numeric'=>'218','currency_numeric'=>'218'),
            array('iso'=>'EG','name'=>'Egypt','name_ar'=>'مصر','iso3'=>'EGY','numcode'=>'818','phonecode'=>'20','iso_numeric'=>'818','currency_numeric'=>'818'),
            array('iso'=>'SV','name'=>'El Salvador','name_ar'=>'السلفادور','iso3'=>'SLV','numcode'=>'222','phonecode'=>'503','iso_numeric'=>'222','currency_numeric'=>'222'),
            array('iso'=>'GQ','name'=>'Equatorial Guinea','name_ar'=>'غينيا الاستوائية','iso3'=>'GNQ','numcode'=>'226','phonecode'=>'240','iso_numeric'=>'226','currency_numeric'=>'950'),
            array('iso'=>'ER','name'=>'Eritrea','name_ar'=>'إريتريا','iso3'=>'ERI','numcode'=>'232','phonecode'=>'291','iso_numeric'=>'232','currency_numeric'=>'232'),
            array('iso'=>'EE','name'=>'Estonia','name_ar'=>'إستونيا','iso3'=>'EST','numcode'=>'233','phonecode'=>'372','iso_numeric'=>'233','currency_numeric'=>'978'),
            array('iso'=>'ET','name'=>'Ethiopia','name_ar'=>'إثيوبيا','iso3'=>'ETH','numcode'=>'231','phonecode'=>'251','iso_numeric'=>'231','currency_numeric'=>'230'),
            array('iso'=>'FK','name'=>'Falkland Islands (Malvinas)','name_ar'=>'جزر فوكلاند','iso3'=>'FLK','numcode'=>'238','phonecode'=>'500','iso_numeric'=>'238','currency_numeric'=>'238'),
            array('iso'=>'FO','name'=>'Faroe Islands','name_ar'=>'جزر فارو','iso3'=>'FRO','numcode'=>'234','phonecode'=>'298','iso_numeric'=>'234','currency_numeric'=>'208'),
            array('iso'=>'FJ','name'=>'Fiji','name_ar'=>'فيجي','iso3'=>'FJI','numcode'=>'242','phonecode'=>'679','iso_numeric'=>'242','currency_numeric'=>'242'),
            array('iso'=>'FI','name'=>'Finland','name_ar'=>'فنلندا','iso3'=>'FIN','numcode'=>'246','phonecode'=>'358','iso_numeric'=>'246','currency_numeric'=>'978'),
            array('iso'=>'FR','name'=>'France','name_ar'=>'فرنسا','iso3'=>'FRA','numcode'=>'250','phonecode'=>'33','iso_numeric'=>'250','currency_numeric'=>'978'),
            array('iso'=>'GF','name'=>'French Guiana','name_ar'=>'غيانا الفرنسية','iso3'=>'GUF','numcode'=>'254','phonecode'=>'594','iso_numeric'=>'254','currency_numeric'=>'978'),
            array('iso'=>'PF','name'=>'French Polynesia','name_ar'=>'بولينيزيا الفرنسية','iso3'=>'PYF','numcode'=>'258','phonecode'=>'689','iso_numeric'=>'258','currency_numeric'=>'953'),
            array('iso'=>'TF','name'=>'French Southern Territories','name_ar'=>'الأقاليم الجنوبية الفرنسية','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'0','iso_numeric'=>NULL,'currency_numeric'=>'978'),
            array('iso'=>'GA','name'=>'Gabon','name_ar'=>'الغابون','iso3'=>'GAB','numcode'=>'266','phonecode'=>'241','iso_numeric'=>'266','currency_numeric'=>'950'),
            array('iso'=>'GM','name'=>'Gambia','name_ar'=>'غامبيا','iso3'=>'GMB','numcode'=>'270','phonecode'=>'220','iso_numeric'=>'270','currency_numeric'=>'270'),
            array('iso'=>'GE','name'=>'Georgia','name_ar'=>'جورجيا','iso3'=>'GEO','numcode'=>'268','phonecode'=>'995','iso_numeric'=>'268','currency_numeric'=>'981'),
            array('iso'=>'DE','name'=>'Germany','name_ar'=>'ألمانيا','iso3'=>'DEU','numcode'=>'276','phonecode'=>'49','iso_numeric'=>'276','currency_numeric'=>'978'),
            array('iso'=>'GH','name'=>'Ghana','name_ar'=>'غانا','iso3'=>'GHA','numcode'=>'288','phonecode'=>'233','iso_numeric'=>'288','currency_numeric'=>'288'),
            array('iso'=>'GI','name'=>'Gibraltar','name_ar'=>'جبل طارق','iso3'=>'GIB','numcode'=>'292','phonecode'=>'350','iso_numeric'=>'292','currency_numeric'=>'292'),
            array('iso'=>'GR','name'=>'Greece','name_ar'=>'اليونان','iso3'=>'GRC','numcode'=>'300','phonecode'=>'30','iso_numeric'=>'300','currency_numeric'=>'978'),
            array('iso'=>'GL','name'=>'Greenland','name_ar'=>'جرينلاند','iso3'=>'GRL','numcode'=>'304','phonecode'=>'299','iso_numeric'=>'304','currency_numeric'=>'208'),
            array('iso'=>'GD','name'=>'Grenada','name_ar'=>'غرينادا','iso3'=>'GRD','numcode'=>'308','phonecode'=>'1473','iso_numeric'=>'308','currency_numeric'=>'951'),
            array('iso'=>'GP','name'=>'Guadeloupe','name_ar'=>'جوادلوب','iso3'=>'GLP','numcode'=>'312','phonecode'=>'590','iso_numeric'=>'312','currency_numeric'=>'978'),
            array('iso'=>'GU','name'=>'Guam','name_ar'=>'غوام','iso3'=>'GUM','numcode'=>'316','phonecode'=>'1671','iso_numeric'=>'316','currency_numeric'=>'840'),
            array('iso'=>'GT','name'=>'Guatemala','name_ar'=>'غواتيمالا','iso3'=>'GTM','numcode'=>'320','phonecode'=>'502','iso_numeric'=>'320','currency_numeric'=>'320'),
            array('iso'=>'GN','name'=>'Guinea','name_ar'=>'غينيا','iso3'=>'GIN','numcode'=>'324','phonecode'=>'224','iso_numeric'=>'324','currency_numeric'=>'324'),
            array('iso'=>'GW','name'=>'Guinea-Bissau','name_ar'=>'غينيا بيساو','iso3'=>'GNB','numcode'=>'624','phonecode'=>'245','iso_numeric'=>'624','currency_numeric'=>'952'),
            array('iso'=>'GY','name'=>'Guyana','name_ar'=>'غيانا','iso3'=>'GUY','numcode'=>'328','phonecode'=>'592','iso_numeric'=>'328','currency_numeric'=>'328'),
            array('iso'=>'HT','name'=>'Haiti','name_ar'=>'هايتي','iso3'=>'HTI','numcode'=>'332','phonecode'=>'509','iso_numeric'=>'332','currency_numeric'=>'332'),
            array('iso'=>'HM','name'=>'Heard Island and Mcdonald Islands','name_ar'=>'جزيرة هيرد وجزر ماكدونالد','iso3'=>NULL,'numcode'=>NULL,'phonecode'=>'0','iso_numeric'=>NULL,'currency_numeric'=>'036'),
            array('iso'=>'VA','name'=>'Holy See (Vatican City State)','name_ar'=>'الفاتيكان','iso3'=>'VAT','numcode'=>'336','phonecode'=>'39','iso_numeric'=>'336','currency_numeric'=>'978'),
            array('iso'=>'HN','name'=>'Honduras','name_ar'=>'هندوراس','iso3'=>'HND','numcode'=>'340','phonecode'=>'504','iso_numeric'=>'340','currency_numeric'=>'340'),
            array('iso'=>'HK','name'=>'Hong Kong','name_ar'=>'هونغ كونغ','iso3'=>'HKG','numcode'=>'344','phonecode'=>'852','iso_numeric'=>'344','currency_numeric'=>'344'),
            array('iso'=>'HU','name'=>'Hungary','name_ar'=>'المجر','iso3'=>'HUN','numcode'=>'348','phonecode'=>'36','iso_numeric'=>'348','currency_numeric'=>'348'),
            array('iso'=>'IS','name'=>'Iceland','name_ar'=>'آيسلندا','iso3'=>'ISL','numcode'=>'352','phonecode'=>'354','iso_numeric'=>'352','currency_numeric'=>'352'),
            array('iso'=>'IN','name'=>'India','name_ar'=>'الهند','iso3'=>'IND','numcode'=>'356','phonecode'=>'91','iso_numeric'=>'356','currency_numeric'=>'356'),
            array('iso'=>'ID','name'=>'Indonesia','name_ar'=>'إندونيسيا','iso3'=>'IDN','numcode'=>'360','phonecode'=>'62','iso_numeric'=>'360','currency_numeric'=>'360'),
            array('iso'=>'IR','name'=>'Iran, Islamic Republic of','name_ar'=>'إيران','iso3'=>'IRN','numcode'=>'364','phonecode'=>'98','iso_numeric'=>'364','currency_numeric'=>'364'),
            array('iso'=>'IQ','name'=>'Iraq','name_ar'=>'العراق','iso3'=>'IRQ','numcode'=>'368','phonecode'=>'964','iso_numeric'=>'368','currency_numeric'=>'368'),

            array('iso' => 'IE', 'name' => 'Ireland', 'name_ar' => 'أيرلندا', 'iso3' => 'IRL', 'numcode' => '372', 'phonecode' => '353', 'iso_numeric' => '372', 'currency_numeric' => '978'),
            array('iso' => 'IL', 'name' => 'Israel', 'name_ar' => 'إسرائيل', 'iso3' => 'ISR', 'numcode' => '376', 'phonecode' => '972', 'iso_numeric' => '376', 'currency_numeric' => '376'),
            array('iso' => 'IT', 'name' => 'Italy', 'name_ar' => 'إيطاليا', 'iso3' => 'ITA', 'numcode' => '380', 'phonecode' => '39', 'iso_numeric' => '380', 'currency_numeric' => '978'),
            array('iso' => 'JM', 'name' => 'Jamaica', 'name_ar' => 'جامايكا', 'iso3' => 'JAM', 'numcode' => '388', 'phonecode' => '1876', 'iso_numeric' => '388', 'currency_numeric' => '388'),
            array('iso' => 'JP', 'name' => 'Japan', 'name_ar' => 'اليابان', 'iso3' => 'JPN', 'numcode' => '392', 'phonecode' => '81', 'iso_numeric' => '392', 'currency_numeric' => '392'),
            array('iso' => 'JO', 'name' => 'Jordan', 'name_ar' => 'الأردن', 'iso3' => 'JOR', 'numcode' => '400', 'phonecode' => '962', 'iso_numeric' => '400', 'currency_numeric' => '400'),
            array('iso' => 'KZ', 'name' => 'Kazakhstan', 'name_ar' => 'كازاخستان', 'iso3' => 'KAZ', 'numcode' => '398', 'phonecode' => '7', 'iso_numeric' => '398', 'currency_numeric' => '398'),
            array('iso' => 'KE', 'name' => 'Kenya', 'name_ar' => 'كينيا', 'iso3' => 'KEN', 'numcode' => '404', 'phonecode' => '254', 'iso_numeric' => '404', 'currency_numeric' => '404'),
            array('iso' => 'KI', 'name' => 'Kiribati', 'name_ar' => 'كيريباتي', 'iso3' => 'KIR', 'numcode' => '296', 'phonecode' => '686', 'iso_numeric' => '296', 'currency_numeric' => '036'),
            array('iso' => 'KP', 'name' => 'Korea, Democratic People\'s Republic of', 'name_ar' => 'كوريا الشمالية', 'iso3' => 'PRK', 'numcode' => '408', 'phonecode' => '850', 'iso_numeric' => '408', 'currency_numeric' => '408'),
            array('iso' => 'KR', 'name' => 'Korea, Republic of', 'name_ar' => 'كوريا الجنوبية', 'iso3' => 'KOR', 'numcode' => '410', 'phonecode' => '82', 'iso_numeric' => '410', 'currency_numeric' => '410'),
            array('iso' => 'KW', 'name' => 'Kuwait', 'name_ar' => 'الكويت', 'iso3' => 'KWT', 'numcode' => '414', 'phonecode' => '965', 'iso_numeric' => '414', 'currency_numeric' => '414'),
            array('iso' => 'KG', 'name' => 'Kyrgyzstan', 'name_ar' => 'قيرغيزستان', 'iso3' => 'KGZ', 'numcode' => '417', 'phonecode' => '996', 'iso_numeric' => '417', 'currency_numeric' => '417'),
            array('iso' => 'LA', 'name' => 'Lao People\'s Democratic Republic', 'name_ar' => 'لاوس', 'iso3' => 'LAO', 'numcode' => '418', 'phonecode' => '856', 'iso_numeric' => '418', 'currency_numeric' => '418'),
            array('iso' => 'LV', 'name' => 'Latvia', 'name_ar' => 'لاتفيا', 'iso3' => 'LVA', 'numcode' => '428', 'phonecode' => '371', 'iso_numeric' => '428', 'currency_numeric' => '978'),
            array('iso' => 'LB', 'name' => 'Lebanon', 'name_ar' => 'لبنان', 'iso3' => 'LBN', 'numcode' => '422', 'phonecode' => '961', 'iso_numeric' => '422', 'currency_numeric' => '422'),
            array('iso' => 'LS', 'name' => 'Lesotho', 'name_ar' => 'ليسوتو', 'iso3' => 'LSO', 'numcode' => '426', 'phonecode' => '266', 'iso_numeric' => '426', 'currency_numeric' => '426'),
            array('iso' => 'LR', 'name' => 'Liberia', 'name_ar' => 'ليبيريا', 'iso3' => 'LBR', 'numcode' => '430', 'phonecode' => '231', 'iso_numeric' => '430', 'currency_numeric' => '430'),
            array('iso' => 'LY', 'name' => 'Libyan Arab Jamahiriya', 'name_ar' => 'ليبيا', 'iso3' => 'LBY', 'numcode' => '434', 'phonecode' => '218', 'iso_numeric' => '434', 'currency_numeric' => '434'),
            array('iso' => 'LI', 'name' => 'Liechtenstein', 'name_ar' => 'ليختنشتاين', 'iso3' => 'LIE', 'numcode' => '438', 'phonecode' => '423', 'iso_numeric' => '438', 'currency_numeric' => '756'),
            array('iso' => 'LT', 'name' => 'Lithuania', 'name_ar' => 'ليتوانيا', 'iso3' => 'LTU', 'numcode' => '440', 'phonecode' => '370', 'iso_numeric' => '440', 'currency_numeric' => '978'),
            array('iso' => 'LU', 'name' => 'Luxembourg', 'name_ar' => 'لوكسمبورغ', 'iso3' => 'LUX', 'numcode' => '442', 'phonecode' => '352', 'iso_numeric' => '442', 'currency_numeric' => '978'),
            array('iso' => 'MO', 'name' => 'Macao', 'name_ar' => 'ماكاو', 'iso3' => 'MAC', 'numcode' => '446', 'phonecode' => '853', 'iso_numeric' => '446', 'currency_numeric' => '446'),
            array('iso' => 'MK', 'name' => 'Macedonia, the Former Yugoslav Republic of', 'name_ar' => 'مقدونيا الشمالية', 'iso3' => 'MKD', 'numcode' => '807', 'phonecode' => '389', 'iso_numeric' => '807', 'currency_numeric' => '807'),
            array('iso' => 'MG', 'name' => 'Madagascar', 'name_ar' => 'مدغشقر', 'iso3' => 'MDG', 'numcode' => '450', 'phonecode' => '261', 'iso_numeric' => '450', 'currency_numeric' => '969'),
            array('iso' => 'MW', 'name' => 'Malawi', 'name_ar' => 'مالاوي', 'iso3' => 'MWI', 'numcode' => '454', 'phonecode' => '265', 'iso_numeric' => '454', 'currency_numeric' => '454'),
            array('iso' => 'MY', 'name' => 'Malaysia', 'name_ar' => 'ماليزيا', 'iso3' => 'MYS', 'numcode' => '458', 'phonecode' => '60', 'iso_numeric' => '458', 'currency_numeric' => '458'),
            array('iso' => 'MV', 'name' => 'Maldives', 'name_ar' => 'المالديف', 'iso3' => 'MDV', 'numcode' => '462', 'phonecode' => '960', 'iso_numeric' => '462', 'currency_numeric' => '462'),
            array('iso' => 'ML', 'name' => 'Mali', 'name_ar' => 'مالي', 'iso3' => 'MLI', 'numcode' => '466', 'phonecode' => '223', 'iso_numeric' => '466', 'currency_numeric' => '952'),
            array('iso' => 'MT', 'name' => 'Malta', 'name_ar' => 'مالطا', 'iso3' => 'MLT', 'numcode' => '470', 'phonecode' => '356', 'iso_numeric' => '470', 'currency_numeric' => '978'),
            array('iso' => 'MH', 'name' => 'Marshall Islands', 'name_ar' => 'جزر مارشال', 'iso3' => 'MHL', 'numcode' => '584', 'phonecode' => '692', 'iso_numeric' => '584', 'currency_numeric' => '840'),
            array('iso' => 'MQ', 'name' => 'Martinique', 'name_ar' => 'مارتينيك', 'iso3' => 'MTQ', 'numcode' => '474', 'phonecode' => '596', 'iso_numeric' => '474', 'currency_numeric' => '978'),
            array('iso' => 'MR', 'name' => 'Mauritania', 'name_ar' => 'موريتانيا', 'iso3' => 'MRT', 'numcode' => '478', 'phonecode' => '222', 'iso_numeric' => '478', 'currency_numeric' => '929'),
            array('iso' => 'MU', 'name' => 'Mauritius', 'name_ar' => 'موريشيوس', 'iso3' => 'MUS', 'numcode' => '480', 'phonecode' => '230', 'iso_numeric' => '480', 'currency_numeric' => '480'),
            array('iso' => 'YT', 'name' => 'Mayotte', 'name_ar' => 'مايوت', 'iso3' => NULL, 'numcode' => NULL, 'phonecode' => '269', 'iso_numeric' => NULL, 'currency_numeric' => '978'),
            array('iso' => 'MX', 'name' => 'Mexico', 'name_ar' => 'المكسيك', 'iso3' => 'MEX', 'numcode' => '484', 'phonecode' => '52', 'iso_numeric' => '484', 'currency_numeric' => '484'),
            array('iso' => 'FM', 'name' => 'Micronesia, Federated States of', 'name_ar' => 'ولايات ميكرونيزيا المتحدة', 'iso3' => 'FSM', 'numcode' => '583', 'phonecode' => '691', 'iso_numeric' => '583', 'currency_numeric' => '840'),
            array('iso' => 'MD', 'name' => 'Moldova, Republic of', 'name_ar' => 'مولدوفا', 'iso3' => 'MDA', 'numcode' => '498', 'phonecode' => '373', 'iso_numeric' => '498', 'currency_numeric' => '498'),
            array('iso' => 'MC', 'name' => 'Monaco', 'name_ar' => 'موناكو', 'iso3' => 'MCO', 'numcode' => '492', 'phonecode' => '377', 'iso_numeric' => '492', 'currency_numeric' => '978'),
            array('iso' => 'MN', 'name' => 'Mongolia', 'name_ar' => 'منغوليا', 'iso3' => 'MNG', 'numcode' => '496', 'phonecode' => '976', 'iso_numeric' => '496', 'currency_numeric' => '496'),
            array('iso' => 'MS', 'name' => 'Montserrat', 'name_ar' => 'مونتسيرات', 'iso3' => 'MSR', 'numcode' => '500', 'phonecode' => '1664', 'iso_numeric' => '500', 'currency_numeric' => '951'),
            array('iso' => 'MA', 'name' => 'Morocco', 'name_ar' => 'المغرب', 'iso3' => 'MAR', 'numcode' => '504', 'phonecode' => '212', 'iso_numeric' => '504', 'currency_numeric' => '504'),
            array('iso' => 'MZ', 'name' => 'Mozambique', 'name_ar' => 'موزمبيق', 'iso3' => 'MOZ', 'numcode' => '508', 'phonecode' => '258', 'iso_numeric' => '508', 'currency_numeric' => '943'),
            array('iso' => 'MM', 'name' => 'Myanmar', 'name_ar' => 'ميانمار', 'iso3' => 'MMR', 'numcode' => '104', 'phonecode' => '95', 'iso_numeric' => '104', 'currency_numeric' => '104'),
            array('iso' => 'NA', 'name' => 'Namibia', 'name_ar' => 'ناميبيا', 'iso3' => 'NAM', 'numcode' => '516', 'phonecode' => '264', 'iso_numeric' => '516', 'currency_numeric' => '516'),
            array('iso' => 'NR', 'name' => 'Nauru', 'name_ar' => 'ناورو', 'iso3' => 'NRU', 'numcode' => '520', 'phonecode' => '674', 'iso_numeric' => '520', 'currency_numeric' => '036'),
            array('iso' => 'NP', 'name' => 'Nepal', 'name_ar' => 'نيبال', 'iso3' => 'NPL', 'numcode' => '524', 'phonecode' => '977', 'iso_numeric' => '524', 'currency_numeric' => '524'),
            array('iso' => 'NL', 'name' => 'Netherlands', 'name_ar' => 'هولندا', 'iso3' => 'NLD', 'numcode' => '528', 'phonecode' => '31', 'iso_numeric' => '528', 'currency_numeric' => '978'),
            array('iso' => 'AN', 'name' => 'Netherlands Antilles', 'name_ar' => 'جزر الأنتيل الهولندية', 'iso3' => 'ANT', 'numcode' => '530', 'phonecode' => '599', 'iso_numeric' => '530', 'currency_numeric' => '532'),
            array('iso' => 'NC', 'name' => 'New Caledonia', 'name_ar' => 'كاليدونيا الجديدة', 'iso3' => 'NCL', 'numcode' => '540', 'phonecode' => '687', 'iso_numeric' => '540', 'currency_numeric' => '953'),
            array('iso' => 'NZ', 'name' => 'New Zealand', 'name_ar' => 'نيوزيلندا', 'iso3' => 'NZL', 'numcode' => '554', 'phonecode' => '64', 'iso_numeric' => '554', 'currency_numeric' => '554'),

            array('iso' => 'NI', 'name' => 'Nicaragua', 'name_ar' => 'نيكاراغوا', 'iso3' => 'NIC', 'numcode' => '558', 'phonecode' => '505', 'iso_numeric' => '558', 'currency_numeric' => '558'),
            array('iso' => 'NE', 'name' => 'Niger', 'name_ar' => 'النيجر', 'iso3' => 'NER', 'numcode' => '562', 'phonecode' => '227', 'iso_numeric' => '562', 'currency_numeric' => '562'),
            array('iso' => 'NG', 'name' => 'Nigeria', 'name_ar' => 'نيجيريا', 'iso3' => 'NGA', 'numcode' => '566', 'phonecode' => '234', 'iso_numeric' => '566', 'currency_numeric' => '566'),
            array('iso' => 'NU', 'name' => 'Niue', 'name_ar' => 'نيوي', 'iso3' => 'NIU', 'numcode' => '570', 'phonecode' => '683', 'iso_numeric' => '570', 'currency_numeric' => '570'),
            array('iso' => 'NF', 'name' => 'Norfolk Island', 'name_ar' => 'جزيرة نورفولك', 'iso3' => 'NFK', 'numcode' => '574', 'phonecode' => '672', 'iso_numeric' => '574', 'currency_numeric' => '574'),
            array('iso' => 'MP', 'name' => 'Northern Mariana Islands', 'name_ar' => 'جزر ماريانا الشمالية', 'iso3' => 'MNP', 'numcode' => '580', 'phonecode' => '1670', 'iso_numeric' => '580', 'currency_numeric' => '580'),
            array('iso' => 'NO', 'name' => 'Norway', 'name_ar' => 'النرويج', 'iso3' => 'NOR', 'numcode' => '578', 'phonecode' => '47', 'iso_numeric' => '578', 'currency_numeric' => '578'),
            array('iso' => 'OM', 'name' => 'Oman', 'name_ar' => 'عُمان', 'iso3' => 'OMN', 'numcode' => '512', 'phonecode' => '968', 'iso_numeric' => '512', 'currency_numeric' => '512'),
            array('iso' => 'PK', 'name' => 'Pakistan', 'name_ar' => 'باكستان', 'iso3' => 'PAK', 'numcode' => '586', 'phonecode' => '92', 'iso_numeric' => '586', 'currency_numeric' => '586'),
            array('iso' => 'PW', 'name' => 'Palau', 'name_ar' => 'بالاو', 'iso3' => 'PLW', 'numcode' => '585', 'phonecode' => '680', 'iso_numeric' => '585', 'currency_numeric' => '585'),
            array('iso' => 'PS', 'name' => 'Palestine', 'name_ar' => 'فلسطين', 'iso3' => 'PSE', 'numcode' => '275', 'phonecode' => '970', 'iso_numeric' => '275', 'currency_numeric' => NULL),
            array('iso' => 'PA', 'name' => 'Panama', 'name_ar' => 'بنما', 'iso3' => 'PAN', 'numcode' => '591', 'phonecode' => '507', 'iso_numeric' => '591', 'currency_numeric' => '591'),
            array('iso' => 'PG', 'name' => 'Papua New Guinea', 'name_ar' => 'بابوا غينيا الجديدة', 'iso3' => 'PNG', 'numcode' => '598', 'phonecode' => '675', 'iso_numeric' => '598', 'currency_numeric' => '598'),
            array('iso' => 'PY', 'name' => 'Paraguay', 'name_ar' => 'باراغواي', 'iso3' => 'PRY', 'numcode' => '600', 'phonecode' => '595', 'iso_numeric' => '600', 'currency_numeric' => '600'),
            array('iso' => 'PE', 'name' => 'Peru', 'name_ar' => 'بيرو', 'iso3' => 'PER', 'numcode' => '604', 'phonecode' => '51', 'iso_numeric' => '604', 'currency_numeric' => '604'),
            array('iso' => 'PH', 'name' => 'Philippines', 'name_ar' => 'الفلبين', 'iso3' => 'PHL', 'numcode' => '608', 'phonecode' => '63', 'iso_numeric' => '608', 'currency_numeric' => '608'),
            array('iso' => 'PN', 'name' => 'Pitcairn', 'name_ar' => 'بيتكيرن', 'iso3' => 'PCN', 'numcode' => '612', 'phonecode' => '0', 'iso_numeric' => '612', 'currency_numeric' => '612'),
            array('iso' => 'PL', 'name' => 'Poland', 'name_ar' => 'بولندا', 'iso3' => 'POL', 'numcode' => '616', 'phonecode' => '48', 'iso_numeric' => '616', 'currency_numeric' => '616'),
            array('iso' => 'PT', 'name' => 'Portugal', 'name_ar' => 'البرتغال', 'iso3' => 'PRT', 'numcode' => '620', 'phonecode' => '351', 'iso_numeric' => '620', 'currency_numeric' => '620'),
            array('iso' => 'PR', 'name' => 'Puerto Rico', 'name_ar' => 'بورتو ريكو', 'iso3' => 'PRI', 'numcode' => '630', 'phonecode' => '1787', 'iso_numeric' => '630', 'currency_numeric' => '630'),
            array('iso' => 'QA', 'name' => 'Qatar', 'name_ar' => 'قطر', 'iso3' => 'QAT', 'numcode' => '634', 'phonecode' => '974', 'iso_numeric' => '634', 'currency_numeric' => '634'),
            array('iso' => 'RE', 'name' => 'Reunion', 'name_ar' => 'ريونيون', 'iso3' => 'REU', 'numcode' => '638', 'phonecode' => '262', 'iso_numeric' => '638', 'currency_numeric' => '638'),
            array('iso' => 'RO', 'name' => 'Romania', 'name_ar' => 'رومانيا', 'iso3' => 'ROM', 'numcode' => '642', 'phonecode' => '40', 'iso_numeric' => '642', 'currency_numeric' => '642'),
            array('iso' => 'RU', 'name' => 'Russian Federation', 'name_ar' => 'روسيا', 'iso3' => 'RUS', 'numcode' => '643', 'phonecode' => '70', 'iso_numeric' => '643', 'currency_numeric' => '643'),
            array('iso' => 'RW', 'name' => 'Rwanda', 'name_ar' => 'رواندا', 'iso3' => 'RWA', 'numcode' => '646', 'phonecode' => '250', 'iso_numeric' => '646', 'currency_numeric' => '646'),
            array('iso' => 'SH', 'name' => 'Saint Helena', 'name_ar' => 'سانت هيلينا', 'iso3' => 'SHN', 'numcode' => '654', 'phonecode' => '290', 'iso_numeric' => '654', 'currency_numeric' => '654'),
            array('iso' => 'KN', 'name' => 'Saint Kitts and Nevis', 'name_ar' => 'سانت كيتس ونيفيس', 'iso3' => 'KNA', 'numcode' => '659', 'phonecode' => '1869', 'iso_numeric' => '659', 'currency_numeric' => '659'),
            array('iso' => 'LC', 'name' => 'Saint Lucia', 'name_ar' => 'سانت لوسيا', 'iso3' => 'LCA', 'numcode' => '662', 'phonecode' => '1758', 'iso_numeric' => '662', 'currency_numeric' => '662'),
            array('iso' => 'PM', 'name' => 'Saint Pierre and Miquelon', 'name_ar' => 'سان بيير وميكلون', 'iso3' => 'SPM', 'numcode' => '666', 'phonecode' => '508', 'iso_numeric' => '666', 'currency_numeric' => '666'),
            array('iso' => 'VC', 'name' => 'Saint Vincent and the Grenadines', 'name_ar' => 'سانت فنسنت والغرينادين', 'iso3' => 'VCT', 'numcode' => '670', 'phonecode' => '1784', 'iso_numeric' => '670', 'currency_numeric' => '670'),
            array('iso' => 'WS', 'name' => 'Samoa', 'name_ar' => 'ساموا', 'iso3' => 'WSM', 'numcode' => '882', 'phonecode' => '684', 'iso_numeric' => '882', 'currency_numeric' => '882'),
            array('iso' => 'SM', 'name' => 'San Marino', 'name_ar' => 'سان مارينو', 'iso3' => 'SMR', 'numcode' => '674', 'phonecode' => '378', 'iso_numeric' => '674', 'currency_numeric' => '674'),
            array('iso' => 'ST', 'name' => 'Sao Tome and Principe', 'name_ar' => 'ساو تومي وبرينسيبي', 'iso3' => 'STP', 'numcode' => '678', 'phonecode' => '239', 'iso_numeric' => '678', 'currency_numeric' => '678'),
            array('iso' => 'SA', 'name' => 'Saudi Arabia', 'name_ar' => 'السعودية', 'iso3' => 'SAU', 'numcode' => '682', 'phonecode' => '966', 'iso_numeric' => '682', 'currency_numeric' => '682'),
            array('iso' => 'SN', 'name' => 'Senegal', 'name_ar' => 'السنغال', 'iso3' => 'SEN', 'numcode' => '686', 'phonecode' => '221', 'iso_numeric' => '686', 'currency_numeric' => '686'),
            array('iso' => 'CS', 'name' => 'Serbia and Montenegro', 'name_ar' => 'صربيا والجبل الأسود', 'iso3' => NULL, 'numcode' => NULL, 'phonecode' => '381', 'iso_numeric' => NULL, 'currency_numeric' => NULL),
            array('iso' => 'SC', 'name' => 'Seychelles', 'name_ar' => 'سيشل', 'iso3' => 'SYC', 'numcode' => '690', 'phonecode' => '248', 'iso_numeric' => '690', 'currency_numeric' => '690'),
            array('iso' => 'SL', 'name' => 'Sierra Leone', 'name_ar' => 'سيراليون', 'iso3' => 'SLE', 'numcode' => '694', 'phonecode' => '232', 'iso_numeric' => '694', 'currency_numeric' => '694'),
            array('iso' => 'SG', 'name' => 'Singapore', 'name_ar' => 'سنغافورة', 'iso3' => 'SGP', 'numcode' => '702', 'phonecode' => '65', 'iso_numeric' => '702', 'currency_numeric' => '702'),
            array('iso' => 'SK', 'name' => 'Slovakia', 'name_ar' => 'سلوفاكيا', 'iso3' => 'SVK', 'numcode' => '703', 'phonecode' => '421', 'iso_numeric' => '703', 'currency_numeric' => '703'),
            array('iso' => 'SI', 'name' => 'Slovenia', 'name_ar' => 'سلوفينيا', 'iso3' => 'SVN', 'numcode' => '705', 'phonecode' => '386', 'iso_numeric' => '705', 'currency_numeric' => '705'),
            array('iso' => 'SB', 'name' => 'Solomon Islands', 'name_ar' => 'جزر سليمان', 'iso3' => 'SLB', 'numcode' => '90', 'phonecode' => '677', 'iso_numeric' => '90', 'currency_numeric' => '90'),
            array('iso' => 'SO', 'name' => 'Somalia', 'name_ar' => 'الصومال', 'iso3' => 'SOM', 'numcode' => '706', 'phonecode' => '252', 'iso_numeric' => '706', 'currency_numeric' => '706'),
            array('iso' => 'ZA', 'name' => 'South Africa', 'name_ar' => 'جنوب أفريقيا', 'iso3' => 'ZAF', 'numcode' => '710', 'phonecode' => '27', 'iso_numeric' => '710', 'currency_numeric' => '710'),
            array('iso' => 'GS', 'name' => 'South Georgia and the South Sandwich Islands', 'name_ar' => 'جورجيا الجنوبية وجزر ساندويتش الجنوبية', 'iso3' => NULL, 'numcode' => NULL, 'phonecode' => '0', 'iso_numeric' => NULL, 'currency_numeric' => NULL),
            array('iso' => 'ES', 'name' => 'Spain', 'name_ar' => 'إسبانيا', 'iso3' => 'ESP', 'numcode' => '724', 'phonecode' => '34', 'iso_numeric' => '724', 'currency_numeric' => '724'),
            array('iso' => 'LK', 'name' => 'Sri Lanka', 'name_ar' => 'سريلانكا', 'iso3' => 'LKA', 'numcode' => '144', 'phonecode' => '94', 'iso_numeric' => '144', 'currency_numeric' => '144'),
            array('iso' => 'SD', 'name' => 'Sudan', 'name_ar' => 'السودان', 'iso3' => 'SDN', 'numcode' => '736', 'phonecode' => '249', 'iso_numeric' => '736', 'currency_numeric' => '736'),
            array('iso' => 'SR', 'name' => 'Suriname', 'name_ar' => 'سورينام', 'iso3' => 'SUR', 'numcode' => '740', 'phonecode' => '597', 'iso_numeric' => '740', 'currency_numeric' => '740'),
            array('iso' => 'SJ', 'name' => 'Svalbard and Jan Mayen', 'name_ar' => 'سفالبارد ويان ماين', 'iso3' => 'SJM', 'numcode' => '744', 'phonecode' => '47', 'iso_numeric' => '744', 'currency_numeric' => '744'),
            array('iso' => 'SZ', 'name' => 'Swaziland', 'name_ar' => 'إسواتيني', 'iso3' => 'SWZ', 'numcode' => '748', 'phonecode' => '268', 'iso_numeric' => '748', 'currency_numeric' => '748'),

            array('iso' => 'SE', 'name' => 'Sweden', 'name_ar' => 'السويد', 'iso3' => 'SWE', 'numcode' => '752', 'phonecode' => '46', 'iso_numeric' => '752', 'currency_numeric' => '752'),
            array('iso' => 'CH', 'name' => 'Switzerland', 'name_ar' => 'سويسرا', 'iso3' => 'CHE', 'numcode' => '756', 'phonecode' => '41', 'iso_numeric' => '756', 'currency_numeric' => '756'),
            array('iso' => 'SY', 'name' => 'Syrian Arab Republic', 'name_ar' => 'سوريا', 'iso3' => 'SYR', 'numcode' => '760', 'phonecode' => '963', 'iso_numeric' => '760', 'currency_numeric' => '760'),
            array('iso' => 'TW', 'name' => 'Taiwan, Province of China', 'name_ar' => 'تايوان', 'iso3' => 'TWN', 'numcode' => '158', 'phonecode' => '886', 'iso_numeric' => '158', 'currency_numeric' => '158'),
            array('iso' => 'TJ', 'name' => 'Tajikistan', 'name_ar' => 'طاجيكستان', 'iso3' => 'TJK', 'numcode' => '762', 'phonecode' => '992', 'iso_numeric' => '762', 'currency_numeric' => '762'),
            array('iso' => 'TZ', 'name' => 'Tanzania, United Republic of', 'name_ar' => 'تنزانيا', 'iso3' => 'TZA', 'numcode' => '834', 'phonecode' => '255', 'iso_numeric' => '834', 'currency_numeric' => '834'),
            array('iso' => 'TH', 'name' => 'Thailand', 'name_ar' => 'تايلاند', 'iso3' => 'THA', 'numcode' => '764', 'phonecode' => '66', 'iso_numeric' => '764', 'currency_numeric' => '764'),
            array('iso' => 'TL', 'name' => 'Timor-Leste', 'name_ar' => 'تيمور الشرقية', 'iso3' => NULL, 'numcode' => NULL, 'phonecode' => '670', 'iso_numeric' => NULL, 'currency_numeric' => NULL),
            array('iso' => 'TG', 'name' => 'Togo', 'name_ar' => 'توغو', 'iso3' => 'TGO', 'numcode' => '768', 'phonecode' => '228', 'iso_numeric' => '768', 'currency_numeric' => '768'),
            array('iso' => 'TK', 'name' => 'Tokelau', 'name_ar' => 'توكيلاو', 'iso3' => 'TKL', 'numcode' => '772', 'phonecode' => '690', 'iso_numeric' => '772', 'currency_numeric' => '772'),
            array('iso' => 'TO', 'name' => 'Tonga', 'name_ar' => 'تونغا', 'iso3' => 'TON', 'numcode' => '776', 'phonecode' => '676', 'iso_numeric' => '776', 'currency_numeric' => '776'),
            array('iso' => 'TT', 'name' => 'Trinidad and Tobago', 'name_ar' => 'ترينيداد وتوباغو', 'iso3' => 'TTO', 'numcode' => '780', 'phonecode' => '1868', 'iso_numeric' => '780', 'currency_numeric' => '780'),
            array('iso' => 'TN', 'name' => 'Tunisia', 'name_ar' => 'تونس', 'iso3' => 'TUN', 'numcode' => '788', 'phonecode' => '216', 'iso_numeric' => '788', 'currency_numeric' => '788'),
            array('iso' => 'TR', 'name' => 'Turkey', 'name_ar' => 'تركيا', 'iso3' => 'TUR', 'numcode' => '792', 'phonecode' => '90', 'iso_numeric' => '792', 'currency_numeric' => '792'),
            array('iso' => 'TM', 'name' => 'Turkmenistan', 'name_ar' => 'تركمانستان', 'iso3' => 'TKM', 'numcode' => '795', 'phonecode' => '7370', 'iso_numeric' => '795', 'currency_numeric' => '795'),
            array('iso' => 'TC', 'name' => 'Turks and Caicos Islands', 'name_ar' => 'جزر توركس وكايكوس', 'iso3' => 'TCA', 'numcode' => '796', 'phonecode' => '1649', 'iso_numeric' => '796', 'currency_numeric' => '796'),
            array('iso' => 'TV', 'name' => 'Tuvalu', 'name_ar' => 'توفالو', 'iso3' => 'TUV', 'numcode' => '798', 'phonecode' => '688', 'iso_numeric' => '798', 'currency_numeric' => '798'),
            array('iso' => 'UG', 'name' => 'Uganda', 'name_ar' => 'أوغندا', 'iso3' => 'UGA', 'numcode' => '800', 'phonecode' => '256', 'iso_numeric' => '800', 'currency_numeric' => '800'),
            array('iso' => 'UA', 'name' => 'Ukraine', 'name_ar' => 'أوكرانيا', 'iso3' => 'UKR', 'numcode' => '804', 'phonecode' => '380', 'iso_numeric' => '804', 'currency_numeric' => '804'),
            array('iso' => 'AE', 'name' => 'United Arab Emirates', 'name_ar' => 'الإمارات العربية المتحدة', 'iso3' => 'ARE', 'numcode' => '784', 'phonecode' => '971', 'iso_numeric' => '784', 'currency_numeric' => '784'),
            array('iso' => 'GB', 'name' => 'United Kingdom', 'name_ar' => 'المملكة المتحدة', 'iso3' => 'GBR', 'numcode' => '826', 'phonecode' => '44', 'iso_numeric' => '826', 'currency_numeric' => '826'),
            array('iso' => 'US', 'name' => 'United States', 'name_ar' => 'الولايات المتحدة', 'iso3' => 'USA', 'numcode' => '840', 'phonecode' => '1', 'iso_numeric' => '840', 'currency_numeric' => '840'),
            array('iso' => 'UM', 'name' => 'United States Minor Outlying Islands', 'name_ar' => 'الجزر الصغيرة النائية التابعة للولايات المتحدة', 'iso3' => 'UMI', 'numcode' => '581', 'phonecode' => '1', 'iso_numeric' => '581', 'currency_numeric' => '840'),
            array('iso' => 'UY', 'name' => 'Uruguay', 'name_ar' => 'أوروغواي', 'iso3' => 'URY', 'numcode' => '858', 'phonecode' => '598', 'iso_numeric' => '858', 'currency_numeric' => '858'),
            array('iso' => 'UZ', 'name' => 'Uzbekistan', 'name_ar' => 'أوزبكستان', 'iso3' => 'UZB', 'numcode' => '860', 'phonecode' => '998', 'iso_numeric' => '860', 'currency_numeric' => '860'),
            array('iso' => 'VU', 'name' => 'Vanuatu', 'name_ar' => 'فانواتو', 'iso3' => 'VUT', 'numcode' => '548', 'phonecode' => '678', 'iso_numeric' => '548', 'currency_numeric' => '548'),
            array('iso' => 'VE', 'name' => 'Venezuela', 'name_ar' => 'فنزويلا', 'iso3' => 'VEN', 'numcode' => '862', 'phonecode' => '58', 'iso_numeric' => '862', 'currency_numeric' => '862'),
            array('iso' => 'VN', 'name' => 'Viet Nam', 'name_ar' => 'فيتنام', 'iso3' => 'VNM', 'numcode' => '704', 'phonecode' => '84', 'iso_numeric' => '704', 'currency_numeric' => '704'),
            array('iso' => 'VG', 'name' => 'Virgin Islands, British', 'name_ar' => 'جزر فيرجن البريطانية', 'iso3' => 'VGB', 'numcode' => '92', 'phonecode' => '1284', 'iso_numeric' => '92', 'currency_numeric' => '92'),
            array('iso' => 'VI', 'name' => 'Virgin Islands, U.S.', 'name_ar' => 'جزر فيرجن الأمريكية', 'iso3' => 'VIR', 'numcode' => '850', 'phonecode' => '1340', 'iso_numeric' => '850', 'currency_numeric' => '850'),
            array('iso' => 'WF', 'name' => 'Wallis and Futuna', 'name_ar' => 'واليس وفوتونا', 'iso3' => 'WLF', 'numcode' => '876', 'phonecode' => '681', 'iso_numeric' => '876', 'currency_numeric' => '876'),
            array('iso' => 'EH', 'name' => 'Western Sahara', 'name_ar' => 'الصحراء الغربية', 'iso3' => 'ESH', 'numcode' => '732', 'phonecode' => '212', 'iso_numeric' => '732', 'currency_numeric' => '732'),
            array('iso' => 'YE', 'name' => 'Yemen', 'name_ar' => 'اليمن', 'iso3' => 'YEM', 'numcode' => '887', 'phonecode' => '967', 'iso_numeric' => '887', 'currency_numeric' => '887'),
            array('iso' => 'ZM', 'name' => 'Zambia', 'name_ar' => 'زامبيا', 'iso3' => 'ZMB', 'numcode' => '894', 'phonecode' => '260', 'iso_numeric' => '894', 'currency_numeric' => '894'),
            array('iso' => 'ZW', 'name' => 'Zimbabwe', 'name_ar' => 'زيمبابوي', 'iso3' => 'ZWE', 'numcode' => '716', 'phonecode' => '263', 'iso_numeric' => '716', 'currency_numeric' => '716'),
            array('iso' => 'RS', 'name' => 'Serbia', 'name_ar' => 'صربيا', 'iso3' => 'SRB', 'numcode' => '688', 'phonecode' => '381', 'iso_numeric' => '688', 'currency_numeric' => '688'),
            array('iso' => 'AP', 'name' => 'Asia / Pacific Region', 'name_ar' => 'منطقة آسيا والمحيط الهادئ', 'iso3' => '0', 'numcode' => '0', 'phonecode' => '0', 'iso_numeric' => '0', 'currency_numeric' => '0'),
            array('iso' => 'ME', 'name' => 'Montenegro', 'name_ar' => 'الجبل الأسود', 'iso3' => 'MNE', 'numcode' => '499', 'phonecode' => '382', 'iso_numeric' => '499', 'currency_numeric' => '499'),
            array('iso' => 'AX', 'name' => 'Aland Islands', 'name_ar' => 'جزر آلاند', 'iso3' => 'ALA', 'numcode' => '248', 'phonecode' => '358', 'iso_numeric' => '248', 'currency_numeric' => '248'),
            array('iso' => 'BQ', 'name' => 'Bonaire, Sint Eustatius and Saba', 'name_ar' => 'بونير وسينت أوستاتيوس وسابا', 'iso3' => 'BES', 'numcode' => '535', 'phonecode' => '599', 'iso_numeric' => '535', 'currency_numeric' => '535'),
            array('iso' => 'CW', 'name' => 'Curacao', 'name_ar' => 'كوراساو', 'iso3' => 'CUW', 'numcode' => '531', 'phonecode' => '599', 'iso_numeric' => '531', 'currency_numeric' => '531'),
            array('iso' => 'GG', 'name' => 'Guernsey', 'name_ar' => 'غيرنزي', 'iso3' => 'GGY', 'numcode' => '831', 'phonecode' => '44', 'iso_numeric' => '831', 'currency_numeric' => '831'),
            array('iso' => 'IM', 'name' => 'Isle of Man', 'name_ar' => 'جزيرة مان', 'iso3' => 'IMN', 'numcode' => '833', 'phonecode' => '44', 'iso_numeric' => '833', 'currency_numeric' => '833'),
            array('iso' => 'JE', 'name' => 'Jersey', 'name_ar' => 'جيرسي', 'iso3' => 'JEY', 'numcode' => '832', 'phonecode' => '44', 'iso_numeric' => '832', 'currency_numeric' => '832'),
            array('iso' => 'XK', 'name' => 'Kosovo', 'name_ar' => 'كوسوفو', 'iso3' => '---', 'numcode' => '0', 'phonecode' => '381', 'iso_numeric' => '0', 'currency_numeric' => '0'),
            array('iso' => 'BL', 'name' => 'Saint Barthelemy', 'name_ar' => 'سان بارتيلمي', 'iso3' => 'BLM', 'numcode' => '652', 'phonecode' => '590', 'iso_numeric' => '652', 'currency_numeric' => '652'),
            array('iso' => 'MF', 'name' => 'Saint Martin', 'name_ar' => 'سانت مارتن', 'iso3' => 'MAF', 'numcode' => '663', 'phonecode' => '590', 'iso_numeric' => '663', 'currency_numeric' => '663'),
            array('iso' => 'SX', 'name' => 'Sint Maarten', 'name_ar' => 'سينت مارتن', 'iso3' => 'SXM', 'numcode' => '534', 'phonecode' => '1', 'iso_numeric' => '534', 'currency_numeric' => '534'),
            array('iso' => 'SS', 'name' => 'South Sudan', 'name_ar' => 'جنوب السودان', 'iso3' => 'SSD', 'numcode' => '728', 'phonecode' => '211', 'iso_numeric' => '728', 'currency_numeric' => '728'),

        );

        foreach ($countries as $data) {
            $iso = trim($data['iso']);
            $iso3 = trim($data['iso3'] ?? '');
            $prettyName = trim($data['name']);
            $prettyArName = trim($data['name_ar']);
            $phone = '+' . trim($data['phonecode']);
            $lowerIso = Str::lower($iso);
            $imagePath = "images/flags/{$lowerIso}.svg";

            $country = Country::where('iso', $iso)
                ->orWhere('iso3', $iso3)
                ->orWhere('e_name', 'LIKE', "%{$prettyName}%")
//                ->orWhere('phone_code', $phone)
                ->first();

            if ($country) {
                $country->update([
                    'e_name' => $prettyName,
                    'name' => $prettyArName,
                    'iso' => $iso ?: $country->iso,
                    'iso3' => $iso3 ?: $country->iso3,
                    'phone_code' => $phone ?: $country->phone_code,
                    'status' => $country->status == 1 ? 1 : 0,
                    'flag' => $imagePath,
                    'iso_numeric' => $data['iso_numeric'],
                    'currency_numeric' => $data['currency_numeric'],
                ]);

                $this->command->info("🔄 Updated existing country: {$prettyName} (ID {$country->id})");
            } else {
                $new = Country::create([
                    'e_name'     => $prettyName,
                    'name'       => $prettyArName,
                    'iso'        => $iso,
                    'iso3'       => $iso3,
                    'phone_code' => $phone,
                    'status'     => 0,
                    'flag'       => $imagePath,
                    'iso_numeric' => $data['iso_numeric'],
                    'currency_numeric' => $data['currency_numeric'],
                ]);

                $this->command->info("➕ Created new country: {$prettyName} (ID {$new->id})");
            }
        }

        $this->command->info('✅ Countries updated or inserted successfully!');
    }
}
