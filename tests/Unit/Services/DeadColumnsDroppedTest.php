<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins migration 2026_06_12_150000_drop_dead_columns_from_users_rooms_profiles:
 * the audited dead columns are gone after the full migration chain, while the
 * live counter columns survive. Catches any future migration that re-adds a
 * dead column or a code path that re-introduces one via hasColumn guards.
 */
class DeadColumnsDroppedTest extends TestCase
{
    use RefreshDatabase;

    private const DROPPED_USERS_COLUMNS = [
        'locktime', 'cp_card', 'keys_num', 'idno', 'img_2', 'img_3', 'login_ip',
        'is_idcard', 'dashboard_manager_id', 'reel_following_type', 'isOnline',
    ];

    private const DROPPED_ROOMS_COLUMNS = [
        'commission_proportion', 'fresh_time', 'start_hour', 'end_hour', 'week_star',
        'super_uid', 'visitor_count', 'no_of_members', 'openid', 'image_size',
    ];

    public function test_dead_users_columns_are_dropped(): void
    {
        foreach (self::DROPPED_USERS_COLUMNS as $column) {
            $this->assertFalse(
                Schema::hasColumn('users', $column),
                "users.{$column} is audited dead and must stay dropped."
            );
        }
    }

    public function test_dead_rooms_and_profiles_columns_are_dropped(): void
    {
        foreach (self::DROPPED_ROOMS_COLUMNS as $column) {
            $this->assertFalse(
                Schema::hasColumn('rooms', $column),
                "rooms.{$column} is audited dead and must stay dropped."
            );
        }

        $this->assertFalse(Schema::hasColumn('profiles', 'image_size'));
    }

    public function test_live_follow_counter_columns_survive(): void
    {
        foreach (['number_of_fans', 'number_of_followings', 'number_of_friends'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }
    }
}
