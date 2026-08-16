<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-approved 2026-08-14: the country-categories and multi-server features
 * are fully removed (admin pages, actions, models and every reading code path
 * were deleted in the same batch; GET /countries/categories keeps answering an
 * empty list for the published APK). This drops the now-dead storage:
 *
 *  - countries.country_category_id column (backfilled but never read anymore)
 *  - country_categories table (0 rows in production, zero readers)
 *  - servers table (0 rows in production, zero readers)
 *
 * Every step is guarded so the migration is safe to run on any environment
 * regardless of which of these still exist. down() restores the exact
 * structures their original create-migrations defined.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('countries') && Schema::hasColumn('countries', 'country_category_id')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('country_category_id');
            });
        }

        Schema::dropIfExists('country_categories');
        Schema::dropIfExists('servers');
    }

    public function down(): void
    {
        if (Schema::hasTable('countries') && !Schema::hasColumn('countries', 'country_category_id')) {
            Schema::table('countries', function (Blueprint $table) {
                // As created by 2025_12_29_143018 (unsignedBigInteger, nullable).
                $table->unsignedBigInteger('country_category_id')->nullable();
            });
        }

        if (!Schema::hasTable('country_categories')) {
            // As created by 2025_12_29_135641.
            Schema::create('country_categories', function (Blueprint $table) {
                $table->id();
                $table->json('title');
                $table->string('type');
                $table->bigInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('servers')) {
            // As created by 2024_06_02_093342 + later alters (default, bucket,
            // backgrounds, status).
            Schema::create('servers', function (Blueprint $table) {
                $table->id();
                $table->string('server_name')->nullable();
                $table->string('domain')->nullable();
                $table->string('short_name')->nullable();
                $table->string('img')->nullable();
                $table->string('description_ar')->nullable();
                $table->string('description_en')->nullable();
                $table->timestamps();
                $table->integer('default')->default(0);
                $table->string('bucket_name')->nullable();
                $table->string('login_background')->nullable();
                $table->string('splash_background')->nullable();
                $table->boolean('status')->default(1);
            });
        }
    }
};