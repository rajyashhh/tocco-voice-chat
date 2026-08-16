<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Helper: Check if an index already exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // الجزء 1: Foreign Key Indexes
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        // ─── 1.1 جدول users ───
        // country_id: مستخدم في فلترة المستخدمين حسب الدولة
        // agency_id: مستخدم في عرض مستخدمي الوكالات
        // now_room_uid: مستخدم في معرفة الغرفة الحالية للمستخدم
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_country_id')) {
                $table->index('country_id', 'idx_users_country_id');
            }
            if (!$this->indexExists('users', 'idx_users_agency_id')) {
                $table->index('agency_id', 'idx_users_agency_id');
            }
            if (!$this->indexExists('users', 'idx_users_now_room_uid')) {
                $table->index('now_room_uid', 'idx_users_now_room_uid');
            }
        });

        // vip_id: قد لا يكون موجود كعمود مباشر في users (العلاقة عبر users_vips)
        if (Schema::hasColumn('users', 'vip_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'idx_users_vip_id')) {
                    $table->index('vip_id', 'idx_users_vip_id');
                }
            });
        }

        // ─── 1.2 جدول rooms ───
        // uid: موجود بالفعل ✓
        // country_id: إذا كان العمود موجود
        // cat_id: إذا كان العمود موجود
        if (Schema::hasColumn('rooms', 'country_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                if (!$this->indexExists('rooms', 'idx_rooms_country_id')) {
                    $table->index('country_id', 'idx_rooms_country_id');
                }
            });
        }

        if (Schema::hasColumn('rooms', 'cat_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                if (!$this->indexExists('rooms', 'idx_rooms_cat_id')) {
                    $table->index('cat_id', 'idx_rooms_cat_id');
                }
            });
        }

        // ─── 1.3 جدول gift_logs ───
        // sender_id: موجود كـ composite index (sender_id, created_at, giftPrice) ✓
        // receiver_id: موجود كـ idx_gift_logs_receiver_id ✓
        // room_id: موجود كـ composite index (room_id, created_at, giftPrice) ✓
        // gift_id (giftId): موجود بالفعل ✓
        // ─── لا حاجة لإضافة indexes جديدة على gift_logs FK columns ───

        // ─── 1.4 جدول coin_logs ───
        // user_id: غير موجود - مطلوب إضافته
        Schema::table('coin_logs', function (Blueprint $table) {
            if (!$this->indexExists('coin_logs', 'idx_coin_logs_user_id')) {
                $table->index('user_id', 'idx_coin_logs_user_id');
            }
        });

        // ─── 1.5 جدول follows ───
        // user_id: غير موجود - مطلوب إضافته
        // followed_user_id: غير موجود - مطلوب إضافته
        Schema::table('follows', function (Blueprint $table) {
            if (!$this->indexExists('follows', 'idx_follows_user_id')) {
                $table->index('user_id', 'idx_follows_user_id');
            }
            if (!$this->indexExists('follows', 'idx_follows_followed_user_id')) {
                $table->index('followed_user_id', 'idx_follows_followed_user_id');
            }
        });

        // ─── 1.6 جدول family_user ───
        // user_id: موجود بالفعل ✓
        // family_id: موجود بالفعل ✓
        // ─── لا حاجة لإضافة indexes جديدة ───

        // ─── 1.7 جدول users_joined_agencies (agency_members) ───
        // agency_id: غير موجود - مطلوب إضافته
        // user_id: غير موجود - مطلوب إضافته
        if (Schema::hasTable('users_joined_agencies')) {
            Schema::table('users_joined_agencies', function (Blueprint $table) {
                if (!$this->indexExists('users_joined_agencies', 'idx_uja_agency_id')) {
                    $table->index('agency_id', 'idx_uja_agency_id');
                }
                if (!$this->indexExists('users_joined_agencies', 'idx_uja_user_id')) {
                    $table->index('user_id', 'idx_uja_user_id');
                }
            });
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // الجزء 2: Composite Indexes للاستعلامات المتكررة
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        // ─── 2.1 gift_logs composite indexes ───
        // ✓ (sender_id, created_at, giftPrice) — موجود من migration 2025_06_19
        // ✓ (receiver_id, created_at, giftPrice) — موجود من migration 2025_06_19
        // ✓ (room_id, created_at, giftPrice) — موجود من migration 2026_04_20
        // ─── جميع composite indexes المطلوبة لـ gift_logs موجودة بالفعل ───

        // ─── 2.2 coin_logs: (user_id, created_at) لتاريخ العملات ───
        Schema::table('coin_logs', function (Blueprint $table) {
            if (!$this->indexExists('coin_logs', 'idx_coin_logs_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_coin_logs_user_created');
            }
        });

        // ─── 2.3 rooms: (country_id, is_afk, created_at) لعرض الرومات النشطة حسب الدولة ───
        if (Schema::hasColumn('rooms', 'country_id') && Schema::hasColumn('rooms', 'is_afk')) {
            Schema::table('rooms', function (Blueprint $table) {
                if (!$this->indexExists('rooms', 'idx_rooms_country_afk_created')) {
                    $table->index(['country_id', 'is_afk', 'created_at'], 'idx_rooms_country_afk_created');
                }
            });
        }

        // ─── 2.4 users: (country_id, isOnline) لعرض المستخدمين المتصلين حسب الدولة ───
        if (Schema::hasColumn('users', 'isOnline')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'idx_users_country_online')) {
                    $table->index(['country_id', 'isOnline'], 'idx_users_country_online');
                }
            });
        }

        // ─── 2.5 users_joined_agencies: (agency_id, user_id) للبحث السريع ───
        if (Schema::hasTable('users_joined_agencies')) {
            Schema::table('users_joined_agencies', function (Blueprint $table) {
                if (!$this->indexExists('users_joined_agencies', 'idx_uja_agency_user')) {
                    $table->index(['agency_id', 'user_id'], 'idx_uja_agency_user');
                }
            });
        }

        // ─── 2.6 follows: (user_id, followed_user_id) unique composite للمتابعات ───
        Schema::table('follows', function (Blueprint $table) {
            if (!$this->indexExists('follows', 'idx_follows_user_followed')) {
                $table->index(['user_id', 'followed_user_id'], 'idx_follows_user_followed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ─── إزالة Foreign Key Indexes ───

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'idx_users_country_id')) {
                $table->dropIndex('idx_users_country_id');
            }
            if ($this->indexExists('users', 'idx_users_agency_id')) {
                $table->dropIndex('idx_users_agency_id');
            }
            if ($this->indexExists('users', 'idx_users_now_room_uid')) {
                $table->dropIndex('idx_users_now_room_uid');
            }
            if ($this->indexExists('users', 'idx_users_vip_id')) {
                $table->dropIndex('idx_users_vip_id');
            }
            if ($this->indexExists('users', 'idx_users_country_online')) {
                $table->dropIndex('idx_users_country_online');
            }
        });

        if (Schema::hasTable('rooms')) {
            Schema::table('rooms', function (Blueprint $table) {
                if ($this->indexExists('rooms', 'idx_rooms_country_id')) {
                    $table->dropIndex('idx_rooms_country_id');
                }
                if ($this->indexExists('rooms', 'idx_rooms_cat_id')) {
                    $table->dropIndex('idx_rooms_cat_id');
                }
                if ($this->indexExists('rooms', 'idx_rooms_country_afk_created')) {
                    $table->dropIndex('idx_rooms_country_afk_created');
                }
            });
        }

        Schema::table('coin_logs', function (Blueprint $table) {
            if ($this->indexExists('coin_logs', 'idx_coin_logs_user_id')) {
                $table->dropIndex('idx_coin_logs_user_id');
            }
            if ($this->indexExists('coin_logs', 'idx_coin_logs_user_created')) {
                $table->dropIndex('idx_coin_logs_user_created');
            }
        });

        Schema::table('follows', function (Blueprint $table) {
            if ($this->indexExists('follows', 'idx_follows_user_id')) {
                $table->dropIndex('idx_follows_user_id');
            }
            if ($this->indexExists('follows', 'idx_follows_followed_user_id')) {
                $table->dropIndex('idx_follows_followed_user_id');
            }
            if ($this->indexExists('follows', 'idx_follows_user_followed')) {
                $table->dropIndex('idx_follows_user_followed');
            }
        });

        if (Schema::hasTable('users_joined_agencies')) {
            Schema::table('users_joined_agencies', function (Blueprint $table) {
                if ($this->indexExists('users_joined_agencies', 'idx_uja_agency_id')) {
                    $table->dropIndex('idx_uja_agency_id');
                }
                if ($this->indexExists('users_joined_agencies', 'idx_uja_user_id')) {
                    $table->dropIndex('idx_uja_user_id');
                }
                if ($this->indexExists('users_joined_agencies', 'idx_uja_agency_user')) {
                    $table->dropIndex('idx_uja_agency_user');
                }
            });
        }
    }
};
