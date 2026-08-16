<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\CP\Database\Seeders\CPDatabaseSeeder;
use Modules\Form\Database\Seeders\AgencyAndBdFormsSeeder;
use Modules\Form\Database\Seeders\CustomFieldWidgetSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            LanguageSeeder::class,
            PaymentGatewaysSeeder::class,
            CustomFieldWidgetSeeder::class,
            AgencyAndBdFormsSeeder::class,
            WhiteLabelSettingsSeeder::class,
            BrandIdentityDefaultsSeeder::class,
            DefaultRoomCategorySeeder::class,
            DefaultBackgroundSeeder::class,
            CatalogSeeder::class,
            FeatureFlagsDefaultsSeeder::class,
            EventsBootstrapSeeder::class,
            WithdrawMethodsSeeder::class,
        ]);
    }
}
