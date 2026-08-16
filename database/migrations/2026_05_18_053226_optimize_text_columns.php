<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Optimization: Convert LONGTEXT to TEXT or VARCHAR where appropriate.
 *
 * Benefits:
 * - Reduced storage overhead (4 bytes vs 2 bytes length prefix)
 * - Better index support for smaller TEXT fields
 * - Faster queries on smaller data types
 *
 * Estimated Savings: ~50 MB
 *
 * Safety: Validates data before conversion
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        $this->info('🔍 Validating TEXT column data before conversion...');

        $validation = $this->validateTextLengths();

        if (!$validation['safe']) {
            $errorMessage = "❌ Data validation failed! Cannot proceed with migration.\n\n";
            $errorMessage .= "Violations found:\n";
            foreach ($validation['violations'] as $violation) {
                $errorMessage .= "  - {$violation}\n";
            }

            Log::error('TEXT column optimization migration failed validation', [
                'violations' => $validation['violations']
            ]);

            throw new \Exception($errorMessage);
        }

        $this->info('✅ Data validation passed.');

        // ==========================================
        // Convert LONGTEXT → TEXT (settings table)
        // ==========================================

        $this->info('🔄 Converting settings.value from LONGTEXT to TEXT...');

        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
        });

        // ==========================================
        // Convert LONGTEXT → VARCHAR (brand_images)
        // ==========================================

        $this->info('🔄 Converting brand_images.name from LONGTEXT to VARCHAR...');

        Schema::table('brand_images', function (Blueprint $table) {
            $table->string('name')->change();
        });

        // ==========================================
        // Convert nowpayments_orders columns
        // ==========================================

        $this->info('🔄 Optimizing nowpayments_orders columns...');

        Schema::table('nowpayments_orders', function (Blueprint $table) {
            $table->string('payment_id')->change();
            $table->string('pay_address', 500)->change();
            $table->string('order_id')->change();
        });

        // ==========================================
        // Convert charge_invoices columns
        // ==========================================

        $this->info('🔄 Optimizing charge_invoices columns...');

        Schema::table('charge_invoices', function (Blueprint $table) {
            $table->text('reason_en')->nullable()->change();
            $table->text('reason_ar')->nullable()->change();
            $table->text('invoice')->nullable()->change();
        });

        $this->info('✅ TEXT column optimization completed successfully!');*/
    }

    /**
     * Validate TEXT column lengths
     */
    private function validateTextLengths(): array
    {
      /*  $violations = [];
        $safe = true;

        // Check settings.value (TEXT limit = 65535)
        $maxSettingsValue = DB::selectOne("
            SELECT MAX(LENGTH(value)) as max FROM settings WHERE value IS NOT NULL
        ")->max ?? 0;

        if ($maxSettingsValue > 65535) {
            $violations[] = "settings.value max={$maxSettingsValue} exceeds TEXT limit (65535)";
            $safe = false;
        }

        // Check brand_images.name (VARCHAR 255 limit)
        $maxBrandName = DB::selectOne("
            SELECT MAX(LENGTH(name)) as max FROM brand_images WHERE name IS NOT NULL
        ")->max ?? 0;

        if ($maxBrandName > 255) {
            $violations[] = "brand_images.name max={$maxBrandName} exceeds VARCHAR(255)";
            $safe = false;
        }

        // Check nowpayments_orders.payment_id
        $maxPaymentId = DB::selectOne("
            SELECT MAX(LENGTH(payment_id)) as max FROM nowpayments_orders WHERE payment_id IS NOT NULL
        ")->max ?? 0;

        if ($maxPaymentId > 255) {
            $violations[] = "nowpayments_orders.payment_id max={$maxPaymentId} exceeds VARCHAR(255)";
            $safe = false;
        }

        // Check nowpayments_orders.pay_address
        $maxPayAddress = DB::selectOne("
            SELECT MAX(LENGTH(pay_address)) as max FROM nowpayments_orders WHERE pay_address IS NOT NULL
        ")->max ?? 0;

        if ($maxPayAddress > 500) {
            $violations[] = "nowpayments_orders.pay_address max={$maxPayAddress} exceeds VARCHAR(500)";
            $safe = false;
        }

        return [
            'safe' => $safe,
            'violations' => $violations
        ];
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->warn('⚠️  Rolling back TEXT column optimizations');

        // Revert settings
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('value')->nullable()->change();
        });

        // Revert brand_images
        Schema::table('brand_images', function (Blueprint $table) {
            $table->longText('name')->change();
        });

        // Revert nowpayments_orders
        Schema::table('nowpayments_orders', function (Blueprint $table) {
            $table->longText('payment_id')->change();
            $table->longText('pay_address')->change();
            $table->longText('order_id')->change();
        });

        // Revert charge_invoices
        Schema::table('charge_invoices', function (Blueprint $table) {
            $table->longText('reason_en')->nullable()->change();
            $table->longText('reason_ar')->nullable()->change();
            $table->longText('invoice')->nullable()->change();
        });

        $this->info('Rollback completed.');
    }

    /**
     * Helper methods
     */
    private function info(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
        Log::info($message);
    }

    private function warn(string $message): void
    {
        if (app()->runningInConsole()) {
            echo '⚠️  ' . $message . PHP_EOL;
        }
        Log::warning($message);
    }
};
