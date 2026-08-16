<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * CheckDatabaseHealth Command
 * 
 * Comprehensive database health check
 * - Validates indexes
 * - Detects deadlocks
 * - Monitors connections
 * - Finds long-running queries
 * - Checks table locks
 * - Reports index usage
 */
class CheckDatabaseHealth extends Command
{
    protected $signature = 'db:health-check
        {--detailed : Show detailed information}
        {--format=table : Output format (table, json)}';

    protected $description = 'Check database health and performance';

    private $checks = [
        'indexes' => false,
        'deadlocks' => false,
        'connections' => false,
        'long_queries' => false,
        'locks' => false,
        'index_usage' => false,
    ];

    private $warnings = [];
    private $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Database Health Check');
        $this->newLine();

        try {
            // Run all checks
            $this->checkMissingIndexes();
            $this->checkDeadlocks();
            $this->checkConnections();
            $this->checkLongRunningQueries();
            $this->checkTableLocks();
            $this->checkIndexUsage();

            // Print summary
            $this->printSummary();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during health check: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Check for missing indexes
     */
    private function checkMissingIndexes(): void
    {
        $this->line('<fg=cyan>Checking Indexes...</>');

        $requiredIndexes = [
            'users' => [
                'device_token' => 'idx_device_token',
            ],
            'chat_messages' => [
                'chat_room_id,status' => 'idx_chatroom_status',
            ],
        ];

        $indexData = [];

        foreach ($requiredIndexes as $table => $indexes) {
            foreach ($indexes as $columns => $indexName) {
                try {
                    $exists = $this->indexExists($table, $indexName);

                    if ($exists) {
                        $this->info("  ✅ Index <fg=green>$indexName</> exists on <fg=cyan>$table</>");
                        $indexData[] = [$table, $columns, $indexName, 'EXISTS'];
                    } else {
                        $this->error("  ❌ Missing index: <fg=red>$indexName</> on <fg=cyan>$table($columns)</>");
                        $this->errors[] = "Missing index: $indexName on $table($columns)";
                        $indexData[] = [$table, $columns, $indexName, 'MISSING'];
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠️  Error checking index $indexName: {$e->getMessage()}");
                }
            }
        }

        if ($this->option('detailed')) {
            $this->newLine();
            $this->table(['Table', 'Columns', 'Index Name', 'Status'], $indexData);
        }

        $this->checks['indexes'] = true;
        $this->newLine();
    }

    /**
     * Check for deadlocks
     */
    private function checkDeadlocks(): void
    {
        $this->line('<fg=cyan>Checking Deadlocks...</>');

        try {
            $status = DB::select("SHOW ENGINE INNODB STATUS");

            if (empty($status)) {
                $this->info('  ✅ No deadlock information available');
                $this->checks['deadlocks'] = true;
                $this->newLine();
                return;
            }

            $statusText = $status[0]->Status ?? '';

            if (stripos($statusText, 'DEADLOCK') !== false) {
                $this->error('  ❌ Deadlock detected!');
                $this->errors[] = 'Deadlock detected in InnoDB';

                if ($this->option('detailed')) {
                    $this->newLine();
                    $this->line('<fg=red>Deadlock Details:</>');
                    $this->line($statusText);
                }
            } else {
                $this->info('  ✅ No active deadlocks detected');
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Could not check deadlocks: {$e->getMessage()}");
        }

        $this->checks['deadlocks'] = true;
        $this->newLine();
    }

    /**
     * Check idle connections
     */
    private function checkConnections(): void
    {
        $this->line('<fg=cyan>Checking Connections...</>');

        try {
            // Get idle connections
            $idleResult = DB::select("
                SELECT COUNT(*) as count, AVG(TIME) as avg_time
                FROM INFORMATION_SCHEMA.PROCESSLIST
                WHERE COMMAND = 'Sleep'
            ");

            $idleCount = $idleResult[0]->count ?? 0;
            $avgIdleTime = $idleResult[0]->avg_time ?? 0;

            // Get active connections
            $activeResult = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.PROCESSLIST
                WHERE COMMAND != 'Sleep' AND COMMAND != ''
            ");

            $activeCount = $activeResult[0]->count ?? 0;

            // Get max connections
            $maxResult = DB::select("SELECT @@max_connections as max_connections");
            $maxConnections = $maxResult[0]->max_connections ?? 0;

            $totalConnections = $idleCount + $activeCount;
            $utilization = $maxConnections > 0 ? round(($totalConnections / $maxConnections) * 100, 2) : 0;

            // Display results
            $this->info("  ✅ Idle connections: <fg=yellow>$idleCount</> (avg sleep: {$avgIdleTime}s)");
            $this->info("  ✅ Active connections: <fg=green>$activeCount</>");
            $this->info("  ✅ Max connections: <fg=cyan>$maxConnections</>");
            $this->info("  ✅ Utilization: <fg=cyan>$utilization%</>");

            // Warn if too many idle connections
            if ($idleCount > 50) {
                $this->warn("  ⚠️  High number of idle connections: $idleCount (> 50)");
                $this->warnings[] = "High idle connections: $idleCount";
            }

            if ($utilization > 80) {
                $this->warn("  ⚠️  High connection utilization: $utilization%");
                $this->warnings[] = "High connection utilization: $utilization%";
            }

            if ($this->option('detailed')) {
                $this->newLine();
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Idle Connections', $idleCount],
                        ['Active Connections', $activeCount],
                        ['Total Connections', $totalConnections],
                        ['Max Connections', $maxConnections],
                        ['Utilization %', $utilization],
                        ['Avg Idle Time (s)', round($avgIdleTime, 2)],
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Could not check connections: {$e->getMessage()}");
        }

        $this->checks['connections'] = true;
        $this->newLine();
    }

    /**
     * Check for long-running queries
     */
    private function checkLongRunningQueries(): void
    {
        $this->line('<fg=cyan>Checking Long-Running Queries...</>');

        try {
            $processes = DB::select("SHOW PROCESSLIST");

            $longRunning = [];
            $threshold = 30; // seconds

            foreach ($processes as $process) {
                if ($process->Time >= $threshold && $process->Command !== 'Sleep') {
                    $longRunning[] = $process;
                }
            }

            if (empty($longRunning)) {
                $this->info("  ✅ No long-running queries (> {$threshold}s)");
            } else {
                $this->warn("  ⚠️  Found " . count($longRunning) . " long-running queries:");
                $this->warnings[] = count($longRunning) . " queries running > {$threshold}s";

                $queryData = [];
                foreach ($longRunning as $query) {
                    $queryData[] = [
                        $query->Id,
                        $query->User,
                        $query->Host,
                        $query->Time . 's',
                        substr($query->Info ?? 'N/A', 0, 50),
                    ];
                }

                $this->newLine();
                $this->table(['ID', 'User', 'Host', 'Time', 'Query'], $queryData);
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Could not check long-running queries: {$e->getMessage()}");
        }

        $this->checks['long_queries'] = true;
        $this->newLine();
    }

    /**
     * Check for table locks
     */
    private function checkTableLocks(): void
    {
        $this->line('<fg=cyan>Checking Table Locks...</>');

        try {
            $processes = DB::select("SHOW PROCESSLIST");

            $lockedQueries = [];

            foreach ($processes as $process) {
                if (stripos($process->State ?? '', 'Waiting for table metadata lock') !== false) {
                    $lockedQueries[] = $process;
                }
            }

            if (empty($lockedQueries)) {
                $this->info('  ✅ No table metadata locks detected');
            } else {
                $this->error('  ❌ Found ' . count($lockedQueries) . ' queries waiting for table locks:');
                $this->errors[] = count($lockedQueries) . " queries waiting for table locks";

                $lockData = [];
                foreach ($lockedQueries as $query) {
                    $lockData[] = [
                        $query->Id,
                        $query->User,
                        $query->Time . 's',
                        substr($query->Info ?? 'N/A', 0, 50),
                    ];
                }

                $this->newLine();
                $this->table(['ID', 'User', 'Time', 'Query'], $lockData);
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Could not check table locks: {$e->getMessage()}");
        }

        $this->checks['locks'] = true;
        $this->newLine();
    }

    /**
     * Check index usage
     */
    private function checkIndexUsage(): void
    {
        $this->line('<fg=cyan>Checking Index Usage...</>');

        try {
            $tables = ['users', 'chat_messages'];
            $indexData = [];
            $totalIndexes = 0;
            $unusedIndexes = 0;

            foreach ($tables as $table) {
                try {
                    $indexes = DB::select("SHOW INDEX FROM $table");

                    foreach ($indexes as $index) {
                        $totalIndexes++;
                        $indexData[] = [
                            $table,
                            $index->Key_name,
                            $index->Column_name,
                            $index->Cardinality ?? 'N/A',
                        ];

                        // Note: In production, you'd check performance_schema for actual usage
                        // This is a simplified check
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠️  Could not check indexes for table $table");
                }
            }

            if ($totalIndexes > 0) {
                $this->info("  ✅ Total indexes: <fg=cyan>$totalIndexes</>");

                if ($this->option('detailed') && !empty($indexData)) {
                    $this->newLine();
                    $this->table(['Table', 'Index Name', 'Column', 'Cardinality'], $indexData);
                }
            } else {
                $this->warn('  ⚠️  No indexes found');
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Could not check index usage: {$e->getMessage()}");
        }

        $this->checks['index_usage'] = true;
        $this->newLine();
    }

    /**
     * Print summary
     */
    private function printSummary(): void
    {
        $this->line('<fg=cyan>═══════════════════════════════════════</>');
        $this->line('<fg=cyan>Summary</>');
        $this->line('<fg=cyan>═══════════════════════════════════════</>');

        $totalChecks = count($this->checks);
        $passedChecks = $totalChecks - count($this->warnings) - count($this->errors);

        $this->newLine();
        $this->info("  Total checks: <fg=cyan>$totalChecks</>");
        $this->info("  Passed: <fg=green>$passedChecks</>");

        if (count($this->warnings) > 0) {
            $this->warn("  Warnings: <fg=yellow>" . count($this->warnings) . "</>");
        }

        if (count($this->errors) > 0) {
            $this->error("  Errors: <fg=red>" . count($this->errors) . "</>");
        }

        if (!empty($this->warnings)) {
            $this->newLine();
            $this->line('<fg=yellow>Warnings:</>');
            foreach ($this->warnings as $warning) {
                $this->line("  • $warning");
            }
        }

        if (!empty($this->errors)) {
            $this->newLine();
            $this->line('<fg=red>Errors:</>');
            foreach ($this->errors as $error) {
                $this->line("  • $error");
            }
        }

        $this->newLine();
        $this->line('Run with <fg=cyan>--detailed</> for more information.');

        if ($this->option('format') === 'json') {
            $this->outputJson();
        }
    }

    /**
     * Output results as JSON
     */
    private function outputJson(): void
    {
        $output = [
            'status' => empty($this->errors) ? 'healthy' : 'unhealthy',
            'checks' => $this->checks,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->newLine();
        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
}
