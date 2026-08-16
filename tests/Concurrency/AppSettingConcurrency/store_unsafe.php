<?php
/**
 * NEGATIVE CONTROL: the OLD unsafe behavior (what the patch replaced).
 * Read whole file, mutate in memory, write whole file back — NO lock, and the
 * read happens up-front (stale), exactly the read-modify-write race described in
 * the root cause. Used only to prove the concurrency test actually detects lost
 * updates (i.e. the test has teeth). NOT representative of current production.
 */
function us_mutate(string $filePath, callable $mutator): array
{
    // Stale read (no lock) — mirrors the old AppSetting constructor read.
    $current = [];
    if (is_file($filePath)) {
        $content = @file_get_contents($filePath);
        if ($content !== false && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }
    }
    $next = $mutator($current);
    if (!is_array($next)) {
        $next = $current;
    }
    // Widen the lost-update window between read and write.
    usleep(random_int(500, 3000));
    @file_put_contents($filePath, json_encode($next));
    return $next;
}
