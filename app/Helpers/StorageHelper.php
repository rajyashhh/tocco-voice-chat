<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Get the full public URL for a stored file.
     * Handles both local paths and GCS paths correctly.
     * Uses storage_url from configs (editable in admin) instead of hard-coded .env
     *
     * @param string|null $path The file path (can be relative or with storage prefix)
     * @param string $disk The storage disk to use (default: 'admin' for GCS)
     * @return string The full public URL, or empty string if path is null/empty
     */
    public static function url(?string $path, string $disk = 'admin'): string
    {
        if (empty($path)) {
            return '';
        }

        // If already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Remove any leading 'storage/' or '/storage/' prefix (legacy paths)
        $path = preg_replace('#^/?storage/#', '', $path);

        // Build a public URL purely from admin-set values (panel-driven), never
        // by booting a storage client — generating a public link needs no keyfile.
        // Prefer an explicit 'storage_url' setting; else derive from the bucket.
        $baseUrl = Common::getSettingValue('storage_url');
        if (!is_string($baseUrl) || $baseUrl === '') {
            $bucket = Common::getSettingValue('gcs_bucket');
            if (is_string($bucket) && $bucket !== '') {
                $baseUrl = 'https://storage.googleapis.com/' . $bucket;
            }
        }

        if (is_string($baseUrl) && $baseUrl !== '') {
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        // Last-resort fallback: Laravel's Storage disk URL. Guarded so a storage
        // misconfiguration degrades one image to '' instead of failing the whole
        // response (a broken avatar must never take down the page).
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get URLs for multiple paths at once
     *
     * @param array|null $paths Array of file paths
     * @param string $disk The storage disk to use
     * @return array Array of full URLs
     */
    public static function urls(?array $paths, string $disk = 'admin'): array
    {
        if (empty($paths)) {
            return [];
        }

        return array_map(fn($path) => self::url($path, $disk), $paths);
    }
}
