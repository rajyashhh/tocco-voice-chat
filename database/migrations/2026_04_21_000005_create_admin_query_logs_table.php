<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_query_logs', function (Blueprint $table) {
            $table->id();
            
            $table->string('table_name')->index();
            
            $table->decimal('query_duration', 10, 4)->comment('Query duration in seconds');
            $table->unsignedBigInteger('rows_examined')->default(0)->comment('Number of rows examined');
            $table->unsignedBigInteger('rows_returned')->default(0)->comment('Number of rows returned');
            
            $table->string('query_hash')->nullable()->index();
            
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['table_name', 'created_at']);
            $table->index(['query_duration', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_query_logs');
    }
};
