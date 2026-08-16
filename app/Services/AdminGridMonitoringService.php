<?php

namespace App\Services;

use DB;
use App\Models\User;
use App\Models\AdminQueryLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AdminGridMonitoringService
 * 
 * Comprehensive monitoring dashboard for admin grid performance
 * - Tracks query performance
 * - Monitors connection pool health
 * - Detects slow queries
 * - Generates performance reports
 */
class AdminGridMonitoringService
{
    const SLOW_QUERY_THRESHOLD = 1.0; 
    const IDLE_CONNECTION_THRESHOLD = 30; 
    const MONITORED_TABLES = ['users', 'user_profiles', 'chat_messages'];

    /**
     * 
     * @return array
     */
    public function getAdminGridMetrics(): array
    {
        $metrics = [];

        $start = microtime(true);
        $gridData = User::with('profile')
            ->paginate(15);
        $metrics['grid_load_time'] = round((microtime(true) - $start) * 1000, 2); // ms

        $metrics['total_queries'] = count(DB::getQueryLog());

        $idleConns = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.PROCESSLIST 
            WHERE COMMAND = 'Sleep'
        ");
        $metrics['idle_connections'] = $idleConns[0]->count ?? 0;

        $activeConns = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.PROCESSLIST 
            WHERE COMMAND != 'Sleep' AND COMMAND != ''
        ");
        $metrics['active_connections'] = $activeConns[0]->count ?? 0;

        $maxConns = DB::select("
            SELECT @@max_connections as max_connections
        ");
        $metrics['max_connections'] = $maxConns[0]->max_connections ?? 0;

        $totalConns = $metrics['idle_connections'] + $metrics['active_connections'];
        $metrics['connection_utilization'] = $metrics['max_connections'] > 0
            ? round(($totalConns / $metrics['max_connections']) * 100, 2)
            : 0;

        $metrics['query_metrics'] = $this->getQueryPerformanceMetrics();

        $metrics['slow_queries_count'] = AdminQueryLog::slowQueries(self::SLOW_QUERY_THRESHOLD)
            ->fromLastHours(1)
            ->count();

        $metrics['avg_query_time'] = round(AdminQueryLog::fromLastHours(1)->avg('query_duration') ?? 0, 4);

        $metrics['memory_estimate'] = $this->estimateMemoryUsage();

        return $metrics;
    }

    /**
     * 
     * @return void
     */
    public function trackQueryPerformance(): void
    {
        DB::enableQueryLog();

        foreach (self::MONITORED_TABLES as $table) {
            try {
                $this->logTableQueries($table);
            } catch (\Exception $e) {
                Log::error("Error tracking queries for table: {$table}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->processQueryLogs();
    }

    /**
     * 
     * @param string $tableName
     * @return void
     */
    private function logTableQueries(string $tableName): void
    {
    }

    /**
     * Process and store query logs
     * 
     * @return void
     */
    private function processQueryLogs(): void
    {
        $queries = DB::getQueryLog();

        foreach ($queries as $query) {
            $tableName = $this->extractTableName($query['query']);

            // Only log queries for monitored tables
            if (!in_array($tableName, self::MONITORED_TABLES)) {
                continue;
            }

            try {
                AdminQueryLog::create([
                    'table_name' => $tableName,
                    'query_duration' => $query['time'] / 1000, // Convert ms to seconds
                    'rows_examined' => $this->estimateRowsExamined($query),
                    'rows_returned' => 0, // Will be updated by middleware
                    'query_hash' => md5($query['query']),
                ]);
            } catch (\Exception $e) {
                Log::error('Error logging query', [
                    'query' => $query['query'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get slow queries exceeding threshold
     * 
     * @param float $threshold
     * @param int $hours
     * @return array
     */
    public function getSlowQueries(float $threshold = self::SLOW_QUERY_THRESHOLD, int $hours = 24): array
    {
        $slowQueries = AdminQueryLog::slowQueries($threshold)
            ->fromLastHours($hours)
            ->orderBy('query_duration', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'table' => $log->table_name,
                    'duration' => round($log->query_duration, 4),
                    'rows_examined' => $log->rows_examined,
                    'rows_returned' => $log->rows_returned,
                    'timestamp' => $log->created_at->toDateTimeString(),
                ];
            })
            ->toArray();

        return [
            'threshold' => $threshold,
            'hours' => $hours,
            'count' => count($slowQueries),
            'queries' => $slowQueries,
        ];
    }

    /**
     * Get connection pool health status
     * 
     * @return array
     */
    public function getConnectionPoolHealth(): array
    {
        // Get idle connections
        $idleResult = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.PROCESSLIST 
            WHERE COMMAND = 'Sleep'
        ");
        $idle = $idleResult[0]->count ?? 0;

        // Get active connections
        $activeResult = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.PROCESSLIST 
            WHERE COMMAND != 'Sleep' AND COMMAND != ''
        ");
        $active = $activeResult[0]->count ?? 0;

        // Get max connections
        $maxResult = DB::select("
            SELECT @@max_connections as max_connections
        ");
        $max = $maxResult[0]->max_connections ?? 0;

        $total = $idle + $active;
        $utilization = $max > 0 ? round(($total / $max) * 100, 2) : 0;

        return [
            'idle' => $idle,
            'active' => $active,
            'total' => $total,
            'max' => $max,
            'utilization_percent' => $utilization,
            'health_status' => $this->getHealthStatus($utilization),
        ];
    }

    /**
     * Get index utilization report
     * 
     * @return array
     */
    public function getIndexUtilizationReport(): array
    {
        $report = [];

        foreach (self::MONITORED_TABLES as $table) {
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table}");

                $report[$table] = [
                    'total_indexes' => count($indexes),
                    'indexes' => array_map(function ($index) {
                        return [
                            'name' => $index->Key_name,
                            'column' => $index->Column_name,
                            'seq_in_index' => $index->Seq_in_index,
                            'cardinality' => $index->Cardinality,
                        ];
                    }, $indexes),
                ];
            } catch (\Exception $e) {
                Log::error("Error getting indexes for table: {$table}", [
                    'error' => $e->getMessage(),
                ]);
                $report[$table] = ['error' => $e->getMessage()];
            }
        }

        return $report;
    }

    /**
     * Generate daily performance report
     * 
     * @return array
     */
    public function generateDailyReport(): array
    {
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        // Today's metrics
        $todayMetrics = [
            'total_queries' => AdminQueryLog::where('created_at', '>=', $today)->count(),
            'avg_duration' => AdminQueryLog::where('created_at', '>=', $today)->avg('query_duration') ?? 0,
            'max_duration' => AdminQueryLog::where('created_at', '>=', $today)->max('query_duration') ?? 0,
            'slow_queries' => AdminQueryLog::slowQueries(self::SLOW_QUERY_THRESHOLD)
                ->where('created_at', '>=', $today)
                ->count(),
            'total_rows_examined' => AdminQueryLog::where('created_at', '>=', $today)->sum('rows_examined') ?? 0,
        ];

        // Yesterday's metrics
        $yesterdayMetrics = [
            'total_queries' => AdminQueryLog::whereBetween('created_at', [$yesterday, $today])->count(),
            'avg_duration' => AdminQueryLog::whereBetween('created_at', [$yesterday, $today])->avg('query_duration') ?? 0,
            'max_duration' => AdminQueryLog::whereBetween('created_at', [$yesterday, $today])->max('query_duration') ?? 0,
            'slow_queries' => AdminQueryLog::slowQueries(self::SLOW_QUERY_THRESHOLD)
                ->whereBetween('created_at', [$yesterday, $today])
                ->count(),
            'total_rows_examined' => AdminQueryLog::whereBetween('created_at', [$yesterday, $today])->sum('rows_examined') ?? 0,
        ];

        // Calculate trends
        $trends = $this->calculateTrends($todayMetrics, $yesterdayMetrics);

        // Check for degradation
        $hasRegression = $trends['avg_duration_trend'] > 10 || $trends['slow_queries_trend'] > 20;

        if ($hasRegression) {
            $this->sendPerformanceAlert($todayMetrics, $trends);
        }

        return [
            'date' => $today->toDateString(),
            'today' => $todayMetrics,
            'yesterday' => $yesterdayMetrics,
            'trends' => $trends,
            'has_regression' => $hasRegression,
        ];
    }

    /**
     * Get query performance metrics by table
     * 
     * @return array
     */
    private function getQueryPerformanceMetrics(): array
    {
        $metrics = [];

        foreach (self::MONITORED_TABLES as $table) {
            $metrics[$table] = [
                'total_queries' => AdminQueryLog::byTable($table)->fromLastHours(1)->count(),
                'avg_duration' => round(AdminQueryLog::byTable($table)->fromLastHours(1)->avg('query_duration') ?? 0, 4),
                'max_duration' => round(AdminQueryLog::byTable($table)->fromLastHours(1)->max('query_duration') ?? 0, 4),
                'total_rows_examined' => AdminQueryLog::byTable($table)->fromLastHours(1)->sum('rows_examined') ?? 0,
            ];
        }

        return $metrics;
    }

    /**
     * Extract table name from query
     * 
     * @param string $query
     * @return string
     */
    private function extractTableName(string $query): string
    {
        // Simple regex to extract table name
        if (preg_match('/FROM\s+`?(\w+)`?/i', $query, $matches)) {
            return strtolower($matches[1]);
        }

        if (preg_match('/INTO\s+`?(\w+)`?/i', $query, $matches)) {
            return strtolower($matches[1]);
        }

        if (preg_match('/UPDATE\s+`?(\w+)`?/i', $query, $matches)) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }

    /**
     * Estimate rows examined from query
     * 
     * @param array $query
     * @return int
     */
    private function estimateRowsExamined(array $query): int
    {
        // This is a simplified estimation
        // In production, use EXPLAIN to get actual row counts
        return 0;
    }

    /**
     * Estimate memory usage
     * 
     * @return string
     */
    private function estimateMemoryUsage(): string
    {
        $connections = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.PROCESSLIST
        ");

        $connCount = $connections[0]->count ?? 0;
        $estimatedMemory = $connCount * 2; // Rough estimate: 2MB per connection

        return "{$estimatedMemory}MB";
    }

    /**
     * Get health status based on utilization
     * 
     * @param float $utilization
     * @return string
     */
    private function getHealthStatus(float $utilization): string
    {
        if ($utilization < 50) {
            return 'healthy';
        } elseif ($utilization < 80) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculate trends between two periods
     * 
     * @param array $today
     * @param array $yesterday
     * @return array
     */
    private function calculateTrends(array $today, array $yesterday): array
    {
        return [
            'avg_duration_trend' => $yesterday['avg_duration'] > 0
                ? round((($today['avg_duration'] - $yesterday['avg_duration']) / $yesterday['avg_duration']) * 100, 2)
                : 0,
            'slow_queries_trend' => $yesterday['slow_queries'] > 0
                ? round((($today['slow_queries'] - $yesterday['slow_queries']) / $yesterday['slow_queries']) * 100, 2)
                : 0,
            'total_queries_trend' => $yesterday['total_queries'] > 0
                ? round((($today['total_queries'] - $yesterday['total_queries']) / $yesterday['total_queries']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Send performance alert
     * 
     * @param array $metrics
     * @param array $trends
     * @return void
     */
    private function sendPerformanceAlert(array $metrics, array $trends): void
    {
        Log::warning('Performance degradation detected', [
            'metrics' => $metrics,
            'trends' => $trends,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // TODO: Send email/Slack notification
    }
}
