<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix Bug: Users Stuck at Level 22
 *
 * Problem: Levels 22–29 all have exp=83,100,000 and levels 31–35 all have exp=208,620,000
 * When multiple levels share the same exp, the query returns the lowest level due to lowest primary key
 *
 * Solution: Set distinct exp values for these level ranges
 */
class FixDuplicateVipExpLevels extends Migration
{
    public function up(): void
    {
   
        DB::table('vips')
            ->where(['type' => 2, 'level' => 23])
            ->update(['exp' => 95400000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 24])
            ->update(['exp' => 107700000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 25])
            ->update(['exp' => 120000000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 26])
            ->update(['exp' => 132300000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 27])
            ->update(['exp' => 144600000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 28])
            ->update(['exp' => 156900000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 29])
            ->update(['exp' => 169200000]);

    
        DB::table('vips')
            ->where(['type' => 2, 'level' => 31])
            ->update(['exp' => 187990000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 32])
            ->update(['exp' => 193160000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 33])
            ->update(['exp' => 198330000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 34])
            ->update(['exp' => 203500000]);

        DB::table('vips')
            ->where(['type' => 2, 'level' => 35])
            ->update(['exp' => 208620000]);
    }

    public function down(): void
    {
        // Revert to original duplicate values (not recommended in production)
        DB::table('vips')
            ->where(['type' => 2])
            ->whereIn('level', [23, 24, 25, 26, 27, 28, 29])
            ->update(['exp' => 83100000]);

        DB::table('vips')
            ->where(['type' => 2])
            ->whereIn('level', [31, 32, 33, 34, 35])
            ->update(['exp' => 208620000]);
    }
}
