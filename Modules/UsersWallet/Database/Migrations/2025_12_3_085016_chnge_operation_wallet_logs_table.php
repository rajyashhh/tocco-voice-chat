<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `wallet_logs` MODIFY `operation` ENUM('add','subtract','transfer') NOT NULL DEFAULT 'add'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `wallet_logs` MODIFY `operation` ENUM('add','subtract') NOT NULL DEFAULT 'add'");
    }
}
;