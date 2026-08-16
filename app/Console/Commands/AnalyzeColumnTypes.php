<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyzeColumnTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze-columns
                            {--table= : Specific table to analyze}
                            {--export= : Export results to JSON file}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database columns and recommend type optimizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting database column analysis...');
        $this->newLine();

        $tables = $this->option('table')
            ? [$this->option('table')]
            : $this->getAllTables();

        if (empty($tables)) {
            $this->error('No tables found to analyze.');
            return 1;
        }

        $report = [];
        $progressBar = $this->output->createProgressBar(count($tables));
        $progressBar->start();

        foreach ($tables as $table) {
            $report[$table] = $this->analyzeTable($table);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($report);

        // Export if requested
        if ($export = $this->option('export')) {
            $this->exportReport($report, $export);
        }

        return 0;
    }

    /**
     * Get all tables in the database
     */
    private function getAllTables(): array
    {
        $database = config('database.connections.mysql.database');
        $tables = DB::select("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$database]);

        return array_column($tables, 'TABLE_NAME');
    }

    /**
     * Analyze a specific table
     */
    private function analyzeTable(string $table): array
    {
        try {
            $columns = $this->getTableColumns($table);
            $analysis = [];

            foreach ($columns as $column) {
                $columnName = $column->COLUMN_NAME;
                $dataType = $column->DATA_TYPE;
                $columnType = $column->COLUMN_TYPE;

                // Analyze based on data type
                if (in_array($dataType, ['varchar', 'char', 'text', 'longtext', 'mediumtext', 'tinytext'])) {
                    $analysis[$columnName] = $this->analyzeStringColumn($table, $columnName, $dataType, $columnType);
                } elseif (in_array($dataType, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint'])) {
                    $analysis[$columnName] = $this->analyzeIntegerColumn($table, $columnName, $dataType, $columnType);
                } elseif (in_array($dataType, ['double', 'float', 'decimal'])) {
                    $analysis[$columnName] = $this->analyzeNumericColumn($table, $columnName, $dataType, $columnType);
                }
            }

            return [
                'columns_analyzed' => count($analysis),
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get table columns metadata
     */
    private function getTableColumns(string $table): array
    {
        $database = config('database.connections.mysql.database');
        return DB::select("
            SELECT
                COLUMN_NAME,
                DATA_TYPE,
                COLUMN_TYPE,
                CHARACTER_MAXIMUM_LENGTH,
                IS_NULLABLE,
                COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$database, $table]);
    }

    /**
     * Analyze string column (VARCHAR, CHAR, TEXT)
     */
    private function analyzeStringColumn(string $table, string $column, string $dataType, string $columnType): array
    {
        $stats = DB::selectOne("
            SELECT
                COUNT(*) as total_rows,
                COUNT(DISTINCT `{$column}`) as distinct_values,
                MAX(LENGTH(`{$column}`)) as max_length,
                AVG(LENGTH(`{$column}`)) as avg_length,
                MIN(LENGTH(`{$column}`)) as min_length,
                SUM(CASE WHEN `{$column}` IS NULL THEN 1 ELSE 0 END) as null_count
            FROM `{$table}`
        ");

        $recommendation = $this->getStringRecommendation($stats, $dataType, $columnType);

        return [
            'current_type' => $columnType,
            'max_length' => $stats->max_length ?? 0,
            'avg_length' => round($stats->avg_length ?? 0, 2),
            'min_length' => $stats->min_length ?? 0,
            'distinct_values' => $stats->distinct_values ?? 0,
            'total_rows' => $stats->total_rows ?? 0,
            'null_count' => $stats->null_count ?? 0,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Analyze integer column
     */
    private function analyzeIntegerColumn(string $table, string $column, string $dataType, string $columnType): array
    {
        $stats = DB::selectOne("
            SELECT
                COUNT(*) as total_rows,
                MIN(`{$column}`) as min_value,
                MAX(`{$column}`) as max_value,
                AVG(`{$column}`) as avg_value,
                SUM(CASE WHEN `{$column}` IS NULL THEN 1 ELSE 0 END) as null_count
            FROM `{$table}`
        ");

        $recommendation = $this->getIntegerRecommendation($stats, $dataType, $columnType);

        return [
            'current_type' => $columnType,
            'min_value' => $stats->min_value ?? 0,
            'max_value' => $stats->max_value ?? 0,
            'avg_value' => round($stats->avg_value ?? 0, 2),
            'total_rows' => $stats->total_rows ?? 0,
            'null_count' => $stats->null_count ?? 0,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Analyze numeric column (DOUBLE, FLOAT, DECIMAL)
     */
    private function analyzeNumericColumn(string $table, string $column, string $dataType, string $columnType): array
    {
        $stats = DB::selectOne("
            SELECT
                COUNT(*) as total_rows,
                MIN(`{$column}`) as min_value,
                MAX(`{$column}`) as max_value,
                AVG(`{$column}`) as avg_value,
                SUM(CASE WHEN `{$column}` IS NULL THEN 1 ELSE 0 END) as null_count,
                SUM(CASE WHEN (`{$column}` - FLOOR(`{$column}`)) > 0.01 THEN 1 ELSE 0 END) as has_decimals
            FROM `{$table}`
        ");

        $recommendation = $this->getNumericRecommendation($stats, $dataType, $columnType);

        return [
            'current_type' => $columnType,
            'min_value' => $stats->min_value ?? 0,
            'max_value' => $stats->max_value ?? 0,
            'avg_value' => round($stats->avg_value ?? 0, 2),
            'has_decimals' => $stats->has_decimals ?? 0,
            'total_rows' => $stats->total_rows ?? 0,
            'null_count' => $stats->null_count ?? 0,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Get recommendation for string columns
     */
    private function getStringRecommendation($stats, string $dataType, string $columnType): string
    {
        $maxLen = $stats->max_length ?? 0;
        $minLen = $stats->min_length ?? 0;
        $distinctValues = $stats->distinct_values ?? 0;
        $totalRows = $stats->total_rows ?? 1;

        // If all values are same length and short, recommend CHAR
        if ($maxLen === $minLen && $maxLen > 0 && $maxLen <= 50) {
            return "CHAR({$maxLen}) - fixed length detected";
        }

        // If very few distinct values, recommend ENUM
        if ($distinctValues > 0 && $distinctValues <= 10 && ($distinctValues / $totalRows) < 0.01) {
            return "ENUM - only {$distinctValues} distinct values detected";
        }

        // For VARCHAR columns, recommend appropriate size
        if (strpos($dataType, 'varchar') !== false) {
            if ($maxLen <= 50) {
                return "VARCHAR(50)";
            } elseif ($maxLen <= 100) {
                return "VARCHAR(100)";
            } elseif ($maxLen <= 150) {
                return "VARCHAR(150)";
            } elseif ($maxLen <= 255) {
                return "VARCHAR(255)";
            } elseif ($maxLen <= 500) {
                return "VARCHAR(500)";
            } elseif ($maxLen <= 1000) {
                return "VARCHAR(1000)";
            } else {
                return "TEXT - data exceeds practical VARCHAR limit";
            }
        }

        // For TEXT columns
        if ($dataType === 'longtext' && $maxLen <= 65535) {
            return "TEXT - LONGTEXT not needed";
        }

        if ($dataType === 'text' && $maxLen <= 255) {
            return "VARCHAR(255) - TEXT not needed";
        }

        return "Keep current type: {$columnType}";
    }

    /**
     * Get recommendation for integer columns
     */
    private function getIntegerRecommendation($stats, string $dataType, string $columnType): string
    {
        $minVal = $stats->min_value ?? 0;
        $maxVal = $stats->max_value ?? 0;

        $isUnsigned = strpos($columnType, 'unsigned') !== false;

        // Check if TINYINT is sufficient
        if ($isUnsigned && $minVal >= 0 && $maxVal <= 255) {
            return "TINYINT UNSIGNED (0-255) - current max: {$maxVal}";
        } elseif (!$isUnsigned && $minVal >= -128 && $maxVal <= 127) {
            return "TINYINT (-128 to 127) - current range: {$minVal} to {$maxVal}";
        }

        // Check if SMALLINT is sufficient
        if ($isUnsigned && $minVal >= 0 && $maxVal <= 65535) {
            return "SMALLINT UNSIGNED (0-65535) - current max: {$maxVal}";
        } elseif (!$isUnsigned && $minVal >= -32768 && $maxVal <= 32767) {
            return "SMALLINT (-32768 to 32767) - current range: {$minVal} to {$maxVal}";
        }

        // Check if MEDIUMINT is sufficient
        if ($isUnsigned && $minVal >= 0 && $maxVal <= 16777215) {
            return "MEDIUMINT UNSIGNED - current max: {$maxVal}";
        }

        // Check if INT is sufficient (vs BIGINT)
        if ($dataType === 'bigint') {
            if ($isUnsigned && $maxVal <= 4294967295) {
                return "INT UNSIGNED - BIGINT not needed, max: {$maxVal}";
            } elseif (!$isUnsigned && $minVal >= -2147483648 && $maxVal <= 2147483647) {
                return "INT - BIGINT not needed, range: {$minVal} to {$maxVal}";
            }
        }

        return "Keep current type: {$columnType}";
    }

    /**
     * Get recommendation for numeric columns
     */
    private function getNumericRecommendation($stats, string $dataType, string $columnType): string
    {
        $hasDecimals = $stats->has_decimals ?? 0;

        // If it's a DOUBLE or FLOAT with currency-like data, recommend DECIMAL
        if (in_array($dataType, ['double', 'float'])) {
            if ($hasDecimals > 0) {
                return "⚠️  DECIMAL(15,2) - CRITICAL: Use DECIMAL for precision (detected {$hasDecimals} rows with decimals)";
            } else {
                return "⚠️  Consider DECIMAL(15,2) or INT if this is currency/financial data";
            }
        }

        return "Keep current type: {$columnType}";
    }

    /**
     * Display analysis summary
     */
    private function displaySummary(array $report): void
    {
        $this->info('📊 Analysis Summary');
        $this->newLine();

        $totalOptimizations = 0;
        $criticalIssues = 0;

        foreach ($report as $table => $data) {
            if (isset($data['error'])) {
                $this->warn("❌ {$table}: {$data['error']}");
                continue;
            }

            $optimizations = 0;
            $critical = 0;

            foreach ($data['analysis'] ?? [] as $column => $analysis) {
                if (!str_contains($analysis['recommendation'], 'Keep current')) {
                    $optimizations++;

                    if (str_contains($analysis['recommendation'], 'CRITICAL')) {
                        $critical++;
                    }

                    if ($this->option('verbose')) {
                        $this->line("  📝 {$table}.{$column}: {$analysis['recommendation']}");
                    }
                }
            }

            $totalOptimizations += $optimizations;
            $criticalIssues += $critical;

            if ($optimizations > 0) {
                $icon = $critical > 0 ? '⚠️ ' : '✅';
                $this->line("{$icon} {$table}: {$optimizations} optimization(s) found" . ($critical > 0 ? " ({$critical} critical)" : ""));
            }
        }

        $this->newLine();
        $this->info("Total optimization opportunities: {$totalOptimizations}");
        if ($criticalIssues > 0) {
            $this->warn("⚠️  Critical issues (DOUBLE/FLOAT for currency): {$criticalIssues}");
        }
    }

    /**
     * Export report to JSON file
     */
    private function exportReport(array $report, string $filepath): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $json);
        $this->info("✅ Report exported to: {$filepath}");
    }
}
