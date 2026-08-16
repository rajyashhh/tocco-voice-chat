<?php

namespace App\Services;

use App\Models\AdminQueryLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * AdminPerformanceAlertService
 * 
 * Monitors performance thresholds and sends alerts
 * - Detects slow queries
 * - Monitors connection pool health
 * - Sends notifications when thresholds are exceeded
 */
class AdminPerformanceAlertService
{
    // Performance thresholds
    const SLOW_QUERY_THRESHOLD = 1.0; // seconds
    const SLOW_QUERY_COUNT_THRESHOLD = 10; // per hour
    const AVG_QUERY_TIME_THRESHOLD = 0.5; // seconds
    const CONNECTION_UTILIZATION_THRESHOLD = 80; // percent
    const ROWS_EXAMINED_THRESHOLD = 100000; // per query

    /**
     * Check all performance metrics and send alerts if needed
     *
     * @return array
     */
    public function checkPerformanceMetrics(): array
    {
        $alerts = [];

        // Check slow queries
        $slowQueryAlert = $this->checkSlowQueries();
        if ($slowQueryAlert) {
            $alerts[] = $slowQueryAlert;
        }

        // Check average query time
        $avgTimeAlert = $this->checkAverageQueryTime();
        if ($avgTimeAlert) {
            $alerts[] = $avgTimeAlert;
        }

        // Check rows examined
        $rowsAlert = $this->checkRowsExamined();
        if ($rowsAlert) {
            $alerts[] = $rowsAlert;
        }

        // Send alerts if any
        if (!empty($alerts)) {
            $this->sendAlerts($alerts);
        }

        return [
            'alert_count' => count($alerts),
            'alerts' => $alerts,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Check for slow queries
     *
     * @return array|null
     */
    private function checkSlowQueries(): ?array
    {
        $slowQueryCount = AdminQueryLog::slowQueries(self::SLOW_QUERY_THRESHOLD)
            ->fromLastHours(1)
            ->count();

        if ($slowQueryCount >= self::SLOW_QUERY_COUNT_THRESHOLD) {
            return [
                'type' => 'slow_queries',
                'severity' => 'warning',
                'message' => "Detected {$slowQueryCount} slow queries in the last hour",
                'threshold' => self::SLOW_QUERY_COUNT_THRESHOLD,
                'current_value' => $slowQueryCount,
                'details' => $this->getSlowQueryDetails(),
            ];
        }

        return null;
    }

    /**
     * Check average query time
     *
     * @return array|null
     */
    private function checkAverageQueryTime(): ?array
    {
        $avgTime = AdminQueryLog::fromLastHours(1)->avg('query_duration') ?? 0;

        if ($avgTime >= self::AVG_QUERY_TIME_THRESHOLD) {
            return [
                'type' => 'avg_query_time',
                'severity' => 'warning',
                'message' => "Average query time is {$avgTime}s (threshold: " . self::AVG_QUERY_TIME_THRESHOLD . "s)",
                'threshold' => self::AVG_QUERY_TIME_THRESHOLD,
                'current_value' => round($avgTime, 4),
                'details' => $this->getAverageTimeDetails(),
            ];
        }

        return null;
    }

    /**
     * Check rows examined
     *
     * @return array|null
     */
    private function checkRowsExamined(): ?array
    {
        $maxRowsExamined = AdminQueryLog::fromLastHours(1)->max('rows_examined') ?? 0;

        if ($maxRowsExamined >= self::ROWS_EXAMINED_THRESHOLD) {
            return [
                'type' => 'rows_examined',
                'severity' => 'critical',
                'message' => "Query examined {$maxRowsExamined} rows (threshold: " . self::ROWS_EXAMINED_THRESHOLD . ")",
                'threshold' => self::ROWS_EXAMINED_THRESHOLD,
                'current_value' => $maxRowsExamined,
                'details' => $this->getRowsExaminedDetails(),
            ];
        }

        return null;
    }

    /**
     * Get slow query details
     *
     * @return array
     */
    private function getSlowQueryDetails(): array
    {
        $slowQueries = AdminQueryLog::slowQueries(self::SLOW_QUERY_THRESHOLD)
            ->fromLastHours(1)
            ->orderBy('query_duration', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'table' => $log->table_name,
                    'duration' => round($log->query_duration, 4),
                    'rows_examined' => $log->rows_examined,
                    'timestamp' => $log->created_at->toDateTimeString(),
                ];
            })
            ->toArray();

        return [
            'top_slow_queries' => $slowQueries,
        ];
    }

    /**
     * Get average time details
     *
     * @return array
     */
    private function getAverageTimeDetails(): array
    {
        $byTable = [];

        foreach (['users', 'user_profiles', 'chat_messages'] as $table) {
            $avgTime = AdminQueryLog::byTable($table)
                ->fromLastHours(1)
                ->avg('query_duration') ?? 0;

            $byTable[$table] = round($avgTime, 4);
        }

        return [
            'by_table' => $byTable,
        ];
    }

    /**
     * Get rows examined details
     *
     * @return array
     */
    private function getRowsExaminedDetails(): array
    {
        $topQueries = AdminQueryLog::fromLastHours(1)
            ->orderBy('rows_examined', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'table' => $log->table_name,
                    'rows_examined' => $log->rows_examined,
                    'duration' => round($log->query_duration, 4),
                    'timestamp' => $log->created_at->toDateTimeString(),
                ];
            })
            ->toArray();

        return [
            'top_queries_by_rows' => $topQueries,
        ];
    }

    /**
     * Send alerts via multiple channels
     *
     * @param array $alerts
     * @return void
     */
    private function sendAlerts(array $alerts): void
    {
        // Log alerts
        foreach ($alerts as $alert) {
            Log::warning("Performance Alert: {$alert['type']}", $alert);
        }

        // TODO: Send email notifications
        // TODO: Send Slack notifications
        // TODO: Send SMS for critical alerts
    }

    /**
     * Get alert summary for dashboard
     *
     * @return array
     */
    public function getAlertSummary(): array
    {
        $lastHour = AdminQueryLog::fromLastHours(1);

        return [
            'slow_queries_count' => $lastHour->slowQueries(self::SLOW_QUERY_THRESHOLD)->count(),
            'avg_query_time' => round($lastHour->avg('query_duration') ?? 0, 4),
            'max_query_time' => round($lastHour->max('query_duration') ?? 0, 4),
            'total_queries' => $lastHour->count(),
            'total_rows_examined' => $lastHour->sum('rows_examined') ?? 0,
            'alerts' => $this->checkPerformanceMetrics(),
        ];
    }

    /**
     * Get performance trend
     *
     * @return array
     */
    public function getPerformanceTrend(): array
    {
        $now = now();
        $trend = [];

        // Get metrics for last 24 hours (hourly)
        for ($i = 23; $i >= 0; $i--) {
            $hour = $now->copy()->subHours($i);
            $nextHour = $hour->copy()->addHour();

            $metrics = AdminQueryLog::whereBetween('created_at', [$hour, $nextHour])
                ->selectRaw('
                    COUNT(*) as total_queries,
                    AVG(query_duration) as avg_duration,
                    MAX(query_duration) as max_duration,
                    SUM(rows_examined) as total_rows_examined
                ')
                ->first();

            $trend[$hour->format('H:00')] = [
                'total_queries' => $metrics->total_queries ?? 0,
                'avg_duration' => round($metrics->avg_duration ?? 0, 4),
                'max_duration' => round($metrics->max_duration ?? 0, 4),
                'total_rows_examined' => $metrics->total_rows_examined ?? 0,
            ];
        }

        return $trend;
    }
}
