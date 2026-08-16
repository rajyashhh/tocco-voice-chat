<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Interests feature removed entirely (owner decision, 2026-08-12): the admin
 * page, API, models and app screens are all deleted from source. This drops
 * the three storage artifacts:
 *  - reals_categories: pivot reals<->interests (feature never shipped to the
 *    app: the upload endpoint never sent categories)
 *  - AddinterestsForUsers: user<->interest pivot
 *  - interests: the catalog itself
 * Also removes the orphaned admin menu entry and its Content parent if empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reals_categories');
        Schema::dropIfExists('AddinterestsForUsers');
        Schema::dropIfExists('interests');

        DB::table('admin_menu')->where('uri', 'interests')->delete();
        $content = DB::table('admin_menu')
            ->where('title', 'Content')->whereNull('uri')->first();
        if ($content && !DB::table('admin_menu')->where('parent_id', $content->id)->exists()) {
            DB::table('admin_menu')->where('id', $content->id)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the feature's code no longer exists.
    }
};
