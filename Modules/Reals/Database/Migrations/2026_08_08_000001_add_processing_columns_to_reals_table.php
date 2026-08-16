<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reals', function (Blueprint $table) {
            // processing -> ready -> (failed). Feed only ever serves 'ready', so a
            // reel can never reach the app player before transcoding finished.
            $table->string('status', 20)->default('processing')->after('url')->index();
            // Original upload path (pre-sign/...), kept so a reel can always be
            // re-transcoded from source. `url` becomes the transcoded H.264 mp4.
            $table->string('source_url')->nullable()->after('status');
            $table->string('thumbnail')->nullable()->after('sub_video');
            $table->decimal('duration', 8, 2)->nullable()->after('thumbnail');
            $table->string('fail_reason', 500)->nullable()->after('duration');
        });
    }

    public function down()
    {
        Schema::table('reals', function (Blueprint $table) {
            $table->dropColumn(['status', 'source_url', 'thumbnail', 'duration', 'fail_reason']);
        });
    }
};
