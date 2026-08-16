<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for country definitions (ISO 3166-1).
 *
 * - Upserts every country in the world into `countries` keyed by ISO2 code:
 *   English name from the embedded dataset, Arabic name from PHP intl,
 *   dial code, ISO3, and a normalized flag path `images/flags/{iso2}.svg`
 *   (the bundled flag library in public/images/flags — no manual uploads).
 * - Existing rows keep their id (FKs to users/agencies/regions are safe);
 *   only missing/blank fields are filled and the flag path is normalized.
 * - Re-links orphan users.country_id (pointing at deleted country rows) by
 *   longest dial-code prefix match on the user's phone, else NULL.
 *
 * Idempotent: safe to run repeatedly.
 */
class WorldCountriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncFlagsToStorage();

        $intl = extension_loaded('intl');
        $created = 0;
        $updated = 0;

        foreach (self::WORLD as $def) {
            $iso = $def['iso'];
            $flagFile = public_path('images/flags/' . strtolower($iso) . '.svg');
            $flag = file_exists($flagFile) ? 'images/flags/' . strtolower($iso) . '.svg' : null;
            $arName = $intl ? (\Locale::getDisplayRegion('-' . $iso, 'ar') ?: $def['en']) : $def['en'];

            $row = Country::where('iso', $iso)->orderBy('id')->first();
            if ($row) {
                $dirty = false;
                if (trim((string) $row->e_name) === '') { $row->e_name = $def['en']; $dirty = true; }
                if (trim((string) $row->name) === '' || $row->name === $row->e_name) { $row->name = $arName; $dirty = true; }
                if ($flag && $row->flag !== $flag) { $row->flag = $flag; $dirty = true; }
                if (trim((string) $row->phone_code) === '') { $row->phone_code = $def['phone']; $dirty = true; }
                if (trim((string) $row->iso3) === '') { $row->iso3 = $def['iso3']; $dirty = true; }
                if ((int) $row->status !== 1) { $row->status = 1; $dirty = true; }
                if ($dirty) { $row->save(); $updated++; }
            } else {
                Country::create([
                    'name' => $arName,
                    'e_name' => $def['en'],
                    'phone_code' => $def['phone'],
                    'language' => '',
                    'iso' => $iso,
                    'iso3' => $def['iso3'],
                    'continent_name' => '',
                    'e_continent_name' => '',
                    'flag' => $flag ?: '',
                    'status' => 1,
                ]);
                $created++;
            }
        }

        $this->command?->info("Countries upsert done: {$created} created, {$updated} updated.");

        $this->relinkOrphanUsers();

        // Country lists are cached (5 min) — drop them so the app sees the
        // normalized data immediately.
        cache()->forget('filter_countries_list');
        cache()->forget('countries_hot_supporters_');
        cache()->forget('countries_hot_supporters_0');
        cache()->forget('countries_with_supporters');
    }

    /**
     * The app/admin render `flag` through the storage CDN
     * (`filesystems.<default>.url` prefix), so mirror the bundled flag SVGs
     * into the bucket. Skips silently on local/dev where the disk is local.
     */
    private function syncFlagsToStorage(): void
    {
        $disk = config('filesystems.default');
        if ($disk === 'local' || $disk === 'public') {
            return;
        }

        try {
            $storage = \Storage::disk($disk);
            foreach (glob(public_path('images/flags/*.svg')) as $localPath) {
                $gcsPath = 'images/flags/' . basename($localPath);
                if (!$storage->exists($gcsPath)) {
                    $storage->put($gcsPath, file_get_contents($localPath), 'public');
                }
            }
            $this->command?->info('Flag SVGs mirrored to storage disk.');
        } catch (\Throwable $e) {
            $this->command?->warn('Flag mirror to storage failed: ' . $e->getMessage());
        }
    }

    private function relinkOrphanUsers(): void
    {
        $validIds = Country::pluck('id')->all();

        // Dial code => country id, longest dial code first so +1... beats +1.
        $dialMap = Country::whereNotNull('phone_code')
            ->where('phone_code', '!=', '')
            ->get(['id', 'phone_code'])
            ->map(fn ($c) => ['id' => $c->id, 'dial' => ltrim($c->phone_code, '+')])
            ->filter(fn ($c) => $c['dial'] !== '' && ctype_digit($c['dial']))
            ->sortByDesc(fn ($c) => strlen($c['dial']))
            ->values();

        $orphans = DB::table('users')
            ->whereNotNull('country_id')
            ->whereNotIn('country_id', $validIds)
            ->get(['id', 'phone', 'country_id']);

        $fixed = 0;
        $cleared = 0;
        foreach ($orphans as $user) {
            $digits = ltrim(preg_replace('/\D+/', '', (string) $user->phone), '0');
            $newId = null;
            if ($digits !== '') {
                foreach ($dialMap as $c) {
                    if (str_starts_with($digits, $c['dial'])) { $newId = $c['id']; break; }
                }
            }
            DB::table('users')->where('id', $user->id)->update(['country_id' => $newId]);
            $newId ? $fixed++ : $cleared++;
        }

        $this->command?->info("Orphan users relinked: {$fixed} by phone prefix, {$cleared} cleared to NULL.");
    }

    /** ISO 3166-1 dataset: alpha-2, alpha-3, dial code, English name. */
    private const WORLD = [
        ['iso' => 'AC', 'iso3' => 'AC', 'phone' => '+247', 'en' => "Ascension Island"],
        ['iso' => 'AD', 'iso3' => 'AND', 'phone' => '+376', 'en' => "Andorra"],
        ['iso' => 'AE', 'iso3' => 'ARE', 'phone' => '+971', 'en' => "United Arab Emirates"],
        ['iso' => 'AF', 'iso3' => 'AFG', 'phone' => '+93', 'en' => "Afghanistan"],
        ['iso' => 'AG', 'iso3' => 'ATG', 'phone' => '+1', 'en' => "Antigua and Barbuda"],
        ['iso' => 'AI', 'iso3' => 'AIA', 'phone' => '+1', 'en' => "Anguilla"],
        ['iso' => 'AL', 'iso3' => 'ALB', 'phone' => '+355', 'en' => "Albania"],
        ['iso' => 'AM', 'iso3' => 'ARM', 'phone' => '+374', 'en' => "Armenia"],
        ['iso' => 'AO', 'iso3' => 'AGO', 'phone' => '+244', 'en' => "Angola"],
        ['iso' => 'AR', 'iso3' => 'ARG', 'phone' => '+54', 'en' => "Argentina"],
        ['iso' => 'AS', 'iso3' => 'ASM', 'phone' => '+1', 'en' => "American Samoa"],
        ['iso' => 'AT', 'iso3' => 'AUT', 'phone' => '+43', 'en' => "Austria"],
        ['iso' => 'AU', 'iso3' => 'AUS', 'phone' => '+61', 'en' => "Australia"],
        ['iso' => 'AW', 'iso3' => 'ABW', 'phone' => '+297', 'en' => "Aruba"],
        ['iso' => 'AX', 'iso3' => 'ALA', 'phone' => '+358', 'en' => "Åland Islands"],
        ['iso' => 'AZ', 'iso3' => 'AZE', 'phone' => '+994', 'en' => "Azerbaijan"],
        ['iso' => 'BA', 'iso3' => 'BIH', 'phone' => '+387', 'en' => "Bosnia and Herzegovina"],
        ['iso' => 'BB', 'iso3' => 'BRB', 'phone' => '+1', 'en' => "Barbados"],
        ['iso' => 'BD', 'iso3' => 'BGD', 'phone' => '+880', 'en' => "Bangladesh"],
        ['iso' => 'BE', 'iso3' => 'BEL', 'phone' => '+32', 'en' => "Belgium"],
        ['iso' => 'BF', 'iso3' => 'BFA', 'phone' => '+226', 'en' => "Burkina Faso"],
        ['iso' => 'BG', 'iso3' => 'BGR', 'phone' => '+359', 'en' => "Bulgaria"],
        ['iso' => 'BH', 'iso3' => 'BHR', 'phone' => '+973', 'en' => "Bahrain"],
        ['iso' => 'BI', 'iso3' => 'BDI', 'phone' => '+257', 'en' => "Burundi"],
        ['iso' => 'BJ', 'iso3' => 'BEN', 'phone' => '+229', 'en' => "Benin"],
        ['iso' => 'BL', 'iso3' => 'BLM', 'phone' => '+590', 'en' => "Saint Barthélemy"],
        ['iso' => 'BM', 'iso3' => 'BMU', 'phone' => '+1', 'en' => "Bermuda"],
        ['iso' => 'BN', 'iso3' => 'BRN', 'phone' => '+673', 'en' => "Brunei"],
        ['iso' => 'BO', 'iso3' => 'BOL', 'phone' => '+591', 'en' => "Bolivia"],
        ['iso' => 'BQ', 'iso3' => 'BES', 'phone' => '+599', 'en' => "Caribbean Netherlands"],
        ['iso' => 'BR', 'iso3' => 'BRA', 'phone' => '+55', 'en' => "Brazil"],
        ['iso' => 'BS', 'iso3' => 'BHS', 'phone' => '+1', 'en' => "Bahamas"],
        ['iso' => 'BT', 'iso3' => 'BTN', 'phone' => '+975', 'en' => "Bhutan"],
        ['iso' => 'BW', 'iso3' => 'BWA', 'phone' => '+267', 'en' => "Botswana"],
        ['iso' => 'BY', 'iso3' => 'BLR', 'phone' => '+375', 'en' => "Belarus"],
        ['iso' => 'BZ', 'iso3' => 'BLZ', 'phone' => '+501', 'en' => "Belize"],
        ['iso' => 'CA', 'iso3' => 'CAN', 'phone' => '+1', 'en' => "Canada"],
        ['iso' => 'CC', 'iso3' => 'CCK', 'phone' => '+61', 'en' => "Cocos [Keeling] Islands"],
        ['iso' => 'CD', 'iso3' => 'COD', 'phone' => '+243', 'en' => "Democratic Republic Congo"],
        ['iso' => 'CF', 'iso3' => 'CAF', 'phone' => '+236', 'en' => "Central African Republic"],
        ['iso' => 'CG', 'iso3' => 'COG', 'phone' => '+242', 'en' => "Republic of Congo"],
        ['iso' => 'CH', 'iso3' => 'CHE', 'phone' => '+41', 'en' => "Switzerland"],
        ['iso' => 'CI', 'iso3' => 'CIV', 'phone' => '+225', 'en' => "Côte d'Ivoire"],
        ['iso' => 'CK', 'iso3' => 'COK', 'phone' => '+682', 'en' => "Cook Islands"],
        ['iso' => 'CL', 'iso3' => 'CHL', 'phone' => '+56', 'en' => "Chile"],
        ['iso' => 'CM', 'iso3' => 'CMR', 'phone' => '+237', 'en' => "Cameroon"],
        ['iso' => 'CN', 'iso3' => 'CHN', 'phone' => '+86', 'en' => "China"],
        ['iso' => 'CO', 'iso3' => 'COL', 'phone' => '+57', 'en' => "Colombia"],
        ['iso' => 'CR', 'iso3' => 'CRI', 'phone' => '+506', 'en' => "Costa Rica"],
        ['iso' => 'CU', 'iso3' => 'CUB', 'phone' => '+53', 'en' => "Cuba"],
        ['iso' => 'CV', 'iso3' => 'CPV', 'phone' => '+238', 'en' => "Cape Verde"],
        ['iso' => 'CW', 'iso3' => 'CUW', 'phone' => '+599', 'en' => "Curaçao"],
        ['iso' => 'CX', 'iso3' => 'CXR', 'phone' => '+61', 'en' => "Christmas Island"],
        ['iso' => 'CY', 'iso3' => 'CYP', 'phone' => '+357', 'en' => "Cyprus"],
        ['iso' => 'CZ', 'iso3' => 'CZE', 'phone' => '+420', 'en' => "Czech Republic"],
        ['iso' => 'DE', 'iso3' => 'DEU', 'phone' => '+49', 'en' => "Germany"],
        ['iso' => 'DJ', 'iso3' => 'DJI', 'phone' => '+253', 'en' => "Djibouti"],
        ['iso' => 'DK', 'iso3' => 'DNK', 'phone' => '+45', 'en' => "Denmark"],
        ['iso' => 'DM', 'iso3' => 'DMA', 'phone' => '+1', 'en' => "Dominica"],
        ['iso' => 'DO', 'iso3' => 'DOM', 'phone' => '+1', 'en' => "Dominican Republic"],
        ['iso' => 'DZ', 'iso3' => 'DZA', 'phone' => '+213', 'en' => "Algeria"],
        ['iso' => 'EC', 'iso3' => 'ECU', 'phone' => '+593', 'en' => "Ecuador"],
        ['iso' => 'EE', 'iso3' => 'EST', 'phone' => '+372', 'en' => "Estonia"],
        ['iso' => 'EG', 'iso3' => 'EGY', 'phone' => '+20', 'en' => "Egypt"],
        ['iso' => 'EH', 'iso3' => 'ESH', 'phone' => '+212', 'en' => "Western Sahara"],
        ['iso' => 'ER', 'iso3' => 'ERI', 'phone' => '+291', 'en' => "Eritrea"],
        ['iso' => 'ES', 'iso3' => 'ESP', 'phone' => '+34', 'en' => "Spain"],
        ['iso' => 'ET', 'iso3' => 'ETH', 'phone' => '+251', 'en' => "Ethiopia"],
        ['iso' => 'FI', 'iso3' => 'FIN', 'phone' => '+358', 'en' => "Finland"],
        ['iso' => 'FJ', 'iso3' => 'FJI', 'phone' => '+679', 'en' => "Fiji"],
        ['iso' => 'FK', 'iso3' => 'FLK', 'phone' => '+500', 'en' => "Falkland Islands [Islas Malvinas]"],
        ['iso' => 'FM', 'iso3' => 'FSM', 'phone' => '+691', 'en' => "Micronesia"],
        ['iso' => 'FO', 'iso3' => 'FRO', 'phone' => '+298', 'en' => "Faroe Islands"],
        ['iso' => 'FR', 'iso3' => 'FRA', 'phone' => '+33', 'en' => "France"],
        ['iso' => 'GA', 'iso3' => 'GAB', 'phone' => '+241', 'en' => "Gabon"],
        ['iso' => 'GB', 'iso3' => 'GBR', 'phone' => '+44', 'en' => "United Kingdom"],
        ['iso' => 'GD', 'iso3' => 'GRD', 'phone' => '+1', 'en' => "Grenada"],
        ['iso' => 'GE', 'iso3' => 'GEO', 'phone' => '+995', 'en' => "Georgia"],
        ['iso' => 'GF', 'iso3' => 'GUF', 'phone' => '+594', 'en' => "French Guiana"],
        ['iso' => 'GG', 'iso3' => 'GGY', 'phone' => '+44', 'en' => "Guernsey"],
        ['iso' => 'GH', 'iso3' => 'GHA', 'phone' => '+233', 'en' => "Ghana"],
        ['iso' => 'GI', 'iso3' => 'GIB', 'phone' => '+350', 'en' => "Gibraltar"],
        ['iso' => 'GL', 'iso3' => 'GRL', 'phone' => '+299', 'en' => "Greenland"],
        ['iso' => 'GM', 'iso3' => 'GMB', 'phone' => '+220', 'en' => "Gambia"],
        ['iso' => 'GN', 'iso3' => 'GIN', 'phone' => '+224', 'en' => "Guinea Conakry"],
        ['iso' => 'GP', 'iso3' => 'GLP', 'phone' => '+590', 'en' => "Guadeloupe"],
        ['iso' => 'GQ', 'iso3' => 'GNQ', 'phone' => '+240', 'en' => "Equatorial Guinea"],
        ['iso' => 'GR', 'iso3' => 'GRC', 'phone' => '+30', 'en' => "Greece"],
        ['iso' => 'GS', 'iso3' => 'SGS', 'phone' => '+500', 'en' => "South Georgia and the South Sandwich Islands"],
        ['iso' => 'GT', 'iso3' => 'GTM', 'phone' => '+502', 'en' => "Guatemala"],
        ['iso' => 'GU', 'iso3' => 'GUM', 'phone' => '+1', 'en' => "Guam"],
        ['iso' => 'GW', 'iso3' => 'GNB', 'phone' => '+245', 'en' => "Guinea-Bissau"],
        ['iso' => 'GY', 'iso3' => 'GUY', 'phone' => '+592', 'en' => "Guyana"],
        ['iso' => 'HK', 'iso3' => 'HKG', 'phone' => '+852', 'en' => "Hong Kong"],
        ['iso' => 'HM', 'iso3' => 'HMD', 'phone' => '+672', 'en' => "Heard Island and McDonald Islands"],
        ['iso' => 'HN', 'iso3' => 'HND', 'phone' => '+504', 'en' => "Honduras"],
        ['iso' => 'HR', 'iso3' => 'HRV', 'phone' => '+385', 'en' => "Croatia"],
        ['iso' => 'HT', 'iso3' => 'HTI', 'phone' => '+509', 'en' => "Haiti"],
        ['iso' => 'HU', 'iso3' => 'HUN', 'phone' => '+36', 'en' => "Hungary"],
        ['iso' => 'ID', 'iso3' => 'IDN', 'phone' => '+62', 'en' => "Indonesia"],
        ['iso' => 'IE', 'iso3' => 'IRL', 'phone' => '+353', 'en' => "Ireland"],
        ['iso' => 'IL', 'iso3' => 'ISR', 'phone' => '+972', 'en' => "Israel"],
        ['iso' => 'IM', 'iso3' => 'IMN', 'phone' => '+44', 'en' => "Isle of Man"],
        ['iso' => 'IN', 'iso3' => 'IND', 'phone' => '+91', 'en' => "India"],
        ['iso' => 'IO', 'iso3' => 'IOT', 'phone' => '+246', 'en' => "British Indian Ocean Territory"],
        ['iso' => 'IQ', 'iso3' => 'IRQ', 'phone' => '+964', 'en' => "Iraq"],
        ['iso' => 'IR', 'iso3' => 'IRN', 'phone' => '+98', 'en' => "Iran"],
        ['iso' => 'IS', 'iso3' => 'ISL', 'phone' => '+354', 'en' => "Iceland"],
        ['iso' => 'IT', 'iso3' => 'ITA', 'phone' => '+39', 'en' => "Italy"],
        ['iso' => 'JE', 'iso3' => 'JEY', 'phone' => '+44', 'en' => "Jersey"],
        ['iso' => 'JM', 'iso3' => 'JAM', 'phone' => '+1', 'en' => "Jamaica"],
        ['iso' => 'JO', 'iso3' => 'JOR', 'phone' => '+962', 'en' => "Jordan"],
        ['iso' => 'JP', 'iso3' => 'JPN', 'phone' => '+81', 'en' => "Japan"],
        ['iso' => 'KE', 'iso3' => 'KEN', 'phone' => '+254', 'en' => "Kenya"],
        ['iso' => 'KG', 'iso3' => 'KGZ', 'phone' => '+996', 'en' => "Kyrgyzstan"],
        ['iso' => 'KH', 'iso3' => 'KHM', 'phone' => '+855', 'en' => "Cambodia"],
        ['iso' => 'KI', 'iso3' => 'KIR', 'phone' => '+686', 'en' => "Kiribati"],
        ['iso' => 'KM', 'iso3' => 'COM', 'phone' => '+269', 'en' => "Comoros"],
        ['iso' => 'KN', 'iso3' => 'KNA', 'phone' => '+1', 'en' => "St. Kitts"],
        ['iso' => 'KP', 'iso3' => 'PRK', 'phone' => '+850', 'en' => "North Korea"],
        ['iso' => 'KR', 'iso3' => 'KOR', 'phone' => '+82', 'en' => "South Korea"],
        ['iso' => 'KW', 'iso3' => 'KWT', 'phone' => '+965', 'en' => "Kuwait"],
        ['iso' => 'KY', 'iso3' => 'CYM', 'phone' => '+1', 'en' => "Cayman Islands"],
        ['iso' => 'KZ', 'iso3' => 'KAZ', 'phone' => '+7', 'en' => "Kazakhstan"],
        ['iso' => 'LA', 'iso3' => 'LAO', 'phone' => '+856', 'en' => "Laos"],
        ['iso' => 'LB', 'iso3' => 'LBN', 'phone' => '+961', 'en' => "Lebanon"],
        ['iso' => 'LC', 'iso3' => 'LCA', 'phone' => '+1', 'en' => "St. Lucia"],
        ['iso' => 'LI', 'iso3' => 'LIE', 'phone' => '+423', 'en' => "Liechtenstein"],
        ['iso' => 'LK', 'iso3' => 'LKA', 'phone' => '+94', 'en' => "Sri Lanka"],
        ['iso' => 'LR', 'iso3' => 'LBR', 'phone' => '+231', 'en' => "Liberia"],
        ['iso' => 'LS', 'iso3' => 'LSO', 'phone' => '+266', 'en' => "Lesotho"],
        ['iso' => 'LT', 'iso3' => 'LTU', 'phone' => '+370', 'en' => "Lithuania"],
        ['iso' => 'LU', 'iso3' => 'LUX', 'phone' => '+352', 'en' => "Luxembourg"],
        ['iso' => 'LV', 'iso3' => 'LVA', 'phone' => '+371', 'en' => "Latvia"],
        ['iso' => 'LY', 'iso3' => 'LBY', 'phone' => '+218', 'en' => "Libya"],
        ['iso' => 'MA', 'iso3' => 'MAR', 'phone' => '+212', 'en' => "Morocco"],
        ['iso' => 'MC', 'iso3' => 'MCO', 'phone' => '+377', 'en' => "Monaco"],
        ['iso' => 'MD', 'iso3' => 'MDA', 'phone' => '+373', 'en' => "Moldova"],
        ['iso' => 'ME', 'iso3' => 'MNE', 'phone' => '+382', 'en' => "Montenegro"],
        ['iso' => 'MF', 'iso3' => 'MAF', 'phone' => '+590', 'en' => "Saint Martin"],
        ['iso' => 'MG', 'iso3' => 'MDG', 'phone' => '+261', 'en' => "Madagascar"],
        ['iso' => 'MH', 'iso3' => 'MHL', 'phone' => '+692', 'en' => "Marshall Islands"],
        ['iso' => 'MK', 'iso3' => 'MKD', 'phone' => '+389', 'en' => "North Macedonia"],
        ['iso' => 'ML', 'iso3' => 'MLI', 'phone' => '+223', 'en' => "Mali"],
        ['iso' => 'MM', 'iso3' => 'MMR', 'phone' => '+95', 'en' => "Myanmar [Burma]"],
        ['iso' => 'MN', 'iso3' => 'MNG', 'phone' => '+976', 'en' => "Mongolia"],
        ['iso' => 'MO', 'iso3' => 'MAC', 'phone' => '+853', 'en' => "Macau"],
        ['iso' => 'MP', 'iso3' => 'MNP', 'phone' => '+1', 'en' => "Northern Mariana Islands"],
        ['iso' => 'MQ', 'iso3' => 'MTQ', 'phone' => '+596', 'en' => "Martinique"],
        ['iso' => 'MR', 'iso3' => 'MRT', 'phone' => '+222', 'en' => "Mauritania"],
        ['iso' => 'MS', 'iso3' => 'MSR', 'phone' => '+1', 'en' => "Montserrat"],
        ['iso' => 'MT', 'iso3' => 'MLT', 'phone' => '+356', 'en' => "Malta"],
        ['iso' => 'MU', 'iso3' => 'MUS', 'phone' => '+230', 'en' => "Mauritius"],
        ['iso' => 'MV', 'iso3' => 'MDV', 'phone' => '+960', 'en' => "Maldives"],
        ['iso' => 'MW', 'iso3' => 'MWI', 'phone' => '+265', 'en' => "Malawi"],
        ['iso' => 'MX', 'iso3' => 'MEX', 'phone' => '+52', 'en' => "Mexico"],
        ['iso' => 'MY', 'iso3' => 'MYS', 'phone' => '+60', 'en' => "Malaysia"],
        ['iso' => 'MZ', 'iso3' => 'MOZ', 'phone' => '+258', 'en' => "Mozambique"],
        ['iso' => 'NA', 'iso3' => 'NAM', 'phone' => '+264', 'en' => "Namibia"],
        ['iso' => 'NC', 'iso3' => 'NCL', 'phone' => '+687', 'en' => "New Caledonia"],
        ['iso' => 'NE', 'iso3' => 'NER', 'phone' => '+227', 'en' => "Niger"],
        ['iso' => 'NF', 'iso3' => 'NFK', 'phone' => '+672', 'en' => "Norfolk Island"],
        ['iso' => 'NG', 'iso3' => 'NGA', 'phone' => '+234', 'en' => "Nigeria"],
        ['iso' => 'NI', 'iso3' => 'NIC', 'phone' => '+505', 'en' => "Nicaragua"],
        ['iso' => 'NL', 'iso3' => 'NLD', 'phone' => '+31', 'en' => "Netherlands"],
        ['iso' => 'NO', 'iso3' => 'NOR', 'phone' => '+47', 'en' => "Norway"],
        ['iso' => 'NP', 'iso3' => 'NPL', 'phone' => '+977', 'en' => "Nepal"],
        ['iso' => 'NR', 'iso3' => 'NRU', 'phone' => '+674', 'en' => "Nauru"],
        ['iso' => 'NU', 'iso3' => 'NIU', 'phone' => '+683', 'en' => "Niue"],
        ['iso' => 'NZ', 'iso3' => 'NZL', 'phone' => '+64', 'en' => "New Zealand"],
        ['iso' => 'OM', 'iso3' => 'OMN', 'phone' => '+968', 'en' => "Oman"],
        ['iso' => 'PA', 'iso3' => 'PAN', 'phone' => '+507', 'en' => "Panama"],
        ['iso' => 'PE', 'iso3' => 'PER', 'phone' => '+51', 'en' => "Peru"],
        ['iso' => 'PF', 'iso3' => 'PYF', 'phone' => '+689', 'en' => "French Polynesia"],
        ['iso' => 'PG', 'iso3' => 'PNG', 'phone' => '+675', 'en' => "Papua New Guinea"],
        ['iso' => 'PH', 'iso3' => 'PHL', 'phone' => '+63', 'en' => "Philippines"],
        ['iso' => 'PK', 'iso3' => 'PAK', 'phone' => '+92', 'en' => "Pakistan"],
        ['iso' => 'PL', 'iso3' => 'POL', 'phone' => '+48', 'en' => "Poland"],
        ['iso' => 'PM', 'iso3' => 'SPM', 'phone' => '+508', 'en' => "Saint Pierre and Miquelon"],
        ['iso' => 'PR', 'iso3' => 'PRI', 'phone' => '+1', 'en' => "Puerto Rico"],
        ['iso' => 'PS', 'iso3' => 'PSE', 'phone' => '+970', 'en' => "Palestinian Territories"],
        ['iso' => 'PT', 'iso3' => 'PRT', 'phone' => '+351', 'en' => "Portugal"],
        ['iso' => 'PW', 'iso3' => 'PLW', 'phone' => '+680', 'en' => "Palau"],
        ['iso' => 'PY', 'iso3' => 'PRY', 'phone' => '+595', 'en' => "Paraguay"],
        ['iso' => 'QA', 'iso3' => 'QAT', 'phone' => '+974', 'en' => "Qatar"],
        ['iso' => 'RE', 'iso3' => 'REU', 'phone' => '+262', 'en' => "Réunion"],
        ['iso' => 'RO', 'iso3' => 'ROU', 'phone' => '+40', 'en' => "Romania"],
        ['iso' => 'RS', 'iso3' => 'SRB', 'phone' => '+381', 'en' => "Serbia"],
        ['iso' => 'RU', 'iso3' => 'RUS', 'phone' => '+7', 'en' => "Russia"],
        ['iso' => 'RW', 'iso3' => 'RWA', 'phone' => '+250', 'en' => "Rwanda"],
        ['iso' => 'SA', 'iso3' => 'SAU', 'phone' => '+966', 'en' => "Saudi Arabia"],
        ['iso' => 'SB', 'iso3' => 'SLB', 'phone' => '+677', 'en' => "Solomon Islands"],
        ['iso' => 'SC', 'iso3' => 'SYC', 'phone' => '+248', 'en' => "Seychelles"],
        ['iso' => 'SD', 'iso3' => 'SDN', 'phone' => '+249', 'en' => "Sudan"],
        ['iso' => 'SE', 'iso3' => 'SWE', 'phone' => '+46', 'en' => "Sweden"],
        ['iso' => 'SG', 'iso3' => 'SGP', 'phone' => '+65', 'en' => "Singapore"],
        ['iso' => 'SH', 'iso3' => 'SHN', 'phone' => '+290', 'en' => "Saint Helena"],
        ['iso' => 'SI', 'iso3' => 'SVN', 'phone' => '+386', 'en' => "Slovenia"],
        ['iso' => 'SJ', 'iso3' => 'SJM', 'phone' => '+47', 'en' => "Svalbard and Jan Mayen"],
        ['iso' => 'SK', 'iso3' => 'SVK', 'phone' => '+421', 'en' => "Slovakia"],
        ['iso' => 'SL', 'iso3' => 'SLE', 'phone' => '+232', 'en' => "Sierra Leone"],
        ['iso' => 'SM', 'iso3' => 'SMR', 'phone' => '+378', 'en' => "San Marino"],
        ['iso' => 'SN', 'iso3' => 'SEN', 'phone' => '+221', 'en' => "Senegal"],
        ['iso' => 'SO', 'iso3' => 'SOM', 'phone' => '+252', 'en' => "Somalia"],
        ['iso' => 'SR', 'iso3' => 'SUR', 'phone' => '+597', 'en' => "Suriname"],
        ['iso' => 'SS', 'iso3' => 'SSD', 'phone' => '+211', 'en' => "South Sudan"],
        ['iso' => 'ST', 'iso3' => 'STP', 'phone' => '+239', 'en' => "São Tomé and Príncipe"],
        ['iso' => 'SV', 'iso3' => 'SLV', 'phone' => '+503', 'en' => "El Salvador"],
        ['iso' => 'SX', 'iso3' => 'SXM', 'phone' => '+1', 'en' => "Sint Maarten"],
        ['iso' => 'SY', 'iso3' => 'SYR', 'phone' => '+963', 'en' => "Syria"],
        ['iso' => 'SZ', 'iso3' => 'SWZ', 'phone' => '+268', 'en' => "Eswatini"],
        ['iso' => 'TC', 'iso3' => 'TCA', 'phone' => '+1', 'en' => "Turks and Caicos Islands"],
        ['iso' => 'TD', 'iso3' => 'TCD', 'phone' => '+235', 'en' => "Chad"],
        ['iso' => 'TG', 'iso3' => 'TGO', 'phone' => '+228', 'en' => "Togo"],
        ['iso' => 'TH', 'iso3' => 'THA', 'phone' => '+66', 'en' => "Thailand"],
        ['iso' => 'TJ', 'iso3' => 'TJK', 'phone' => '+992', 'en' => "Tajikistan"],
        ['iso' => 'TK', 'iso3' => 'TKL', 'phone' => '+690', 'en' => "Tokelau"],
        ['iso' => 'TL', 'iso3' => 'TLS', 'phone' => '+670', 'en' => "East Timor"],
        ['iso' => 'TM', 'iso3' => 'TKM', 'phone' => '+993', 'en' => "Turkmenistan"],
        ['iso' => 'TN', 'iso3' => 'TUN', 'phone' => '+216', 'en' => "Tunisia"],
        ['iso' => 'TO', 'iso3' => 'TON', 'phone' => '+676', 'en' => "Tonga"],
        ['iso' => 'TR', 'iso3' => 'TUR', 'phone' => '+90', 'en' => "Turkey"],
        ['iso' => 'TT', 'iso3' => 'TTO', 'phone' => '+1', 'en' => "Trinidad/Tobago"],
        ['iso' => 'TV', 'iso3' => 'TUV', 'phone' => '+688', 'en' => "Tuvalu"],
        ['iso' => 'TW', 'iso3' => 'TWN', 'phone' => '+886', 'en' => "Taiwan"],
        ['iso' => 'TZ', 'iso3' => 'TZA', 'phone' => '+255', 'en' => "Tanzania"],
        ['iso' => 'UA', 'iso3' => 'UKR', 'phone' => '+380', 'en' => "Ukraine"],
        ['iso' => 'UG', 'iso3' => 'UGA', 'phone' => '+256', 'en' => "Uganda"],
        ['iso' => 'US', 'iso3' => 'USA', 'phone' => '+1', 'en' => "United States"],
        ['iso' => 'UY', 'iso3' => 'URY', 'phone' => '+598', 'en' => "Uruguay"],
        ['iso' => 'UZ', 'iso3' => 'UZB', 'phone' => '+998', 'en' => "Uzbekistan"],
        ['iso' => 'VA', 'iso3' => 'VAT', 'phone' => '+379', 'en' => "Vatican City"],
        ['iso' => 'VC', 'iso3' => 'VCT', 'phone' => '+1', 'en' => "St. Vincent"],
        ['iso' => 'VE', 'iso3' => 'VEN', 'phone' => '+58', 'en' => "Venezuela"],
        ['iso' => 'VG', 'iso3' => 'VGB', 'phone' => '+1', 'en' => "British Virgin Islands"],
        ['iso' => 'VI', 'iso3' => 'VIR', 'phone' => '+1', 'en' => "U.S. Virgin Islands"],
        ['iso' => 'VN', 'iso3' => 'VNM', 'phone' => '+84', 'en' => "Vietnam"],
        ['iso' => 'VU', 'iso3' => 'VUT', 'phone' => '+678', 'en' => "Vanuatu"],
        ['iso' => 'WF', 'iso3' => 'WLF', 'phone' => '+681', 'en' => "Wallis and Futuna"],
        ['iso' => 'WS', 'iso3' => 'WSM', 'phone' => '+685', 'en' => "Samoa"],
        ['iso' => 'XK', 'iso3' => 'XKX', 'phone' => '+383', 'en' => "Kosovo"],
        ['iso' => 'YE', 'iso3' => 'YEM', 'phone' => '+967', 'en' => "Yemen"],
        ['iso' => 'YT', 'iso3' => 'MYT', 'phone' => '+262', 'en' => "Mayotte"],
        ['iso' => 'ZA', 'iso3' => 'ZAF', 'phone' => '+27', 'en' => "South Africa"],
        ['iso' => 'ZM', 'iso3' => 'ZMB', 'phone' => '+260', 'en' => "Zambia"],
        ['iso' => 'ZW', 'iso3' => 'ZWE', 'phone' => '+263', 'en' => "Zimbabwe"],
    ];
}
