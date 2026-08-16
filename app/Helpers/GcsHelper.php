<?php

namespace App\Helpers;

use Google\Cloud\Storage\StorageClient;

/**
 * Single source of truth for the Google Cloud Storage bucket used by the
 * pre-signed upload endpoints (reels / chat video). It mirrors the SAME
 * panel-managed config the `gcs` filesystem disk uses (bucket + service-account
 * credentials injected at runtime from DB settings), so signed-upload URLs
 * always target the SAME bucket the app reads media from.
 *
 * Before this, the pre-signed controllers read the legacy `app.google_cloud_*`
 * env (a legacy base-image leftover pointing at the wrong bucket), so reel/chat
 * uploads went to the wrong bucket and the media 404'd on the app's storage_url.
 */
class GcsHelper
{
    /** Configured GCS bucket from the white-label `gcs` disk config. */
    public static function bucket()
    {
        $gcs = config('filesystems.disks.gcs');

        $options = [];
        if (!empty($gcs['project_id'])) {
            $options['projectId'] = $gcs['project_id'];
        }
        if (!empty($gcs['key_file']) && is_array($gcs['key_file'])) {
            // Credentials injected from the panel (gcs_service_account_json).
            $options['keyFile'] = $gcs['key_file'];
        } elseif (!empty($gcs['key_file_path'])) {
            $options['keyFilePath'] = $gcs['key_file_path'];
        }

        $storage = new StorageClient($options);

        return $storage->bucket($gcs['bucket']);
    }
}
