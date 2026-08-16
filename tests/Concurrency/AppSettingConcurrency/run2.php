<?php
/**
 * Orchestrator (store-selectable). Usage: php run2.php <store>  (posix|unsafe)
 *
 * Seeds {"admin_switch":1,"versions":{}}, fires ~50 concurrent workers
 * (proc_open; pcntl unavailable on Windows), then asserts:
 *   (a) admin_switch === 1  (admin force-update toggle never lost)
 *   (b) all expected version keys present (no lost update)
 */
$store = $argv[1] ?? 'posix';

$dir  = __DIR__;
$file = $dir . DIRECTORY_SEPARATOR . 'settings_' . $store . '.json';

@unlink($file);
@unlink($file . '.lock');
file_put_contents($file, json_encode(['admin_switch' => 1, 'versions' => new stdClass()]));

$php = PHP_BINARY;
$N   = 50;

$jobs = [];
$expectedVersions = [];
for ($i = 1; $i <= $N; $i++) {
    if ($i % 7 === 0) {
        $jobs[] = ['mode' => 'admin', 'id' => $i];
    } else {
        $jobs[] = ['mode' => 'version', 'id' => $i];
        $expectedVersions[] = 'v' . $i;
    }
}

$procs = [];
foreach ($jobs as $job) {
    $cmd = [$php, $dir . DIRECTORY_SEPARATOR . 'worker2.php', $store, $file, $job['mode'], (string) $job['id']];
    $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (is_resource($p)) {
        fclose($pipes[0]);
        $procs[] = ['proc' => $p, 'pipes' => $pipes];
    }
}

echo "[store=$store] Launched " . count($procs) . " concurrent workers...\n";

foreach ($procs as $entry) {
    stream_get_contents($entry['pipes'][1]);
    $err = stream_get_contents($entry['pipes'][2]);
    fclose($entry['pipes'][1]);
    fclose($entry['pipes'][2]);
    proc_close($entry['proc']);
    if (trim($err) !== '') {
        fwrite(STDERR, "worker stderr: " . trim($err) . "\n");
    }
}

$raw   = file_get_contents($file);
$final = json_decode($raw, true);
$failures = [];

if (!is_array($final)) {
    $failures[] = "final file is not valid JSON array. raw=" . var_export($raw, true);
} else {
    if (($final['admin_switch'] ?? null) !== 1) {
        $failures[] = "admin_switch lost: expected 1, got " . var_export($final['admin_switch'] ?? null, true);
    }
    $versions = $final['versions'] ?? [];
    if (!is_array($versions)) { $versions = []; }
    $missing = array_values(array_diff($expectedVersions, array_keys($versions)));
    if (!empty($missing)) {
        $failures[] = count($missing) . " version(s) lost: " . implode(',', $missing);
    }
    $found = count(array_intersect($expectedVersions, array_keys($versions)));
    echo "Expected versions: " . count($expectedVersions) . "\n";
    echo "Found versions:    " . $found . "\n";
    echo "admin_switch:      " . var_export($final['admin_switch'] ?? null, true) . "\n";
}

if (empty($failures)) {
    echo "RESULT: PASS — no lost update.\n";
    exit(0);
}
echo "RESULT: FAIL\n";
foreach ($failures as $f) { echo " - $f\n"; }
exit(1);
