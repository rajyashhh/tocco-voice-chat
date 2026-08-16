<?php

namespace Database\Seeders;

use App\Models\Timezone;
use DateTimeZone;
use DateTime;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimezonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all PHP-supported timezones
        $timezones = DateTimeZone::listIdentifiers();
        // Clear existing timezones (optional)
        DB::table('timezones')->truncate();

        // Insert timezones
        foreach ($timezones as $index => $timezone) {
            $datetime = new DateTime("now", new DateTimeZone($timezone));
            $offsetInHours = $datetime->getOffset() / 3600;
            $formattedOffset =  ($offsetInHours < 0 ? $offsetInHours : '+' . $offsetInHours);
            Timezone::create([
                'name' => $timezone,
                'offset' => $formattedOffset
            ]);
        }
    }
}
