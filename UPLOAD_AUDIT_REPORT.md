# 📋 PHOTO/IMAGE UPLOAD SYSTEM — COMPLETE AUDIT REPORT

## A. UPLOAD ARCHITECTURE

### Upload Endpoints

| # | Endpoint | Method | Controller | Purpose |
|---|----------|--------|------------|---------|
| 1 | `POST /api/v1/profile/update` | Multipart | `ProfileController::update()` → `ProfileService::updateProfile()` | Flutter profile photo upload |
| 2 | `POST /api/v1/chatVideo` | Pre-signed URL | `StorageUploadController::chatVideo()` | Chat video/image pre-signed URL |
| 3 | `POST /api/v1/generate-upload-link` | Pre-signed URL | `UploadLinkController::uploadLink()` | Generic pre-signed upload URL |
| 4 | `POST /api/v1/chat/{id}/messages` | Multipart | `ChatMessagesController` → `MessageService::handleFileUpload()` | Chat file upload |
| 5 | Admin panel forms | Multipart | Laravel-Admin upload widget (disk = `admin`) | Admin panel image uploads |

### Central Upload Function

**`Common::upload()`** at `app/Helpers/Common.php:666`:

```php
$config = $disk ?: config('filesystems.default');  // ← THIS IS THE BUG
$file->storeAs($folder . DIRECTORY_SEPARATOR, $fileName, $config);
return $folder . DIRECTORY_SEPARATOR . $fileName;
```

### Storage Provider

- **Intended**: Google Cloud Storage (GCS) via `kreait/firebase-php` + `spatie/laravel-medialibrary` (Google Cloud Storage driver)
- **Actual**: Files are stored on **LOCAL disk** (`storage/app/`) due to misconfigured default driver

### Bucket

- Configured via DB setting `gcs_bucket` (runtime) or env `GOOGLE_CLOUD_STORAGE_BUCKET` (fallback)
- **env `GOOGLE_CLOUD_STORAGE_BUCKET=`** is **EMPTY** in `.env`
- The `gcs` named disk depends entirely on DB settings via `AppServiceProvider::setupStorageCredentials()`

### Object Path/Key Structure

- Profile images: `profile/{sha256_hash}.{ext}` (e.g., `profile/a1b2c3d4.jpg`)
- Chat images: `Chat_{APP_ENV}/chat_{room_id}/{hash}.{ext}`
- Room images: `rooms/{hash}.{ext}`
- Game images: `images/{hash}.{ext}`
- Badge images: `badges/{hash}.{ext}`

### Database Fields

- `profiles.avatar` — stores relative path like `profile/a1b2c3d4.jpg`
- `profile_gallaries.img` — stores relative path
- `moments_gallaries.image` — stores relative path
- `message_albums.file` — stores relative path

### URL Generation

**`getImagePath()`** at `app/helper/helpers.php:672`:
```php
function getImagePath(?string $path = null): ?string {
    return ($path === null || trim($path) === '') ? null : getDriverUrl() . '/' . $path;
}
```

**`getDriverUrl()`** at `app/helper/helpers.php:847`:
```php
function getDriverUrl(): ?string {
    return config('filesystems.disks.' . config('filesystems.default') . '.url');
}
```

The Flutter app gets its base URL from `/api/v1/config-app` → `VersionController::resolveStoragePublicUrl()` which reads the `storage_url` DB setting.

---

## B. CONFIGURATION STATUS

### Required Environment Variables

| Variable | Required By | Present in `.env` | Value |
|----------|-------------|-------------------|-------|
| `FILESYSTEM_DRIVER` | `config/filesystems.php` (default disk) | **❌ MISSING** | Defaults to `'local'` |
| `MEDIA_STORAGE_DRIVER` | All 12 media disks (driver switch) | ✅ Present | `gcs` |
| `GOOGLE_CLOUD_KEY_FILE` | `admin` + `gcs` disks (fallback) | ✅ Present | **EMPTY** |
| `GOOGLE_CLOUD_PROJECT_ID` | `admin` + `gcs` disks (fallback) | ✅ Present | **EMPTY** |
| `GOOGLE_CLOUD_STORAGE_BUCKET` | `admin` + `gcs` disks (bucket) | ✅ Present | **EMPTY** |
| `AWS_ACCESS_KEY_ID` | `s3` disk (S3 fallback) | ✅ Present | **EMPTY** |
| `AWS_SECRET_ACCESS_KEY` | `s3` disk | ✅ Present | **EMPTY** |
| `AWS_DEFAULT_REGION` | `s3` disk | ✅ Present | **EMPTY** |
| `AWS_BUCKET` | `s3` disk | ✅ Present | **EMPTY** |
| `APP_URL` | Various disk `url` configs | ✅ Present | (set) |

