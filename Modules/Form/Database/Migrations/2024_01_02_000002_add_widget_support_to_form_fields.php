<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('widget_id')->nullable()->after('field_type')
                ->constrained('custom_field_widgets')->onDelete('set null')
                ->comment('Link to custom widget if this field uses one');

            $table->json('widget_config')->nullable()->after('widget_id')
                ->comment('Custom widget configuration (override default config, filters, etc.)');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['widget_id']);
            $table->dropColumn(['widget_id', 'widget_config']);
        });
    }
};
