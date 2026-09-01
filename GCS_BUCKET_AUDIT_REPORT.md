# GCS/Bucket Configuration Audit — Runtime Results

## 1. Database Settings Table (live query)

| Setting | Status |
|---|---|
| `gcs_bucket` | **EMPTY** |
| `gcs_project_id` | **EMPTY** |
| `gcs_service_account_json` | **EMPTY** |
| `storage_url` | **EMPTY** |

All four white-label storage settings in the `settings` table are blank. `AppServiceProvider::setupStorageCredentials()` reads these rows at boot — when they're empty, no overrides are applied.

---

## 2. Runtime Laravel Config (resolved values)

| Config Path | Value | Problem |
|---|---|---|
| `config('filesystems.default')` | `'local'` | ❌ Should be `'gcs'` |
| `FILESYSTEM_DRIVER` env | `NULL` | ❌ Not set in `.env` |
| `MEDIA_STORAGE_DRIVER` env | `'gcs'` | ✅ Correct |
| `filesystems.disks.gcs.driver` | `'gcs'` | ✅ Correct |
| `filesystems.disks.gcs.bucket` | `''` (empty string) | ❌ No bucket name |
| `filesystems.disks.gcs.project_id` | `''` (empty string) | ❌ No project ID |
| `filesystems.disks.gcs.key_file` | `EMPTY` (empty array) | ❌ No credentials |
| `filesystems.disks.gcs.key_file_path` | `EMPTY` (null) | ❌ No key file path |
| `filesystems.disks.gcs.url` | `'https://storage.googleapis.com/'` | ❌ Missing bucket name in URL |

Every GCS configuration value is empty. The DB override path (`setupStorageCredentials`) also has nothing to inject because the DB settings are blank.

---

## 3. Config Cache / Octane State

| Check | Result |
|---|---|
| `bootstrap/cache/config.php` exists? | **YES** (86,003 bytes, dated Aug 16) |
| Cached value of `filesystems.default` | `'local'` |
| Octane running? | **YES** (`OctaneBroadcasterService::isOctane() = true`) |
| Stale config risk? | **YES** — config.php is cached with `default = local`. Even if `.env` is updated, Octane workers won't pick it up until `php artisan config:clear` + worker recycle. |

The `bootstrap/cache/config.php` file bakes `env()` return values at cache time. `FILESYSTEM_DRIVER` was `NULL` when cached, so `config('filesystems.default')` is permanently `'local'` until cache is cleared.

---

## 4. GCS Connectivity / Authentication Test

### Test 1: `GcsHelper::bucket()`
```
Result: Object created (lazy, no network call yet)
bucket->name(): '' (empty string)
```

### Test 2: `bucket->exists()`
```
FAILED: Google\Cloud\Core\Exception\HttpException
Message: {"error":{"code":400,"message":"Required parameter: project","errors":[{"message":"Required parameter: project","domain":"global","reason":"required"}]}}
```
**GCS rejects the request because project_id is empty.** The SDK can't even authenticate — it doesn't know which GCP project to target.

### Test 3: `Storage::disk('gcs')->files('profile')`
```
FAILED: League\Flysystem\UnableToListContents
Message: Unable to list contents for 'profile', shallow listing. Reason: Not Found
```
**GCS returns 404** — bucket name is empty string, so the request targets an invalid endpoint.

### Test 4: `StorageClient` instantiation
```
Result: Constructor succeeds (lazy)
$bucket->bucket('') succeeds (lazy)
First real API call → fails with 400/404
```

**Conclusion: Laravel CANNOT authenticate to or access any GCS bucket.** Zero credentials, zero bucket name, zero project ID.

---

## 5. URL Generation Test

| Function | Input | Output | Broken? |
|---|---|---|---|
| `getDriverUrl()` | — | `NULL` | ❌ `local` disk has no `url` |
| `getImagePath('profile/test.jpg')` | relative path | `'/profile/test.jpg'` | ❌ Relative path, 404 |
| `StorageHelper::url('profile/test.jpg')` | relative path | `'https://storage.googleapis.com/profile/test.jpg'` | ❌ Bucket name missing from URL |