### DB Settings (white-label, runtime override)

| Setting Key | Required By | Runtime Resolution |
|-------------|-------------|-------------------|
| `gcs_service_account_json` | `setupStorageCredentials()` — key_file for GCS SDK | `AppServiceProvider::boot()` |
| `gcs_bucket` | `setupStorageCredentials()` — bucket name + URL | `AppServiceProvider::boot()` |
| `gcs_project_id` | `setupStorageCredentials()` — GCS project | `AppServiceProvider::boot()` |
| `storage_url` | Flutter app base URL, `StorageHelper::url()` | `VersionController::resolveStoragePublicUrl()` |

### Critical Configuration Gap

```
config('filesystems.default')  =  env('FILESYSTEM_DRIVER', 'local')  =  'local'
                                         ↑ NOT SET IN .env ↑
```

The `local` disk:
```php
'local' => ['driver' => 'local', 'root' => storage_path('app')]
// NO 'url' key defined
```

---

## C. FAILURE POINT

### PRIMARY FAILURE: `Common::upload()` stores to wrong disk

**File**: `app/Helpers/Common.php:719`
**Function**: `Common::upload()`
**Line**: `$config = $disk ?: config('filesystems.default');`

**Reason**: `FILESYSTEM_DRIVER` is not set → `config('filesystems.default')` = `'local'` → files stored at `storage/app/{folder}/{filename}` instead of in GCS bucket.

### SECONDARY FAILURE: URL generation returns null/broken path

**File**: `app/helper/helpers.php:847-850`
**Function**: `getDriverUrl()`
**Code**: `config('filesystems.disks.local.url')` → **not set** → returns `null`

**File**: `app/helper/helpers.php:672-674`
**Function**: `getImagePath()`
**Code**: `null . '/' . $path` → returns `'/profile/abc.jpg'` (null cast to empty string)

### What happens in practice:

1. Flutter sends `POST /api/v1/profile/update` with image file
2. `Common::upload('profile', $file, null)` stores to `storage/app/profile/{hash}.jpg` ✅ (file written)
3. DB saves `avatar = 'profile/{hash}.jpg'`
4. Flutter reads profile → backend returns `avatar = 'profile/{hash}.jpg'`
5. Flutter concatenates: `storage_url + '/' + 'profile/{hash}.jpg'`
6. If `storage_url` = `https://storage.googleapis.com/bucket-name`
7. Flutter requests: `https://storage.googleapis.com/bucket-name/profile/{hash}.jpg`
8. **404 NOT FOUND** — file is on LOCAL disk, not in the GCS bucket

---

## D. BUCKET/STORAGE STATUS

### Provider
Google Cloud Storage (GCS), with optional S3 fallback via `MEDIA_STORAGE_DRIVER=s3`

### Bucket Configuration Expected by Backend

| Config Key | Source | Status |
|------------|--------|--------|
| Bucket name | `gcs_bucket` DB setting (primary) or `GOOGLE_CLOUD_STORAGE_BUCKET` env (fallback) | env=**EMPTY**, DB=**UNKNOWN** (needs runtime check) |
| Service account JSON | `gcs_service_account_json` DB setting or `GOOGLE_CLOUD_KEY_FILE` env | env=**EMPTY**, DB=**UNKNOWN** |
| Project ID | `gcs_project_id` DB setting or `GOOGLE_CLOUD_PROJECT_ID` env | env=**EMPTY**, DB=**UNKNOWN** |
| Public URL | `storage_url` DB setting or `https://storage.googleapis.com/{bucket}` | DB=**UNKNOWN** |

### Permissions/Access Assumptions
- GCS bucket must allow object creation via service account
- Objects are stored with `visibility: public` on the GCS disks
- Pre-signed URLs use 15-minute expiry for uploads, 30-minute for deletes
- The `gcs` disk config has `'metadata' => ['cacheControl'=> 'public,max-age=86400']`

