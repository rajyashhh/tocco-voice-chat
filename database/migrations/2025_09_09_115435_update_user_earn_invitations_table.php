<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('user_earn_invitations', function (Blueprint $table) {
            $table->decimal('user_charge', 20, 2)->nullable()->change();
            $table->decimal('parent_percentage', 20, 2)->nullable()->change();

            $table->enum('source_type', [
                'first_join_reward_host',
                'first_join_reward_invitee',
                'charge_percentage'
            ])->after('parent_percentage');

            $table->decimal('amount', 12, 2)->after('source_type');
            $table->bigInteger('charge_id')->unsigned()->nullable()->after('amount');
            $table->boolean('is_claimed')->default(0)->after('charge_id');
            $table->json('meta')->nullable()->after('is_claimed');
        });
    }


    public function down()
    {
        Schema::table('user_earn_invitations', function (Blueprint $table) {
            $table->decimal('user_charge', 12, 2)->nullable(false)->change();
            $table->decimal('parent_percentage', 5, 2)->nullable(false)->change();

            $table->dropColumn(['source_type', 'amount', 'charge_id', 'is_claimed', 'meta']);
        });
    }
};