Every URL generation path produces broken or unresolvable URLs.

---

## 6. Actual Provider / Bucket

| Question | Answer |
|---|---|
| **Actual provider configured** | GCS (driver = `gcs`) |
| **Actual bucket name** | **NONE** — empty string everywhere |
| **Actual project ID** | **NONE** — empty string everywhere |
| **Credentials** | **NONE** — empty key_file, empty key_file_path |
| **Public URL** | `https://storage.googleapis.com/` (no bucket name) |
| **Is the bucket real?** | **UNKNOWN** — can't test because nothing is configured |
| **Is the service account valid?** | **UNKNOWN** — no SA JSON provided |

---

## 7. Is `FILESYSTEM_DRIVER=gcs` Safe to Enable?

**NO — not with the current DB/env state.**

Enabling `FILESYSTEM_DRIVER=gcs` requires ALL of the following to be populated FIRST:

1. **DB setting `gcs_bucket`** — must contain the real GCS bucket name
2. **DB setting `gcs_project_id`** — must contain the real GCP project ID
3. **DB setting `gcs_service_account_json`** — must contain a valid SA JSON key with `storage.objects.create` + `storage.objects.get` permissions on the target bucket
4. **DB setting `storage_url`** — must contain `https://storage.googleapis.com/{bucket-name}`

If any of these is empty when `FILESYSTEM_DRIVER=gcs` is enabled, every upload will crash with the same 400/404 errors seen in the connectivity tests above. The application would go from "uploads silently go to local disk" to "uploads throw 500 errors."

---

## 8. Exact Fix Required (in order)

### Step 1: Populate the DB settings (via admin panel or direct DB)

```
gcs_bucket          →  [your-real-bucket-name]
gcs_project_id      →  [your-real-gcp-project-id]
gcs_service_account_json  →  [full SA JSON key content]
storage_url         →  https://storage.googleapis.com/[your-real-bucket-name]
```

### Step 2: Verify DB settings are correct

```bash
php artisan tinker --execute="
  foreach(['gcs_bucket','gcs_project_id','gcs_service_account_json','storage_url'] as \$k) {
    \$v = App\Models\Setting::where('key',\$k)->first()?->value;
    echo \$k.': '.(\$k==='gcs_service_account_json' ? (empty(\$v)?'EMPTY':'SET('.strlen(\$v).')') : (empty(\$v)?'EMPTY':\$v)).PHP_EOL;
  }
"
```

### Step 3: Set `FILESYSTEM_DRIVER=gcs` in `.env`

```
FILESYSTEM_DRIVER=gcs
```

### Step 4: Clear config cache + recycle Octane

```bash
php artisan config:clear
php artisan config:cache   # optional
php artisan octane:restart
```

### Step 5: Verify

```bash
php artisan tinker --execute="
  echo config('filesystems.default').PHP_EOL;  # must be 'gcs'
  echo config('filesystems.disks.gcs.bucket').PHP_EOL;  # must be non-empty
  \$b = \App\Helpers\GcsHelper::bucket();
  echo \$b->name().PHP_EOL;  # must be the bucket name
  echo \$b->exists() ? 'EXISTS' : 'NOT FOUND';  # must be EXISTS
"
```

---

**Summary:** GCS is **completely unconfigured** — empty DB settings, empty env vars, empty resolved config, and zero connectivity. The backend cannot authenticate to or access any GCS bucket. `FILESYSTEM_DRIVER=gcs` is **not safe to enable** until the four DB settings (`gcs_bucket`, `gcs_project_id`, `gcs_service_account_json`, `storage_url`) are populated with real values. Additionally, the stale `bootstrap/cache/config.php` and running Octane mean that even after env changes, `config:clear` + Octane restart is mandatory.
