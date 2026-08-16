<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix malformed options data that was saved when options was translatable
        $fields = DB::table('form_fields')
            ->whereNotNull('options')
            ->where('options', '!=', 'null')
            ->get();

        foreach ($fields as $field) {
            $options = json_decode($field->options, true);
            
            // Skip if already in correct format (simple key-value array)
            if (is_array($options) && !isset($options['{']) && !isset($options['}'])) {
                continue;
            }
            
            // Set to null for malformed data - user can re-enter
            DB::table('form_fields')
                ->where('id', $field->id)
                ->update(['options' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this operation
    }
};