### Can Backend Reach/Authenticate to Bucket?

**For `Common::upload()` path**: **NO** — files go to local disk, GCS is never contacted.

**For pre-signed URL path** (`StorageUploadController`, `UploadLinkController`): **DEPENDS ON DB SETTINGS** — if `gcs_service_account_json` and `gcs_bucket` are populated in the settings table, GCS authentication works via `GcsHelper::bucket()`. If not, it would fail with an authentication error.

### Key Insight: Two Different Upload Paths

The system has **two completely separate upload mechanisms**:

1. **Multipart upload** (`Common::upload()`) — used by profile photos, chat images, badges, room images, game images. **BROKEN** — stores locally, generates broken URLs.

2. **Pre-signed URL** (`StorageUploadController`/`UploadLinkController`) — used by Flutter for video/chat pre-uploaded media. Uses `GcsHelper::bucket()` directly. **May work** if DB has GCS credentials.

---

## E. ROOT CAUSE (Ranked by Confidence)

### 1. 🔴 `FILESYSTEM_DRIVER` not set in `.env` (HIGHEST CONFIDENCE)

The `.env` file does not contain `FILESYSTEM_DRIVER`. The config defaults to `'local'`. This makes `Common::upload()` (called without explicit `$disk` by nearly every caller) store files on the local filesystem instead of in the GCS bucket.

**Evidence**: `grep -n "FILESYSTEM_DRIVER" .env` returns nothing. Only reference is `config/filesystems.php:16`.

**Impact**: Every multipart photo/image upload across the entire app (profile, chat, badges, rooms, games, moments) stores to local disk and generates broken URLs.

### 2. 🔴 `GOOGLE_CLOUD_*` env vars are all empty

`GOOGLE_CLOUD_KEY_FILE=`, `GOOGLE_CLOUD_PROJECT_ID=`, `GOOGLE_CLOUD_STORAGE_BUCKET=` are all empty in `.env`. The `gcs` named disk has NO fallback credentials from env. It relies entirely on DB settings via `setupStorageCredentials()`.

**Evidence**: `grep` output shows all three are empty.

**Impact**: Even if `FILESYSTEM_DRIVER` were set to `gcs`, the disk would have no credentials unless the DB settings table is populated. If the DB is also empty, GCS operations would fail with authentication errors.

### 3. 🟡 `local` disk has no `url` configured

The `local` disk in `config/filesystems.php` has no `url` key. When `getDriverUrl()` looks up `config('filesystems.disks.local.url')`, it returns null.

**Impact**: `getImagePath()` produces broken relative URLs like `/profile/abc.jpg` that don't resolve.

### 4. 🟡 Octane + config persistence

The app runs on Laravel Octane (Swoole). Octane boots the app once and persists config in memory. If `config:cache` was ever run (the web routes have artisan config:cache commands), the env values are baked and `FILESYSTEM_DRIVER` would remain `local` until `config:clear` is run AND the Octane worker recycles.

---

## F. FIX REQUIRED

### Fix 1: Set `FILESYSTEM_DRIVER=gcs` in `.env` (PRIMARY FIX)

Add to `.env`:
```
FILESYSTEM_DRIVER=gcs
```

This makes `config('filesystems.default')` return `'gcs'` instead of `'local'`. All `Common::upload()` calls without explicit `$disk` will then use the `gcs` disk, storing files in the GCS bucket.

