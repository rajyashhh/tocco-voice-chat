<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AdminQueryLog Model
 * 
 * Tracks performance metrics for admin grid queries
 * - Query duration
 * - Rows examined and returned
 * - Table names
 * - Timestamps for trend analysis
 */
class AdminQueryLog extends Model
{
    use HasFactory;

    protected $table = 'admin_query_logs';

    protected $fillable = [
        'table_name',
        'query_duration',
        'rows_examined',
        'rows_returned',
        'query_hash',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'query_duration' => 'float',
        'rows_examined' => 'integer',
        'rows_returned' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get slow queries
     */
    public function scopeSlowQueries($query, $threshold = 1.0)
    {
        return $query->where('query_duration', '>=', $threshold)
            ->orderBy('query_duration', 'desc');
    }

    /**
     * Scope: Get queries from last N hours
     */
    public function scopeFromLastHours($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Get queries by table
     */
    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Get average query duration
     */
    public static function getAverageDuration($tableName = null, $hours = 24)
    {
        $query = self::fromLastHours($hours);

        if ($tableName) {
            $query->byTable($tableName);
        }

        return $query->avg('query_duration') ?? 0;
    }

    /**
     * Get total queries count
     */
    public static function getTotalQueries($tableName = null, $hours = 24)
    {
        $query = self::fromLastHours($hours);

        if ($tableName) {
            $query->byTable($tableName);
        }

        return $query->count();
    }

    /**
     * Get max query duration
     */
    public static function getMaxDuration($tableName = null, $hours = 24)
    {
        $query = self::fromLastHours($hours);

        if ($tableName) {
            $query->byTable($tableName);
        }

        return $query->max('query_duration') ?? 0;
    }

    /**
     * Get total rows examined
     */
    public static function getTotalRowsExamined($tableName = null, $hours = 24)
    {
        $query = self::fromLastHours($hours);

        if ($tableName) {
            $query->byTable($tableName);
        }

        return $query->sum('rows_examined') ?? 0;
    }
}
