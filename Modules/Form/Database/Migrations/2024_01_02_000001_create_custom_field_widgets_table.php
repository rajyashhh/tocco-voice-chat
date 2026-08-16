<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_type', 50)->comment('bd_selector, user_picker, custom_dropdown, etc.');
            $table->json('widget_name')->comment('Multi-language widget name');
            $table->json('description')->nullable()->comment('Multi-language description');
            $table->string('component_path')->comment('Blade component path: components.widgets.bd-selector');
            $table->json('default_config')->nullable()->comment('Default widget configuration (data source, api endpoint, etc.)');
            $table->boolean('allows_multiple')->default(false)->comment('Allow multiple selections');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('widget_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_widgets');
    }
};
