-- ============================================================================
-- Critical Performance Fix — 2026-05-10
-- ============================================================================
-- Root Cause Analysis (RCA):
--   20+ stuck queries on live_times table (each running 1+ hour)
--   61-second COUNT query on user_official_messages
--   Full table scans on user_diamond_logs
--
-- Run this BEFORE deploying the Laravel migration if you want immediate relief.
-- Safe to run during live traffic — tables are under 300K rows.
-- ============================================================================

-- 1. live_times — MOST CRITICAL
-- Fixes: 20+ stuck ranking queries (correlated subquery doing full scan per user)
-- Expected: ranking query from 1+ hour → under 1 second
CREATE INDEX idx_live_times_uid_start
    ON live_times(uid, start_time, hours);

-- 2. user_official_messages — fixes 61-second COUNT query
-- The query does EXISTS subquery joining on official_message_id + user_id
CREATE INDEX idx_uom_message_user
    ON user_official_messages(official_message_id, user_id);

-- 3. user_diamond_logs — prevents full table scan (148K rows, 0 indexes)
CREATE INDEX idx_udl_user_created
    ON user_diamond_logs(user_id, created_at);


-- ============================================================================
-- RECOMMENDED MySQL / Cloud SQL Flag Changes (DevOps)
-- ============================================================================
-- These prevent future runaway queries and reduce resource waste:
--
-- SET GLOBAL max_execution_time = 30000;       -- 30s max per query (was unlimited!)
-- SET GLOBAL wait_timeout = 300;               -- 5min idle timeout (was 8 hours)
-- SET GLOBAL interactive_timeout = 600;        -- 10min interactive timeout
-- SET GLOBAL max_connections = 300;             -- Was 4030, only 81 ever used
-- SET GLOBAL innodb_buffer_pool_size = 3221225472;  -- 3GB (was 1.5GB, 99% full)
-- SET GLOBAL performance_schema = ON;          -- Enable for monitoring
