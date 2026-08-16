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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->hasIndex('users', 'idx_users_online_lastseen')) {
                    $table->index(['online', 'last_seen_at'], 'idx_users_online_lastseen');
                }
            });
        }

        if (Schema::hasTable('user_official_messages')) {
            Schema::table('user_official_messages', function (Blueprint $table) {
                if (!$this->hasIndex('user_official_messages', 'idx_uom_user_id')) {
                    $table->index('user_id', 'idx_uom_user_id');
                }
                if (!$this->hasIndex('user_official_messages', 'idx_uom_official_message_id')) {
                    $table->index('official_message_id', 'idx_uom_official_message_id');
                }
                if (!$this->hasIndex('user_official_messages', 'idx_uom_created_at')) {
                    $table->index('created_at', 'idx_uom_created_at');
                }
                if (!$this->hasIndex('user_official_messages', 'idx_uom_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_uom_user_created');
                }
            });
        }

        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                if (!$this->hasIndex('user_diamond_logs', 'idx_udl_user_id')) {
                    $table->index('user_id', 'idx_udl_user_id');
                }
                if (!$this->hasIndex('user_diamond_logs', 'idx_udl_created_at')) {
                    $table->index('created_at', 'idx_udl_created_at');
                }
                if (!$this->hasIndex('user_diamond_logs', 'idx_udl_type')) {
                    $table->index('type', 'idx_udl_type');
                }
                if (!$this->hasIndex('user_diamond_logs', 'idx_udl_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_udl_user_created');
                }
            });
        }

        if (Schema::hasTable('user_coin_logs')) {
            Schema::table('user_coin_logs', function (Blueprint $table) {
                if (!$this->hasIndex('user_coin_logs', 'idx_ucl_created_at')) {
                    $table->index('created_at', 'idx_ucl_created_at');
                }
                if (!$this->hasIndex('user_coin_logs', 'idx_ucl_type')) {
                    $table->index('type', 'idx_ucl_type');
                }
                if (!$this->hasIndex('user_coin_logs', 'idx_ucl_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_ucl_user_created');
                }
                if ($this->hasIndex('user_coin_logs', 'user_coin_logs_user_id_index')) {
                    $table->dropIndex('user_coin_logs_user_id_index');
                }
            });
        }

        if (Schema::hasTable('live_times')) {
            Schema::table('live_times', function (Blueprint $table) {
                if (!$this->hasIndex('live_times', 'idx_lt_uid_start')) {
                    $table->index(['uid', 'start_time'], 'idx_lt_uid_start');
                }
                if (!$this->hasIndex('live_times', 'idx_lt_user_id')) {
                    $table->index('user_id', 'idx_lt_user_id');
                }
            });
        }

        if (Schema::hasTable('fair_luck_transactions')) {
            Schema::table('fair_luck_transactions', function (Blueprint $table) {
                if (!$this->hasIndex('fair_luck_transactions', 'idx_flt_created_at')) {
                    $table->index('created_at', 'idx_flt_created_at');
                }
            });
        }

        if (Schema::hasTable('fair_luck_wallet_histories')) {
            Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
                if (!$this->hasIndex('fair_luck_wallet_histories', 'idx_flwh_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_flwh_user_created');
                }
                if (!$this->hasIndex('fair_luck_wallet_histories', 'idx_flwh_wallet_created')) {
                    $table->index(['wallet_type', 'created_at'], 'idx_flwh_wallet_created');
                }
            });
        }

        if (Schema::hasTable('room_boom_gifts')) {
            Schema::table('room_boom_gifts', function (Blueprint $table) {
                if (!$this->hasIndex('room_boom_gifts', 'idx_rbg_created_at')) {
                    $table->index('created_at', 'idx_rbg_created_at');
                }
            });
        }

        if (Schema::hasTable('search_histories')) {
            Schema::table('search_histories', function (Blueprint $table) {
                if (!$this->hasIndex('search_histories', 'idx_sh_user_id')) {
                    $table->index(['user_id', 'created_at'], 'idx_sh_user_id');
                }
            });
        }

        if (Schema::hasTable('admin_operation_log')) {
            if (!$this->hasIndex('admin_operation_log', 'idx_aol_user_id')) {
                DB::statement('CREATE INDEX idx_aol_user_id ON admin_operation_log (user_id, id DESC)');
            }
        }

        if (Schema::hasTable('official_messages')) {
            Schema::table('official_messages', function (Blueprint $table) {
                if (!$this->hasIndex('official_messages', 'idx_om_user_type_date')) {
                    $table->index(['user_id', 'type', 'created_at'], 'idx_om_user_type_date');
                }
            });
        }

        if (Schema::hasTable('coin_game_users')) {
            Schema::table('coin_game_users', function (Blueprint $table) {
                if ($this->hasIndex('coin_game_users', 'idx_cgu_user_id')) {
                    $table->dropIndex('idx_cgu_user_id');
                }
                if ($this->hasIndex('coin_game_users', 'idx_cgu_created_at')) {
                    $table->dropIndex('idx_cgu_created_at');
                }
                if ($this->hasIndex('coin_game_users', 'idx_cgu_user_created')) {
                    $table->dropIndex('idx_cgu_user_created');
                }
            });
        }

        if (Schema::hasTable('coin_game_users_archive')) {
            Schema::table('coin_game_users_archive', function (Blueprint $table) {
                if (!$this->hasIndex('coin_game_users_archive', 'idx_cgua_type_created_coins')) {
                    $table->index(['type', 'created_at', 'coins'], 'idx_cgua_type_created_coins');
                }
                if ($this->hasIndex('coin_game_users_archive', 'idx_cgua_user_id')) {
                    $table->dropIndex('idx_cgua_user_id');
                }
                if ($this->hasIndex('coin_game_users_archive', 'idx_cgua_created_at')) {
                    $table->dropIndex('idx_cgua_created_at');
                }
            });
        }

        if (Schema::hasTable('packs')) {
            Schema::table('packs', function (Blueprint $table) {
                if ($this->hasIndex('packs', 'idx_type_used_expire')) {
                    $table->dropIndex('idx_type_used_expire');
                }
                if ($this->hasIndex('packs', 'idx_packs_type')) {
                    $table->dropIndex('idx_packs_type');
                }
                if ($this->hasIndex('packs', 'idx_packs_is_used')) {
                    $table->dropIndex('idx_packs_is_used');
                }
                if ($this->hasIndex('packs', 'idx_packs_expire')) {
                    $table->dropIndex('idx_packs_expire');
                }
                if ($this->hasIndex('packs', 'idx_packs_deleted_at')) {
                    $table->dropIndex('idx_packs_deleted_at');
                }
            });
        }

        if (Schema::hasTable('gift_logs')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                if ($this->hasIndex('gift_logs', 'idx_gift_logs_receiver_id')) {
                    $table->dropIndex('idx_gift_logs_receiver_id');
                }
                if ($this->hasIndex('gift_logs', 'idx_gift_logs_sender_id')) {
                    $table->dropIndex('idx_gift_logs_sender_id');
                }
                if ($this->hasIndex('gift_logs', 'gift_logs_roomowner_id_index')) {
                    $table->dropIndex('gift_logs_roomowner_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->hasIndex('users', 'idx_users_online_lastseen')) {
                    $table->dropIndex('idx_users_online_lastseen');
                }
            });
        }

        if (Schema::hasTable('user_official_messages')) {
            Schema::table('user_official_messages', function (Blueprint $table) {
                if ($this->hasIndex('user_official_messages', 'idx_uom_user_id')) {
                    $table->dropIndex('idx_uom_user_id');
                }
                if ($this->hasIndex('user_official_messages', 'idx_uom_official_message_id')) {
                    $table->dropIndex('idx_uom_official_message_id');
                }
                if ($this->hasIndex('user_official_messages', 'idx_uom_created_at')) {
                    $table->dropIndex('idx_uom_created_at');
                }
                if ($this->hasIndex('user_official_messages', 'idx_uom_user_created')) {
                    $table->dropIndex('idx_uom_user_created');
                }
            });
        }

        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                if ($this->hasIndex('user_diamond_logs', 'idx_udl_user_id')) {
                    $table->dropIndex('idx_udl_user_id');
                }
                if ($this->hasIndex('user_diamond_logs', 'idx_udl_created_at')) {
                    $table->dropIndex('idx_udl_created_at');
                }
                if ($this->hasIndex('user_diamond_logs', 'idx_udl_type')) {
                    $table->dropIndex('idx_udl_type');
                }
                if ($this->hasIndex('user_diamond_logs', 'idx_udl_user_created')) {
                    $table->dropIndex('idx_udl_user_created');
                }
            });
        }

        if (Schema::hasTable('user_coin_logs')) {
            Schema::table('user_coin_logs', function (Blueprint $table) {
                if ($this->hasIndex('user_coin_logs', 'idx_ucl_created_at')) {
                    $table->dropIndex('idx_ucl_created_at');
                }
                if ($this->hasIndex('user_coin_logs', 'idx_ucl_type')) {
                    $table->dropIndex('idx_ucl_type');
                }
                if ($this->hasIndex('user_coin_logs', 'idx_ucl_user_created')) {
                    $table->dropIndex('idx_ucl_user_created');
                }
                if (!$this->hasIndex('user_coin_logs', 'user_coin_logs_user_id_index')) {
                    $table->index('user_id', 'user_coin_logs_user_id_index');
                }
            });
        }

        if (Schema::hasTable('live_times')) {
            Schema::table('live_times', function (Blueprint $table) {
                if ($this->hasIndex('live_times', 'idx_lt_uid_start')) {
                    $table->dropIndex('idx_lt_uid_start');
                }
                if ($this->hasIndex('live_times', 'idx_lt_user_id')) {
                    $table->dropIndex('idx_lt_user_id');
                }
            });
        }

        if (Schema::hasTable('fair_luck_transactions')) {
            Schema::table('fair_luck_transactions', function (Blueprint $table) {
                if ($this->hasIndex('fair_luck_transactions', 'idx_flt_created_at')) {
                    $table->dropIndex('idx_flt_created_at');
                }
            });
        }

        if (Schema::hasTable('fair_luck_wallet_histories')) {
            Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
                if ($this->hasIndex('fair_luck_wallet_histories', 'idx_flwh_user_created')) {
                    $table->dropIndex('idx_flwh_user_created');
                }
                if ($this->hasIndex('fair_luck_wallet_histories', 'idx_flwh_wallet_created')) {
                    $table->dropIndex('idx_flwh_wallet_created');
                }
            });
        }

        if (Schema::hasTable('room_boom_gifts')) {
            Schema::table('room_boom_gifts', function (Blueprint $table) {
                if ($this->hasIndex('room_boom_gifts', 'idx_rbg_created_at')) {
                    $table->dropIndex('idx_rbg_created_at');
                }
            });
        }

        if (Schema::hasTable('search_histories')) {
            Schema::table('search_histories', function (Blueprint $table) {
                if ($this->hasIndex('search_histories', 'idx_sh_user_id')) {
                    $table->dropIndex('idx_sh_user_id');
                }
            });
        }

        if (Schema::hasTable('admin_operation_log')) {
            Schema::table('admin_operation_log', function (Blueprint $table) {
                if ($this->hasIndex('admin_operation_log', 'idx_aol_user_id')) {
                    $table->dropIndex('idx_aol_user_id');
                }
            });
        }

        if (Schema::hasTable('official_messages')) {
            Schema::table('official_messages', function (Blueprint $table) {
                if ($this->hasIndex('official_messages', 'idx_om_user_type_date')) {
                    $table->dropIndex('idx_om_user_type_date');
                }
            });
        }

        if (Schema::hasTable('coin_game_users')) {
            Schema::table('coin_game_users', function (Blueprint $table) {
                if (!$this->hasIndex('coin_game_users', 'idx_cgu_user_id')) {
                    $table->index('user_id', 'idx_cgu_user_id');
                }
                if (!$this->hasIndex('coin_game_users', 'idx_cgu_created_at')) {
                    $table->index('created_at', 'idx_cgu_created_at');
                }
                if (!$this->hasIndex('coin_game_users', 'idx_cgu_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_cgu_user_created');
                }
            });
        }

        if (Schema::hasTable('coin_game_users_archive')) {
            Schema::table('coin_game_users_archive', function (Blueprint $table) {
                if ($this->hasIndex('coin_game_users_archive', 'idx_cgua_type_created_coins')) {
                    $table->dropIndex('idx_cgua_type_created_coins');
                }
                if (!$this->hasIndex('coin_game_users_archive', 'idx_cgua_user_id')) {
                    $table->index('user_id', 'idx_cgua_user_id');
                }
                if (!$this->hasIndex('coin_game_users_archive', 'idx_cgua_created_at')) {
                    $table->index('created_at', 'idx_cgua_created_at');
                }
            });
        }

        if (Schema::hasTable('packs')) {
            Schema::table('packs', function (Blueprint $table) {
                if (!$this->hasIndex('packs', 'idx_type_used_expire')) {
                    $table->index(['type', 'is_used', 'expire'], 'idx_type_used_expire');
                }
                if (!$this->hasIndex('packs', 'idx_packs_type')) {
                    $table->index('type', 'idx_packs_type');
                }
                if (!$this->hasIndex('packs', 'idx_packs_is_used')) {
                    $table->index('is_used', 'idx_packs_is_used');
                }
                if (!$this->hasIndex('packs', 'idx_packs_expire')) {
                    $table->index('expire', 'idx_packs_expire');
                }
                if (!$this->hasIndex('packs', 'idx_packs_deleted_at')) {
                    $table->index('deleted_at', 'idx_packs_deleted_at');
                }
            });
        }

        if (Schema::hasTable('gift_logs')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                if (!$this->hasIndex('gift_logs', 'idx_gift_logs_receiver_id')) {
                    $table->index('receiver_id', 'idx_gift_logs_receiver_id');
                }
                if (!$this->hasIndex('gift_logs', 'idx_gift_logs_sender_id')) {
                    $table->index('sender_id', 'idx_gift_logs_sender_id');
                }
                if (!$this->hasIndex('gift_logs', 'gift_logs_roomowner_id_index')) {
                    $table->index('roomowner_id', 'gift_logs_roomowner_id_index');
                }
            });
        }
    }
};
