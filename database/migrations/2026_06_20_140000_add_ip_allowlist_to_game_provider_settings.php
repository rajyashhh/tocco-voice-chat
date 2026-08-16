<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_provider_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('game_provider_settings', 'ip_allowlist')) {
                // Comma/newline separated IPs or CIDR ranges. Empty = allow all
                // (backward-compatible so nothing breaks before the owner fills it).
                $table->text('ip_allowlist')->nullable()->after('webhook_sign_key_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_provider_settings', function (Blueprint $table) {
            if (Schema::hasColumn('game_provider_settings', 'ip_allowlist')) {
                $table->dropColumn('ip_allowlist');
            }
        });
    }
};
