<?php
/**
 * Orchestrator: concurrency test for the patched AppSetting read-modify-write.
 *
 * 1. Seed settings file with {"admin_switch":1,"versions":{}}.
 * 2. Launch ~50 concurrent worker processes via proc_open (true OS-level
 *    parallelism, since pcntl_fork is unavailable on Windows):
 *       - ~half add a distinct versions["v<i>"]
 *       - several "admin" workers re-assert admin_switch=1, racing the rest
 * 3. Wait for all to finish.
 * 4. Assert: admin_switch === 1 (never lost) AND every expected version key is
 *    present (no lost update). Exit 0 on pass, 1 on fail.
 */

$dir  = __DIR__;
$file = $dir . DIRECTORY_SEPARATOR . 'settings.json';

@unlink($file);
@unlink($file . '.lock'); // dedicated lock file used by the patched mutateUnderLock
file_put_contents($file, json_encode(['admin_switch' => 1, 'versions' => new stdClass()]));

$php = PHP_BINARY;
$N   = 50;

// Decide each worker's role. Indices divisible by 7 are "admin" re-assertions,
// guaranteeing the admin toggle races repeatedly against version writers.
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
    $cmd = [$php, $dir . DIRECTORY_SEPARATOR . 'worker.php', $file, $job['mode'], (string) $job['id']];
    $p = proc_open($cmd, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);
    if (is_resource($p)) {
        // Close stdin; leave stdout/stderr to drain at the end.
        fclose($pipes[0]);
        $procs[] = ['proc' => $p, 'pipes' => $pipes];
    }
}

echo "Launched " . count($procs) . " concurrent workers...\n";

// Wait for all workers to terminate.
foreach ($procs as $entry) {
    // Drain pipes to avoid deadlock on full buffers.
    $out = stream_get_contents($entry['pipes'][1]);
    $err = stream_get_contents($entry['pipes'][2]);
    fclose($entry['pipes'][1]);
    fclose($entry['pipes'][2]);
    proc_close($entry['proc']);
    if (trim($err) !== '') {
        fwrite(STDERR, "worker stderr: " . trim($err) . "\n");
    }
}

// Verify final state.
$raw = file_get_contents($file);
$final = json_decode($raw, true);

$failures = [];

if (!is_array($final)) {
    $failures[] = "final file is not valid JSON array. raw=" . var_export($raw, true);
} else {
    // (a) admin_switch must still be 1 -> proves no lost update wiped admin's write.
    if (($final['admin_switch'] ?? null) !== 1) {
        $failures[] = "admin_switch lost: expected 1, got " . var_export($final['admin_switch'] ?? null, true);
    }

    // (b) every expected version key must be present -> no version write was lost.
    $versions = $final['versions'] ?? [];
    if (!is_array($versions)) {
        $failures[] = "versions is not an array: " . var_export($versions, true);
        $versions = [];
    }
    $missing = array_values(array_diff($expectedVersions, array_keys($versions)));
    if (!empty($missing)) {
        $failures[] = count($missing) . " version(s) lost: " . implode(',', $missing);
    }
    $foundCount = count(array_intersect($expectedVersions, array_keys($versions)));

    echo "Expected versions: " . count($expectedVersions) . "\n";
    echo "Found versions:    " . $foundCount . "\n";
    echo "admin_switch:      " . var_export($final['admin_switch'] ?? null, true) . "\n";
}

if (empty($failures)) {
    echo "RESULT: PASS — no lost update. admin_switch preserved and all versions present.\n";
    exit(0);
}

echo "RESULT: FAIL\n";
foreach ($failures as $f) {
    echo " - $f\n";
}
exit(1);
