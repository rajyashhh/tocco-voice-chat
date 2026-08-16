<?php
/**
 * Faithful standalone copy of the concurrency-safe read-modify-write logic
 * from app/Classes/AppSetting.php (mutateUnderLock / getFile / atomicWrite),
 * parameterized on an explicit $filePath instead of public_path('settings.json').
 *
 * The control-flow is identical to the patched production code:
 *   - the exclusive lock (LOCK_EX) is taken on a DEDICATED lock file
 *     ($filePath . '.lock'), opened 'c', whose inode is never renamed, so
 *     writers are genuinely serialized;
 *   - the data file is RE-READ fresh from its path under the lock (never a
 *     stale handle), which is what prevents lost updates;
 *   - the result is persisted via a temp-file-in-same-dir + rename, so the data
 *     file is never held open across the rename.
 * This is exactly the code path that ships, so the concurrency test exercises
 * the production behavior.
 */

function readFileAsArray(string $filePath): array
{
    if (!is_file($filePath)) {
        return [];
    }
    $content = @file_get_contents($filePath);
    if ($content === false || $content === '') {
        return [];
    }
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function atomicWrite(string $filePath, array $data): void
{
    $dir     = dirname($filePath);
    $encoded = json_encode($data);
    if ($encoded === false) {
        return;
    }
    $tmp = tempnam($dir, 'set');
    if ($tmp === false) {
        return;
    }
    if (@file_put_contents($tmp, $encoded) === false) {
        @unlink($tmp);
        return;
    }
    if (!@rename($tmp, $filePath)) {
        @unlink($tmp);
    }
}

function mutateUnderLock(string $filePath, callable $mutator): array
{
    $lockPath = $filePath . '.lock';

    // 'c' : create if missing, do NOT truncate. Used ONLY for flock; its inode
    // is stable and never renamed, so the lock genuinely serializes writers.
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) {
        return $mutator([]);
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            return $mutator([]);
        }
        // Re-read CURRENT on-disk content of the DATA file from its path under
        // the lock — prevents lost updates. Never reads a stale handle.
        $current = readFileAsArray($filePath);
        $next = $mutator($current);
        if (!is_array($next)) {
            $next = $current;
        }
        atomicWrite($filePath, $next);
        flock($lock, LOCK_UN);
        return $next;
    } finally {
        fclose($lock);
    }
}
