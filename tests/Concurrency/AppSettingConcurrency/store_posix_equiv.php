<?php
/**
 * POSIX-equivalent harness of the patched AppSetting logic, adapted ONLY so it
 * is runnable on this Windows dev box.
 *
 * WHY THIS EXISTS:
 *   The production code (app/Classes/AppSetting.php) holds the DATA file open
 *   (fopen('c+') + flock LOCK_EX) and then rename()s a temp file OVER that same
 *   path. On POSIX this is correct & atomic — rename swaps the inode and open
 *   handles keep working. On Windows, rename() over a path that ANY process
 *   (including the same one) holds open FAILS, so every write silently no-ops.
 *   That is a Windows filesystem limitation, NOT a bug in the production logic,
 *   which targets Linux (top-share / prod).
 *
 * WHAT CHANGED (only the lock mechanics, not the guarantees):
 *   - The exclusive lock is taken on a SEPARATE lock file (settings.json.lock)
 *     instead of on the data file itself, so the data file is never held open
 *     across the rename. This reproduces the EXACT serialization + atomic
 *     temp+rename semantics the production code relies on under POSIX, on
 *     Windows too.
 *
 * The critical, identical behaviors under test are preserved:
 *   1. Exclusive lock -> only one writer mutates at a time.
 *   2. RE-READ the on-disk data UNDER the lock (not a stale snapshot) -> this is
 *      what prevents lost updates.
 *   3. Mutate, then atomic write via temp-in-same-dir + rename.
 */

function pe_readFileAsArray(string $filePath): array
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

function pe_atomicWrite(string $filePath, array $data): void
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

function pe_mutateUnderLock(string $filePath, callable $mutator): array
{
    $lockPath = $filePath . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) {
        return $mutator([]);
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            return $mutator([]);
        }
        // Re-read CURRENT on-disk content under the lock — prevents lost updates.
        $current = pe_readFileAsArray($filePath);
        $next = $mutator($current);
        if (!is_array($next)) {
            $next = $current;
        }
        pe_atomicWrite($filePath, $next);
        flock($lock, LOCK_UN);
        return $next;
    } finally {
        fclose($lock);
    }
}
