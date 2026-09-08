#!/usr/bin/env php
<?php

/**
 * Patches vendor/encore/laravel-admin/src/Auth/Permission.php for RBAC compatibility.
 *
 * TWO CHANGES:
 *
 * 1. isAdministrator() — the original only checks isRole('administrator'), which
 *    misses the 'admin' and 'developer' role slugs that the app's isSuperAdmin()
 *    recognises.  This caused the encore middleware to deny access to super admins
 *    who didn't hold the literal 'administrator' slug.
 *
 * 2. error() — the original calls Admin::content()->withError() then
 *    Pjax::respond(), which can throw 500 on AJAX / non-Pjax requests when the
 *    admin content builder is unavailable.  Replaced with a simple abort(403).
 *
 * Idempotent — running on an already-patched file is a no-op.
 *
 * USAGE:
 *     php scripts/patch-encore-permission.php
 *
 * Called automatically by `composer post-install-cmd` and `post-update-cmd`.
 */

$file = __DIR__ . '/../vendor/encore/laravel-admin/src/Auth/Permission.php';

if (! file_exists($file)) {
    fwrite(STDERR, "encore Permission.php not found, skipping patch.\n");
    exit(0);
}

$content = file_get_contents($file);

$patched = false;

// ── Patch 1: isAdministrator() ──────────────────────────────────────────────
$oldIsAdmin = <<<'PATCH'
    /**
     * If current user is administrator.
     *
     * @return mixed
     */
    public static function isAdministrator()
    {
        return Admin::user()->isRole('administrator');
    }
PATCH;

$newIsAdmin = <<<'PATCH'
    /**
     * If current user is administrator.
     *
     * Uses the app's isSuperAdmin() which checks 'admin', 'administrator',
     * 'developer' roles and the '*' wildcard — matching the RBAC guard.
     * The original isRole('administrator') was too narrow and denied access
     * to users with the 'admin' or 'developer' role slugs.
     *
     * @return mixed
     */
    public static function isAdministrator()
    {
        $user = Admin::user();

        return method_exists($user, 'isSuperAdmin')
            ? $user->isSuperAdmin()
            : $user->isRole('administrator');
    }
PATCH;

if (strpos($content, 'isSuperAdmin') !== false) {
    fwrite(STDOUT, "encore Permission.php already patched (isAdministrator), skipping.\n");
} else {
    $newContent = str_replace($oldIsAdmin, $newIsAdmin, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        $patched = true;
    } else {
        fwrite(STDERR, "WARNING: Could not find isAdministrator() pattern in encore Permission.php.\n");
    }
}

// ── Patch 2: error() ────────────────────────────────────────────────────────
$oldError = <<<'PATCH'
    /**
     * Send error response page.
     */
    public static function error()
    {
        $response = response(Admin::content()->withError(trans('admin.deny')));

        if (!request()->pjax() && request()->ajax()) {
            abort(403, trans('admin.deny'));
        }

        Pjax::respond($response);
    }
PATCH;

$newError = <<<'PATCH'
    /**
     * Send error response page.
     *
     * Always returns a clean 403 instead of trying to render the admin
     * layout (which can throw 500 on AJAX / non-Pjax requests when the
     * admin content builder is unavailable).
     */
    public static function error()
    {
        abort(403, trans('admin.deny'));
    }
PATCH;

if (strpos($content, 'Always returns a clean 403') !== false) {
    fwrite(STDOUT, "encore Permission.php already patched (error), skipping.\n");
} else {
    $newContent = str_replace($oldError, $newError, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        $patched = true;
    } else {
        fwrite(STDERR, "WARNING: Could not find error() pattern in encore Permission.php.\n");
    }
}

if ($patched) {
    file_put_contents($file, $content);
    fwrite(STDOUT, "Patched encore/laravel-admin Permission.php (isAdministrator + error).\n");
}
