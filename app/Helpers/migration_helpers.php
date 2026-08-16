// General migration helper for Laravel to prevent duplicate table creation
// Usage: Replace Schema::create('table', ...) with this helper in migrations

use Illuminate\Support\Facades\Schema;

if (!function_exists('safeCreateTable')) {
    function safeCreateTable($table, $callback) {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }
}