**Prerequisites**: The DB `settings` table must have valid `gcs_service_account_json`, `gcs_bucket`, and `gcs_project_id` values (set via the admin panel's "App / White-Label Settings" page).

### Fix 2: Ensure GCS credentials are in DB settings

Verify/set in the admin panel (or directly in the `settings` table):
- `gcs_service_account_json` — full service account JSON key file content
- `gcs_bucket` — the GCS bucket name
- `gcs_project_id` — the GCP project ID
- `storage_url` — the public base URL for the bucket (e.g., `https://storage.googleapis.com/your-bucket-name`)

### Fix 3: Set `GOOGLE_CLOUD_STORAGE_BUCKET` in `.env` as fallback

Add to `.env`:
```
GOOGLE_CLOUD_STORAGE_BUCKET=your-actual-bucket-name
```

This provides an env-level fallback if the DB setting is empty.

### Fix 4: After changing `.env`, run these commands

```bash
# In the Docker container:
php artisan config:clear
php artisan config:cache  # optional but recommended for performance
# Then restart Octane workers:
php artisan octane:restart
```

---

## G. VERIFICATION PLAN

### Step 1: Verify env and config resolution

```bash
# Inside the backend container:
php artisan tinker --execute="
    echo 'Default disk: ' . config('filesystems.default') . PHP_EOL;
    echo 'GCS driver: ' . config('filesystems.disks.gcs.driver') . PHP_EOL;
    echo 'GCS bucket: ' . config('filesystems.disks.gcs.bucket') . PHP_EOL;
    echo 'GCS url: ' . config('filesystems.disks.gcs.url') . PHP_EOL;
    echo 'getDriverUrl: ' . getDriverUrl() . PHP_EOL;
"
```

**Expected**: Default disk = `gcs`, GCS bucket = non-empty, getDriverUrl = non-null URL.

### Step 2: Verify DB settings exist

```bash
php artisan tinker --execute="
    echo 'gcs_bucket: ' . (App\Models\Setting::where('key','gcs_bucket')->first()?->value ?: 'EMPTY') . PHP_EOL;
    echo 'gcs_sa: ' . (App\Models\Setting::where('key','gcs_service_account_json')->first()?->value ? 'SET' : 'EMPTY') . PHP_EOL;
    echo 'storage_url: ' . (App\Models\Setting::where('key','storage_url')->first()?->value ?: 'EMPTY') . PHP_EOL;
"
```

### Step 3: Test upload via API

```bash
# Create a test image:
echo -n "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -d > /tmp/test.png

# Upload via API (replace TOKEN):
curl -X POST "https://your-domain.com/api/v1/profile/update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@/tmp/test.png" \
  2>&1 | jq .
```

**Expected**: `success: true`, response contains `data.avatar = "profile/{hash}.webp"` (or `.png`).

### Step 4: Verify the file exists in GCS

```bash
# Via GCS console or gsutil:
gsutil ls gs://YOUR_BUCKET/profile/
# Or via tinker:
php artisan tinker --execute="
    \$disk = Storage::disk('gcs');
    echo 'Files: ' . implode(', ', \$disk->files('profile')) . PHP_EOL;
"
```

**Expected**: The uploaded file appears in the bucket.

### Step 5: Verify the URL is reachable

```bash
# Get the full image URL:
php artisan tinker --execute="
    \$path = 'profile/PUT_THE_HASH_HERE.jpg';
    echo 'Image URL: ' . getImagePath(\$path) . PHP_EOL;
    echo 'StorageHelper: ' . App\Helpers\StorageHelper::url(\$path) . PHP_EOL;
"
```

Then open the URL in a browser or curl:
```bash
curl -I "https://storage.googleapis.com/YOUR_BUCKET/profile/{hash}.jpg"
```

**Expected**: HTTP 200, `Content-Type: image/jpeg` (or image/png).

### Step 6: End-to-end Flutter verification

1. Flutter → POST `/api/v1/profile/update` with photo → expect success
2. Flutter → GET `/api/v1/profile/get/{id}` → check `avatar` field is a relative path
3. Flutter → check `config-app` response has non-empty `storage_url`
4. Flutter → load image: `storage_url + '/' + avatar` → expect HTTP 200
5. Repeat for chat image upload (both multipart and pre-signed paths)

### Step 7: Verify admin panel uploads

1. Open admin panel → any entity with image upload → upload a test image
2. Verify image appears (the admin uses `StorageHelper::url()` which has its own fallback logic)
3. Check the `admin` disk is configured: `config('filesystems.disks.admin.bucket')` should be non-empty

---

**Summary**: The upload pipeline is structurally sound. The single root cause is the missing `FILESYSTEM_DRIVER=gcs` in `.env`, which causes all multipart uploads to silently write to the local filesystem and generate broken URLs. The pre-signed URL path (used for chat video) may work independently if the DB settings are configured. Adding `FILESYSTEM_DRIVER=gcs` and ensuring DB settings (`gcs_service_account_json`, `gcs_bucket`, `gcs_project_id`, `storage_url`) are populated will restore end-to-end functionality.
