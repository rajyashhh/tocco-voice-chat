<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_provider_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('game_provider_settings', 'webhook_sign_key_source')) {
                $table->string('webhook_sign_key_source')->default('app_id')->after('gsp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_provider_settings', function (Blueprint $table) {
            if (Schema::hasColumn('game_provider_settings', 'webhook_sign_key_source')) {
                $table->dropColumn('webhook_sign_key_source');
            }
        });
    }
};
