<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['room_status', 'type', 'pin']);
            $table->index(['room_status', 'top_room', 'pin', 'hour_hot']);
            $table->index(['room_status', 'type', 'pin', 'hour_hot']);
            $table->index('pin', 'hour_hot');
            $table->index(['created_at']);
            $table->index(['session']);
            $table->index(['is_top']);
            $table->index(['id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['id', 'deleted_at']);
            $table->index(['id', 'country_id', 'lat', 'long']);
        });

        Schema::table('packs', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'is_used', 'expire', 'deleted_at']);
            $table->index(['type', 'is_used', 'expire']);
        });

        Schema::table('box_uses', function (Blueprint $table) {
            $table->index(['room_id', 'not_used_num', 'end_at']);
            $table->index(['room_id', 'end_at']);
        });

        Schema::table('user_box_gifts', function (Blueprint $table) {
            $table->index(['box_uses_id', 'user_id']);
        });

        Schema::table('room_visitors', function (Blueprint $table) {
            $table->index(['room_id']);
        });
    }

    public function down(): void
    {
        Schema::table('room_visitors', fn (Blueprint $t) => $t->dropIndex(['room_id']));
        Schema::table('user_box_gifts', fn (Blueprint $t) => $t->dropIndex(['box_uses_id', 'user_id']));

        Schema::table('box_uses', function (Blueprint $t) {
            $t->dropIndex(['room_id', 'not_used_num', 'end_at']);
            $t->dropIndex(['room_id', 'end_at']);
        });

        Schema::table('packs', function (Blueprint $t) {
            $t->dropIndex(['user_id', 'type', 'is_used', 'expire', 'deleted_at']);
            $t->dropIndex(['type', 'is_used', 'expire']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['id', 'deleted_at']);
            $t->dropIndex(['id', 'country_id', 'lat', 'long']);
        });

        Schema::table('rooms', function (Blueprint $t) {
            $t->dropIndex(['room_status', 'type', 'pin']);
            $t->dropIndex(['room_status', 'top_room', 'pin', 'hour_hot']);
            $t->dropIndex(['room_status', 'type', 'pin', 'hour_hot']);
            $t->dropIndex('pin', 'hour_hot');
            $t->dropIndex(['created_at']);
            $t->dropIndex(['session']);
            $t->dropIndex(['is_top']);
            $t->dropIndex(['id']);
        });
    }

};
