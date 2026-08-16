<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateWalletAuditLogsTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('action'); 
            $table->json('details'); 
            $table->ipAddress()->nullable(); 
            $table->text('user_agent')->nullable(); 
            $table->timestamp('created_at')->index();

            $table->index(['user_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_audit_logs');
    }
}
