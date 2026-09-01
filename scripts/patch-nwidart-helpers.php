#!/usr/bin/env php
<?php

/**
 * Patches vendor/nwidart/laravel-modules/src/helpers.php to add a null-guard
 * to module_path().
 *
 * WHY THIS EXISTS:
 * ─────────────────
 * nwidart/laravel-modules v8.0.0 defines module_path() as:
 *
 *     $module = app('modules')->find($name);
 *     return $module->getPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
 *
 * During application bootstrap, module service providers (Region/AreaManager,
 * Country/SuperAdmin, etc.) are registered before the ModuleRepository has
 * finished scanning the Modules/ directory. When their boot() methods call
 * module_path(), find() returns null and getPath() crashes with:
 *
 *     Error: Call to a member function getPath() on null
 *
 * This is a known race condition in nwidart/laravel-modules v8.0.0. The
 * package does not guard against this in its helpers.php. Upgrading to a
 * version that does (if/when available) would eliminate this script.
 *
 * The patch adds a null-check fallback:
 *     $base = $module !== null
 *         ? $module->getPath()
 *         : base_path('Modules' . DIRECTORY_SEPARATOR . $name);
 *
 * This is safe because all modules live under Modules/{Name}/ by convention
 * (verified: config/modules.php → paths.modules = base_path('Modules')).
 *
 * This script is idempotent — running it on an already-patched file is a no-op.
 *
 * USAGE:
 *     php scripts/patch-nwidart-helpers.php
 *
 * Called automatically by `composer post-install-cmd`.
 */

$helpersFile = __DIR__ . '/../vendor/nwidart/laravel-modules/src/helpers.php';

if (! file_exists($helpersFile)) {
    // Package not installed yet — nothing to patch.
    fwrite(STDERR, "nwidart helpers.php not found, skipping patch.\n");
    exit(0);
}

$content = file_get_contents($helpersFile);

// Already patched? Check for the null-guard pattern.
if (strpos($content, "base_path('Modules' . DIRECTORY_SEPARATOR . \$name)") !== false) {
    fwrite(STDOUT, "nwidart helpers.php already patched, skipping.\n");
    exit(0);
}

$old = <<<'PATCH'
    function module_path($name, $path = '')
    {
        $module = app('modules')->find($name);

        return $module->getPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
PATCH;

$new = <<<'PATCH'
    function module_path($name, $path = '')
    {
        $module = app('modules')->find($name);

        // When module service providers boot before the ModuleRepository has
        // finished scanning, find() returns null. Fall back to the well-known
        // Modules/ directory layout so the provider can still resolve paths.
        $base = $module !== null
            ? $module->getPath()
            : base_path('Modules' . DIRECTORY_SEPARATOR . $name);

        return $base . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
PATCH;

$patched = str_replace($old, $new, $content);

if ($patched === $content) {
    fwrite(STDERR, "WARNING: Could not find expected code pattern in helpers.php. "
        . "The package version may have changed. Check vendor/nwidart/laravel-modules/src/helpers.php manually.\n");
    exit(1);
}

file_put_contents($helpersFile, $patched);
fwrite(STDOUT, "Patched nwidart/laravel-modules helpers.php (added null-guard to module_path).\n");
