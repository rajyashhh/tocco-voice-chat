<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submission_values', function (Blueprint $table) {
            // Change field_value to support JSON for multiple selections
            $table->json('field_value_json')->nullable()->after('field_value')
                ->comment('JSON array for multiple selections from custom widgets');
        });
    }

    public function down(): void
    {
        Schema::table('form_submission_values', function (Blueprint $table) {
            $table->dropColumn('field_value_json');
        });
    }
};
