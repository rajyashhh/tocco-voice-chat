<?php

namespace Database\Seeders;

use App\Helpers\Common;
use Illuminate\Database\Seeder;
use App\Models\Config as ConfigModel;
use Modules\Badge\Entities\Badge;

class ConfigBadgesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            'host',
            'agency_owner',
            'shipping',
            'bd',
        ];

        $langs = ['default', 'en', 'ar', 'tr', 'hi', 'id']; // rename to $langs to avoid overwriting
        $configKeys = [];

        foreach ($types as $type) {
            $badge = Badge::create([
                'type' => 'top',
                'name' => $type
            ]);

            foreach ($langs as $l) {
                $currentLang = $l;

                if ($l === 'default') {
                    $defaultLang = Common::getSettingValue('default_language');
                    $currentLang = $defaultLang; // use default language for config key
                }

                $configKeys = "{$currentLang}_{$type}";
                $configs = ConfigModel::where('name', $configKeys)->value('value');

                $badge->images()->create([
                    'language' => $l, // store original language tab
                    'image' => $configs ?? '',
                    'show_image' => $configs ?? '',
                    'image_type' => 'image',
                ]);
            }
        }
    }
}
