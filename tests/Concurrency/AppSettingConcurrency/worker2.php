<?php
/**
 * Worker (store-selectable). Usage:
 *   php worker2.php <store> <settingsFile> <mode> <id>
 *   <store> = posix | unsafe
 */
$store = $argv[1];
$file  = $argv[2];
$mode  = $argv[3];
$id    = $argv[4];

if ($store === 'unsafe') {
    require __DIR__ . '/store_unsafe.php';
    $mutate = 'us_mutate';
} else {
    require __DIR__ . '/store_posix_equiv.php';
    $mutate = 'pe_mutateUnderLock';
}

usleep(random_int(0, 4000));

$mutate($file, function (array $current) use ($mode, $id) {
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
