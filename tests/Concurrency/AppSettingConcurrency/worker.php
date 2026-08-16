<?php
/**
 * Worker: performs ONE read-modify-write against the shared settings file.
 * Usage: php worker.php <settingsFile> <mode> <id>
 *   mode=version : adds versions["v<id>"] = <id>
 *   mode=admin   : sets admin_switch = 1 (simulates the admin force-update toggle
 *                  racing against the hot client endpoint writers)
 *
 * A tiny randomized pre-write spin widens the race window so that, WITHOUT the
 * lock + re-read, concurrent writers would clobber each other (lost update).
 */
require __DIR__ . '/store.php';

$file = $argv[1];
$mode = $argv[2];
$id   = $argv[3];

// Widen the race window: read-ish delay before the locked section contends.
usleep(random_int(0, 4000));

mutateUnderLock($file, function (array $current) use ($mode, $id) {
    // Simulate non-trivial work between read and write inside the lock too,
    // to maximize contention on the lock itself.
    usleep(random_int(200, 1500));

    if ($mode === 'admin') {
        $current['admin_switch'] = 1;
    } else {
        if (!isset($current['versions']) || !is_array($current['versions'])) {
            $current['versions'] = [];
        }
        $current['versions']['v' . $id] = (int) $id;
    }
    return $current;
});
