<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $versionNames = json_encode([
            "17" => "1.0.15",
            "18" => "1.0.16",
            "19" => "1.0.17",
        ]);

        foreach (['android', 'ios', 'huawei'] as $os) {
            if (settings()->get($os . '_version_names') === null) {
                settings()->set($os . '_version_names', $versionNames);
            }

            if (settings()->get($os . '_current_version_name') === null) {
                settings()->set($os . '_current_version_name', "1.0.17");
            }
        }
    }

    public function down(): void
    {
        foreach (['android', 'ios', 'huawei'] as $os) {
            settings()->remove($os . '_version_names');
            settings()->remove($os . '_current_version_name');
        }
    }
};
