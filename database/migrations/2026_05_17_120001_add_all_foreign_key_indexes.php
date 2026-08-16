<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add indexes on ALL Foreign Keys + Composite Indexes for all tables
 * Target: 40-60% query performance improvement
 *
 * IMPORTANT:
 *   1. Backup database before running
 *   2. Test in staging first
 *   3. Measure performance before/after with EXPLAIN
 */
return new class extends Migration
{
    /**
     * Safely add index only if table, columns exist and index doesn't
     */
    private function addIndexSafe(string $table, string|array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) return;

        $colsToCheck = is_array($columns) ? $columns : [$columns];
        foreach ($colsToCheck as $col) {
            if (!Schema::hasColumn($table, $col)) return;
        }

        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (count($exists) > 0) return;

        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    /**
     * Safely drop index only if it exists
     */
    private function dropIndexSafe(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) return;

        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (count($exists) === 0) return;

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropIndex($indexName);
        });
    }

    public function up(): void
    {
        // ============================
        //  1. users
        // ============================
        $this->addIndexSafe('users', 'country_id', 'idx_users_country_id');
        $this->addIndexSafe('users', 'agency_id', 'idx_users_agency_id');
        $this->addIndexSafe('users', 'now_room_uid', 'idx_users_now_room_uid');
        $this->addIndexSafe('users', 'vip_id', 'idx_users_vip_id');
        $this->addIndexSafe('users', 'family_id', 'idx_users_family_id');
        $this->addIndexSafe('users', 'image_color_id', 'idx_users_image_color_id');
        $this->addIndexSafe('users', 'game_id', 'idx_users_game_id');
        $this->addIndexSafe('users', 'dashboard_manager_id', 'idx_users_dashboard_manager_id');

        // ============================
        //  2. rooms (uid already indexed)
        // ============================
        $this->addIndexSafe('rooms', 'country_id', 'idx_rooms_country_id');
        $this->addIndexSafe('rooms', 'cat_id', 'idx_rooms_cat_id');
        $this->addIndexSafe('rooms', 'owner_id', 'idx_rooms_owner_id');
        $this->addIndexSafe('rooms', 'level_id', 'idx_rooms_level_id');
        $this->addIndexSafe('rooms', 'agency_manger_id', 'idx_rooms_agency_manger_id');

        // ============================
        //  3. gift_logs (sender_id, receiver_id, room_id, agency_id, giftId already covered)
        // ============================
        $this->addIndexSafe('gift_logs', 'sender_family_id', 'idx_gift_logs_sender_family_id');
        $this->addIndexSafe('gift_logs', 'receiver_family_id', 'idx_gift_logs_receiver_family_id');
        $this->addIndexSafe('gift_logs', 'union_id', 'idx_gift_logs_union_id');

        // ============================
        //  4. coin_logs
        // ============================
        $this->addIndexSafe('coin_logs', 'user_id', 'idx_coin_logs_user_id');
        $this->addIndexSafe('coin_logs', 'donor_id', 'idx_coin_logs_donor_id');
        $this->addIndexSafe('coin_logs', 'coin_id', 'idx_coin_logs_coin_id');

        // ============================
        //  5. follows
        // ============================
        $this->addIndexSafe('follows', 'user_id', 'idx_follows_user_id');
        $this->addIndexSafe('follows', 'followed_user_id', 'idx_follows_followed_user_id');

        // ============================
        //  6. users_joined_agencies
        // ============================
        $this->addIndexSafe('users_joined_agencies', 'agency_id', 'idx_uja_agency_id');
        $this->addIndexSafe('users_joined_agencies', 'user_id', 'idx_uja_user_id');

        // ============================
        //  7. agencies
        // ============================
        $this->addIndexSafe('agencies', 'owner_id', 'idx_agencies_owner_id');
        $this->addIndexSafe('agencies', 'app_owner_id', 'idx_agencies_app_owner_id');
        $this->addIndexSafe('agencies', 'bd_id', 'idx_agencies_bd_id');

        // ============================
        //  8. charges
        // ============================
        $this->addIndexSafe('charges', 'charger_id', 'idx_charges_charger_id');
        $this->addIndexSafe('charges', 'user_id', 'idx_charges_user_id');
        $this->addIndexSafe('charges', 'agency_id', 'idx_charges_agency_id');
        $this->addIndexSafe('charges', 'action_user_id', 'idx_charges_action_user_id');

        // ============================
        //  9. user_sallaries
        // ============================
        $this->addIndexSafe('user_sallaries', 'user_id', 'idx_user_sallaries_user_id');
        $this->addIndexSafe('user_sallaries', 'user_agency_id', 'idx_user_sallaries_user_agency_id');

        // ============================
        //  10. agency_sallaries
        // ============================
        $this->addIndexSafe('agency_sallaries', 'agency_id', 'idx_agency_sallaries_agency_id');

        // ============================
        //  11. agency_join_requests
        // ============================
        $this->addIndexSafe('agency_join_requests', 'user_id', 'idx_ajr_user_id');
        $this->addIndexSafe('agency_join_requests', 'agency_id', 'idx_ajr_agency_id');
        $this->addIndexSafe('agency_join_requests', 'change_status_admin_id', 'idx_ajr_admin_id');

        // ============================
        //  12. bans
        // ============================
        $this->addIndexSafe('bans', 'staff_id', 'idx_bans_staff_id');
        $this->addIndexSafe('bans', 'ban_type_id', 'idx_bans_ban_type_id');

        // ============================
        //  13. ban_rooms
        // ============================
        $this->addIndexSafe('ban_rooms', 'room_id', 'idx_ban_rooms_room_id');
        $this->addIndexSafe('ban_rooms', 'staff_id', 'idx_ban_rooms_staff_id');

        // ============================
        //  14. user_target
        // ============================
        $this->addIndexSafe('user_target', 'user_id', 'idx_user_target_user_id');
        $this->addIndexSafe('user_target', 'agency_id', 'idx_user_target_agency_id');
        $this->addIndexSafe('user_target', 'target_id', 'idx_user_target_target_id');

        // ============================
        //  15. packs (user_id, type already indexed)
        // ============================
        $this->addIndexSafe('packs', 'target_id', 'idx_packs_target_id');
        $this->addIndexSafe('packs', 'sender_id', 'idx_packs_sender_id');
        $this->addIndexSafe('packs', 'vip_user_id', 'idx_packs_vip_user_id');
        $this->addIndexSafe('packs', 'dash_user_id', 'idx_packs_dash_user_id');

        // ============================
        //  16. pack_logs
        // ============================
        $this->addIndexSafe('pack_logs', 'user_id', 'idx_pack_logs_user_id');
        $this->addIndexSafe('pack_logs', 'target_id', 'idx_pack_logs_target_id');

        // ============================
        //  17. pks
        // ============================
        $this->addIndexSafe('pks', 'room_id', 'idx_pks_room_id');

        // ============================
        //  18. box_uses
        // ============================
        $this->addIndexSafe('box_uses', 'box_id', 'idx_box_uses_box_id');
        $this->addIndexSafe('box_uses', 'user_id', 'idx_box_uses_user_id');
        $this->addIndexSafe('box_uses', 'room_id', 'idx_box_uses_room_id');

        // ============================
        //  19. user_box_gifts
        // ============================
        $this->addIndexSafe('user_box_gifts', 'box_uses_id', 'idx_ubg_box_uses_id');
        $this->addIndexSafe('user_box_gifts', 'user_id', 'idx_ubg_user_id');
        $this->addIndexSafe('user_box_gifts', 'room_id', 'idx_ubg_room_id');

        // ============================
        //  20. histories
        // ============================
        $this->addIndexSafe('histories', 'user_id', 'idx_histories_user_id');
        $this->addIndexSafe('histories', 'agency_id', 'idx_histories_agency_id');

        // ============================
        //  21. user_totals
        // ============================
        $this->addIndexSafe('user_totals', 'user_id', 'idx_user_totals_user_id');

        // ============================
        //  22. black_lists
        // ============================
        $this->addIndexSafe('black_lists', 'user_id', 'idx_black_lists_user_id');
        $this->addIndexSafe('black_lists', 'from_uid', 'idx_black_lists_from_uid');

        // ============================
        //  23. mics
        // ============================
        $this->addIndexSafe('mics', 'roomowner_id', 'idx_mics_roomowner_id');
        $this->addIndexSafe('mics', 'user_id', 'idx_mics_user_id');

        // ============================
        //  24. user_coupons
        // ============================
        $this->addIndexSafe('user_coupons', 'user_id', 'idx_user_coupons_user_id');
        $this->addIndexSafe('user_coupons', 'ware_id', 'idx_user_coupons_ware_id');

        // ============================
        //  25. official_messages
        // ============================
        $this->addIndexSafe('official_messages', 'user_id', 'idx_official_messages_user_id');

        // ============================
        //  26. user_tasks
        // ============================
        $this->addIndexSafe('user_tasks', 'user_id', 'idx_user_tasks_user_id');

        // ============================
        //  27. user_unions (union_id already indexed)
        // ============================
        $this->addIndexSafe('user_unions', 'user_id', 'idx_user_unions_user_id');

        // ============================
        //  28. cps
        // ============================
        $this->addIndexSafe('cps', 'user_id', 'idx_cps_user_id');
        $this->addIndexSafe('cps', 'fromUid', 'idx_cps_from_uid');

        // ============================
        //  29. leaders
        // ============================
        $this->addIndexSafe('leaders', 'user_id', 'idx_leaders_user_id');

        // ============================
        //  30. off_reads
        // ============================
        $this->addIndexSafe('off_reads', 'user_id', 'idx_off_reads_user_id');
        $this->addIndexSafe('off_reads', 'off_id', 'idx_off_reads_off_id');

        // ============================
        //  31. profile_visitors
        // ============================
        $this->addIndexSafe('profile_visitors', 'user_id', 'idx_profile_visitors_user_id');
        $this->addIndexSafe('profile_visitors', 'visitor_id', 'idx_profile_visitors_visitor_id');

        // ============================
        //  32. search_histories
        // ============================
        $this->addIndexSafe('search_histories', 'user_id', 'idx_search_histories_user_id');

        // ============================
        //  33. silver_histories
        // ============================
        $this->addIndexSafe('silver_histories', 'user_id', 'idx_silver_histories_user_id');

        // ============================
        //  34. exchange_logs
        // ============================
        $this->addIndexSafe('exchange_logs', 'user_id', 'idx_exchange_logs_user_id');

        // ============================
        //  35. salary_trxs
        // ============================
        $this->addIndexSafe('salary_trxs', 'payer_id', 'idx_salary_trxs_payer_id');

        // ============================
        //  36. comments
        // ============================
        $this->addIndexSafe('comments', 'author_id', 'idx_comments_author_id');
        $this->addIndexSafe('comments', 'commentable_id', 'idx_comments_commentable_id');

        // ============================
        //  37. likes
        // ============================
        $this->addIndexSafe('likes', 'author_id', 'idx_likes_author_id');
        $this->addIndexSafe('likes', 'likeable_id', 'idx_likes_likeable_id');

        // ============================
        //  38. shares
        // ============================
        $this->addIndexSafe('shares', 'user_id', 'idx_shares_user_id');
        $this->addIndexSafe('shares', 'shareable_id', 'idx_shares_shareable_id');

        // ============================
        //  39. videos
        // ============================
        $this->addIndexSafe('videos', 'author_id', 'idx_videos_author_id');

        // ============================
        //  40. taggables
        // ============================
        $this->addIndexSafe('taggables', 'tag_id', 'idx_taggables_tag_id');
        $this->addIndexSafe('taggables', 'taggable_id', 'idx_taggables_taggable_id');

        // ============================
        //  41. user_level_logs
        // ============================
        $this->addIndexSafe('user_level_logs', 'user_id', 'idx_user_level_logs_user_id');

        // ============================
        //  42. conversations
        // ============================
        $this->addIndexSafe('conversations', 'first_user_id', 'idx_conversations_first_user_id');
        $this->addIndexSafe('conversations', 'second_user_id', 'idx_conversations_second_user_id');

        // ============================
        //  43. messages
        // ============================
        $this->addIndexSafe('messages', 'conversation_id', 'idx_messages_conversation_id');
        $this->addIndexSafe('messages', 'user_id', 'idx_messages_user_id');

        // ============================
        //  44. files
        // ============================
        $this->addIndexSafe('files', 'conversation_id', 'idx_files_conversation_id');
        $this->addIndexSafe('files', 'message_id', 'idx_files_message_id');
        $this->addIndexSafe('files', 'user_id', 'idx_files_user_id');

        // ============================
        //  45. tickets
        // ============================
        $this->addIndexSafe('tickets', 'admin_id', 'idx_tickets_admin_id');

        // ============================
        //  46. recharge_requests
        // ============================
        $this->addIndexSafe('recharge_requests', 'user_id', 'idx_recharge_requests_user_id');
        $this->addIndexSafe('recharge_requests', 'charger_id', 'idx_recharge_requests_charger_id');

        // ============================
        //  47. home_carousels
        // ============================
        $this->addIndexSafe('home_carousels', 'owner_id', 'idx_home_carousels_owner_id');

        // ============================
        //  48. images
        // ============================
        $this->addIndexSafe('images', 'user_id', 'idx_images_user_id');

        // ============================
        //  49. change_level_histories
        // ============================
        $this->addIndexSafe('change_level_histories', 'user_id', 'idx_clh_user_id');
        $this->addIndexSafe('change_level_histories', 'admin_id', 'idx_clh_admin_id');

        // ============================
        //  50. remaining_diamonds
        // ============================
        $this->addIndexSafe('remaining_diamonds', 'user_id', 'idx_remaining_diamonds_user_id');

        // ============================
        //  51. room_visitors
        // ============================
        $this->addIndexSafe('room_visitors', 'user_id', 'idx_room_visitors_user_id');
        $this->addIndexSafe('room_visitors', 'room_id', 'idx_room_visitors_room_id');

        // ============================
        //  52. entered_rooms
        // ============================
        $this->addIndexSafe('entered_rooms', 'user_id', 'idx_entered_rooms_user_id');
        $this->addIndexSafe('entered_rooms', 'room_id', 'idx_entered_rooms_room_id');

        // ============================
        //  53. charge_invoices
        // ============================
        $this->addIndexSafe('charge_invoices', 'charge_id', 'idx_charge_invoices_charge_id');
        $this->addIndexSafe('charge_invoices', 'user_id', 'idx_charge_invoices_user_id');

        // ============================
        //  54. return_charges
        // ============================
        $this->addIndexSafe('return_charges', 'charge_id', 'idx_return_charges_charge_id');
        $this->addIndexSafe('return_charges', 'charger_id', 'idx_return_charges_charger_id');
        $this->addIndexSafe('return_charges', 'receiver_id', 'idx_return_charges_receiver_id');

        // ============================
        //  55. bd_agency_host_sallaries
        // ============================
        $this->addIndexSafe('bd_agency_host_sallaries', 'bd_id', 'idx_bahs_bd_id');
        $this->addIndexSafe('bd_agency_host_sallaries', 'user_id', 'idx_bahs_user_id');
        $this->addIndexSafe('bd_agency_host_sallaries', 'agency_id', 'idx_bahs_agency_id');

        // ============================
        //  56. bd_salaries
        // ============================
        $this->addIndexSafe('bd_salaries', 'bd_id', 'idx_bd_salaries_bd_id');
        $this->addIndexSafe('bd_salaries', 'bd_user_id', 'idx_bd_salaries_bd_user_id');

        // ============================
        //  57. usd_transfers
        // ============================
        $this->addIndexSafe('usd_transfers', 'user_id', 'idx_usd_transfers_user_id');
        $this->addIndexSafe('usd_transfers', 'agency_id', 'idx_usd_transfers_agency_id');

        // ============================
        //  58. charge_winners
        // ============================
        $this->addIndexSafe('charge_winners', 'user_id', 'idx_charge_winners_user_id');

        // ============================
        //  59. nowpayments_orders
        // ============================
        $this->addIndexSafe('nowpayments_orders', 'user_id', 'idx_nowpayments_orders_user_id');

        // ============================
        //  60. agency_packs
        // ============================
        $this->addIndexSafe('agency_packs', 'agency_id', 'idx_agency_packs_agency_id');

        // ============================
        //  61. play_num_logs
        // ============================
        $this->addIndexSafe('play_num_logs', 'user_id', 'idx_play_num_logs_user_id');

        // ============================
        //  62. custom_zego_messages
        // ============================
        $this->addIndexSafe('custom_zego_messages', 'room_id', 'idx_czm_room_id');

        // ============================
        //  63. room_microphones
        // ============================
        $this->addIndexSafe('room_microphones', 'room_id', 'idx_room_microphones_room_id');
        $this->addIndexSafe('room_microphones', 'user_id', 'idx_room_microphones_user_id');

        // ============================
        //  64. kick_records
        // ============================
        $this->addIndexSafe('kick_records', 'user_id', 'idx_kick_records_user_id');
        $this->addIndexSafe('kick_records', 'kicked_user_id', 'idx_kick_records_kicked_user_id');
        $this->addIndexSafe('kick_records', 'room_id', 'idx_kick_records_room_id');

        // ============================
        //  65. music
        // ============================
        $this->addIndexSafe('music', 'user_id', 'idx_music_user_id');

        // ============================
        //  66. group_chat (user_id already indexed)
        // ============================
        $this->addIndexSafe('group_chat', 'parent_id', 'idx_group_chat_parent_id');

        // ============================
        //  67. bd_sallaries
        // ============================
        $this->addIndexSafe('bd_sallaries', 'bd_id', 'idx_bd_sallaries_bd_id');
        $this->addIndexSafe('bd_sallaries', 'agency_id', 'idx_bd_sallaries_agency_id');

        // ============================
        //  68. reward_winner_games
        // ============================
        $this->addIndexSafe('reward_winner_games', 'user_id', 'idx_rwg_user_id');

        // ============================
        //  COMPOSITE INDEXES
        // ============================

        // coin_logs: (user_id, created_at)
        $this->addIndexSafe('coin_logs', ['user_id', 'created_at'], 'idx_coin_logs_user_created');

        // rooms: (country_id, is_afk, created_at)
        $this->addIndexSafe('rooms', ['country_id', 'is_afk', 'created_at'], 'idx_rooms_country_afk_created');

        // users: (country_id, isOnline)
        $this->addIndexSafe('users', ['country_id', 'isOnline'], 'idx_users_country_online');

        // users_joined_agencies: (agency_id, user_id)
        $this->addIndexSafe('users_joined_agencies', ['agency_id', 'user_id'], 'idx_uja_agency_user');

        // follows: (user_id, followed_user_id)
        $this->addIndexSafe('follows', ['user_id', 'followed_user_id'], 'idx_follows_user_followed');

        // user_sallaries: (user_id, is_paid)
        $this->addIndexSafe('user_sallaries', ['user_id', 'is_paid'], 'idx_user_sallaries_user_paid');

        // charges: (charger_id, created_at)
        $this->addIndexSafe('charges', ['charger_id', 'created_at'], 'idx_charges_charger_created');

        // bans: (user_id, created_at)
        $this->addIndexSafe('bans', ['user_id', 'created_at'], 'idx_bans_user_created');

        // agency_join_requests: (agency_id, status)
        $this->addIndexSafe('agency_join_requests', ['agency_id', 'status'], 'idx_ajr_agency_status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'users' => [
                'idx_users_country_id', 'idx_users_agency_id', 'idx_users_now_room_uid',
                'idx_users_vip_id', 'idx_users_family_id', 'idx_users_image_color_id',
                'idx_users_game_id', 'idx_users_dashboard_manager_id', 'idx_users_country_online',
            ],
            'rooms' => [
                'idx_rooms_country_id', 'idx_rooms_cat_id', 'idx_rooms_owner_id',
                'idx_rooms_level_id', 'idx_rooms_agency_manger_id', 'idx_rooms_country_afk_created',
            ],
            'gift_logs' => [
                'idx_gift_logs_sender_family_id', 'idx_gift_logs_receiver_family_id', 'idx_gift_logs_union_id',
            ],
            'coin_logs' => [
                'idx_coin_logs_user_id', 'idx_coin_logs_donor_id', 'idx_coin_logs_coin_id', 'idx_coin_logs_user_created',
            ],
            'follows' => [
                'idx_follows_user_id', 'idx_follows_followed_user_id', 'idx_follows_user_followed',
            ],
            'users_joined_agencies' => [
                'idx_uja_agency_id', 'idx_uja_user_id', 'idx_uja_agency_user',
            ],
            'agencies' => [
                'idx_agencies_owner_id', 'idx_agencies_app_owner_id', 'idx_agencies_bd_id',
            ],
            'charges' => [
                'idx_charges_charger_id', 'idx_charges_user_id', 'idx_charges_agency_id',
                'idx_charges_action_user_id', 'idx_charges_charger_created',
            ],
            'user_sallaries' => [
                'idx_user_sallaries_user_id', 'idx_user_sallaries_user_agency_id', 'idx_user_sallaries_user_paid',
            ],
            'agency_sallaries' => ['idx_agency_sallaries_agency_id'],
            'agency_join_requests' => [
                'idx_ajr_user_id', 'idx_ajr_agency_id', 'idx_ajr_admin_id', 'idx_ajr_agency_status',
            ],
            'bans' => ['idx_bans_staff_id', 'idx_bans_ban_type_id', 'idx_bans_user_created'],
            'ban_rooms' => ['idx_ban_rooms_room_id', 'idx_ban_rooms_staff_id'],
            'user_target' => ['idx_user_target_user_id', 'idx_user_target_agency_id', 'idx_user_target_target_id'],
            'packs' => ['idx_packs_target_id', 'idx_packs_sender_id', 'idx_packs_vip_user_id', 'idx_packs_dash_user_id'],
            'pack_logs' => ['idx_pack_logs_user_id', 'idx_pack_logs_target_id'],
            'pks' => ['idx_pks_room_id'],
            'box_uses' => ['idx_box_uses_box_id', 'idx_box_uses_user_id', 'idx_box_uses_room_id'],
            'user_box_gifts' => ['idx_ubg_box_uses_id', 'idx_ubg_user_id', 'idx_ubg_room_id'],
            'histories' => ['idx_histories_user_id', 'idx_histories_agency_id'],
            'user_totals' => ['idx_user_totals_user_id'],
            'black_lists' => ['idx_black_lists_user_id', 'idx_black_lists_from_uid'],
            'mics' => ['idx_mics_roomowner_id', 'idx_mics_user_id'],
            'user_coupons' => ['idx_user_coupons_user_id', 'idx_user_coupons_ware_id'],
            'official_messages' => ['idx_official_messages_user_id'],
            'user_tasks' => ['idx_user_tasks_user_id'],
            'user_unions' => ['idx_user_unions_user_id'],
            'cps' => ['idx_cps_user_id', 'idx_cps_from_uid'],
            'leaders' => ['idx_leaders_user_id'],
            'off_reads' => ['idx_off_reads_user_id', 'idx_off_reads_off_id'],
            'profile_visitors' => ['idx_profile_visitors_user_id', 'idx_profile_visitors_visitor_id'],
            'search_histories' => ['idx_search_histories_user_id'],
            'silver_histories' => ['idx_silver_histories_user_id'],
            'exchange_logs' => ['idx_exchange_logs_user_id'],
            'salary_trxs' => ['idx_salary_trxs_payer_id'],
            'comments' => ['idx_comments_author_id', 'idx_comments_commentable_id'],
            'likes' => ['idx_likes_author_id', 'idx_likes_likeable_id'],
            'shares' => ['idx_shares_user_id', 'idx_shares_shareable_id'],
            'videos' => ['idx_videos_author_id'],
            'taggables' => ['idx_taggables_tag_id', 'idx_taggables_taggable_id'],
            'user_level_logs' => ['idx_user_level_logs_user_id'],
            'conversations' => ['idx_conversations_first_user_id', 'idx_conversations_second_user_id'],
            'messages' => ['idx_messages_conversation_id', 'idx_messages_user_id'],
            'files' => ['idx_files_conversation_id', 'idx_files_message_id', 'idx_files_user_id'],
            'tickets' => ['idx_tickets_admin_id'],
            'recharge_requests' => ['idx_recharge_requests_user_id', 'idx_recharge_requests_charger_id'],
            'home_carousels' => ['idx_home_carousels_owner_id'],
            'images' => ['idx_images_user_id'],
            'change_level_histories' => ['idx_clh_user_id', 'idx_clh_admin_id'],
            'remaining_diamonds' => ['idx_remaining_diamonds_user_id'],
            'room_visitors' => ['idx_room_visitors_user_id', 'idx_room_visitors_room_id'],
            'entered_rooms' => ['idx_entered_rooms_user_id', 'idx_entered_rooms_room_id'],
            'charge_invoices' => ['idx_charge_invoices_charge_id', 'idx_charge_invoices_user_id'],
            'return_charges' => ['idx_return_charges_charge_id', 'idx_return_charges_charger_id', 'idx_return_charges_receiver_id'],
            'bd_agency_host_sallaries' => ['idx_bahs_bd_id', 'idx_bahs_user_id', 'idx_bahs_agency_id'],
            'bd_salaries' => ['idx_bd_salaries_bd_id', 'idx_bd_salaries_bd_user_id'],
            'usd_transfers' => ['idx_usd_transfers_user_id', 'idx_usd_transfers_agency_id'],
            'charge_winners' => ['idx_charge_winners_user_id'],
            'nowpayments_orders' => ['idx_nowpayments_orders_user_id'],
            'agency_packs' => ['idx_agency_packs_agency_id'],
            'play_num_logs' => ['idx_play_num_logs_user_id'],
            'custom_zego_messages' => ['idx_czm_room_id'],
            'room_microphones' => ['idx_room_microphones_room_id', 'idx_room_microphones_user_id'],
            'kick_records' => ['idx_kick_records_user_id', 'idx_kick_records_kicked_user_id', 'idx_kick_records_room_id'],
            'music' => ['idx_music_user_id'],
            'group_chat' => ['idx_group_chat_parent_id'],
            'bd_sallaries' => ['idx_bd_sallaries_bd_id', 'idx_bd_sallaries_agency_id'],
            'reward_winner_games' => ['idx_rwg_user_id'],
        ];

        foreach ($indexes as $table => $indexNames) {
            foreach ($indexNames as $indexName) {
                $this->dropIndexSafe($table, $indexName);
            }
        }
    }
};
