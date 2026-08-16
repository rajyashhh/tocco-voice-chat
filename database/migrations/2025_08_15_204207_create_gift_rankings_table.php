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
        Schema::dropIfExists('gift_rankings');

        Schema::create('gift_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('type');

            $table->string('role')->comment('Store sender or receiver ..etc');

            $table->unsignedBigInteger('ranker_id');
            $table->string('ranker_type');

            $table->decimal('total_gifts', 20, 2)->default(0);

            $table->timestamp('last_calculated_at')->nullable();

            $table->timestamps();

            $table->unique(['type', 'role', 'ranker_id', 'ranker_type']);
            $table->index(['type', 'role', 'total_gifts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_rankings');

    }
};
