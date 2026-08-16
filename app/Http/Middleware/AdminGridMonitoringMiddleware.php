<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AdminQueryLog;
use Illuminate\Support\Facades\Log;

/**
 * AdminGridMonitoringMiddleware
 * 
 * Automatically tracks query performance for admin grid operations
 * - Logs all queries to monitored tables
 * - Captures query duration and row counts
 * - Enables automatic performance monitoring
 */
class AdminGridMonitoringMiddleware
{
    const MONITORED_TABLES = ['users', 'user_profiles', 'chat_messages'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only monitor admin routes
        if (!$this->isAdminRoute($request)) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();

        // Record start time
        $startTime = microtime(true);

        // Process request
        $response = $next($request);

        // Record end time
        $duration = microtime(true) - $startTime;

        // Log queries asynchronously
        try {
            $this->logQueries($request, $duration);
        } catch (\Exception $e) {
            Log::error('Error logging admin grid queries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $response;
    }

    /**
     * Check if this is an admin route
     *
     * @param Request $request
     * @return bool
     */
    private function isAdminRoute(Request $request): bool
    {
        return $request->is('admin/*') || $request->is('api/admin/*');
    }

    /**
     * Log all queries for monitored tables
     *
     * @param Request $request
     * @param float $duration
     * @return void
     */
    private function logQueries(Request $request, float $duration): void
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
                    'rows_returned' => $this->estimateRowsReturned($query),
                    'query_hash' => md5($query['query']),
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating admin query log', [
                    'table' => $tableName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Extract table name from query
     *
     * @param string $query
     * @return string
     */
    private function extractTableName(string $query): string
    {
        // Match FROM clause
        if (preg_match('/FROM\s+`?(\w+)`?/i', $query, $matches)) {
            return strtolower($matches[1]);
        }

        // Match INTO clause (INSERT)
        if (preg_match('/INTO\s+`?(\w+)`?/i', $query, $matches)) {
            return strtolower($matches[1]);
        }

        // Match UPDATE clause
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
        // For SELECT queries, try to get row count from EXPLAIN
        if (stripos($query['query'], 'SELECT') === 0) {
            try {
                $explainResult = DB::select('EXPLAIN ' . $query['query']);
                if (!empty($explainResult)) {
                    // Sum rows from all rows in EXPLAIN output
                    $totalRows = 0;
                    foreach ($explainResult as $row) {
                        if (isset($row->rows)) {
                            $totalRows += $row->rows;
                        }
                    }
                    return $totalRows;
                }
            } catch (\Exception $e) {
                // If EXPLAIN fails, return 0
                return 0;
            }
        }

        return 0;
    }

    /**
     * Estimate rows returned from query
     *
     * @param array $query
     * @return int
     */
    private function estimateRowsReturned(array $query): int
    {
        // This would require executing the query and counting results
        // For now, we'll estimate based on query type
        if (stripos($query['query'], 'LIMIT') !== false) {
            if (preg_match('/LIMIT\s+(\d+)/i', $query['query'], $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
