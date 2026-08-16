<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    public function up(): void
    {
        Schema::table('charge_king_winners', function (Blueprint $table) {
            $table->unsignedTinyInteger('rank')->default(1)->after('month');
        });

        Schema::table('charge_king_winners', function (Blueprint $table) {
            if (!$this->hasIndex('charge_king_winners', 'charge_king_winners_month_rank_unique')) {
                $table->unique(['month', 'rank'], 'charge_king_winners_month_rank_unique');
            }
            if ($this->hasIndex('charge_king_winners', 'charge_king_winners_month_unique')) {
                $table->dropUnique('charge_king_winners_month_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('charge_king_winners', function (Blueprint $table) {
            if ($this->hasIndex('charge_king_winners', 'charge_king_winners_month_rank_unique')) {
                $table->dropUnique('charge_king_winners_month_rank_unique');
            }
            if (!$this->hasIndex('charge_king_winners', 'charge_king_winners_month_unique')) {
                $table->unique('month', 'charge_king_winners_month_unique');
            }
            $table->dropColumn('rank');
        });
    }
};
