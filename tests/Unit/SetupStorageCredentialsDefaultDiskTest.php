<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Hermetic verification for the production profile-photo 404 fix.
 *
 * Root cause: with FILESYSTEM_DRIVER unset, config('filesystems.default')
 * stayed 'local', so Common::upload() (profile avatars and 60+ other callers)
 * wrote objects to the container disk while the app built GCS URLs from the
 * bucket storage_url -> guaranteed 404 after restart.
 *
 * The fix routes filesystems.default to 'gcs' inside
 * AppServiceProvider::setupStorageCredentials() whenever GCS credentials
 * resolve (DB settings or env fallback), honouring an explicit pin.
 *
 * Runs against SQLite in memory with real provider code — no GCS/network access.
 */
class SetupStorageCredentialsDefaultDiskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated per-test DB with a real settings table.
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::dropIfExists('settings');
        Schema::create('settings', function ($t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
    }

    private function setSettings(array $rows): void
    {
        foreach ($rows as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Invoke the protected provider method under test via reflection.
     */
    private function runSetupStorageCredentials(): void
    {
        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'setupStorageCredentials');
        $method->setAccessible(true);
        $method->invoke($provider);
    }

    /** @test */
    public function default_disk_stays_local_when_no_gcs_credentials_anywhere(): void
    {
        // Resolved config state of an unconfigured install.
        config(['filesystems.default' => 'local']);
        config(['filesystems.disks.gcs.driver' => 'gcs']);
        config(['filesystems.disks.gcs.bucket' => '']);
        config(['filesystems.disks.gcs.key_file' => []]);
        config(['filesystems.disks.gcs.key_file_path' => null]);
        config(['filesystems.disks.gcs.project_id' => '']);

        $this->runSetupStorageCredentials();

        $this->assertSame(
            'local',
            config('filesystems.default'),
            'Unconfigured install must keep the local default (fallbackToLocalDisks behaviour).'
        );
    }

    /** @test */
    public function default_disk_routes_to_gcs_when_db_credentials_resolve(): void
    {
        config(['filesystems.default' => 'local']); // FILESYSTEM_DRIVER unset in prod
        config(['filesystems.disks.gcs.driver' => 'gcs']);
        config(['filesystems.disks.gcs.bucket' => '']);
        config(['filesystems.disks.gcs.key_file' => []]);
        config(['filesystems.disks.gcs.key_file_path' => null]);
        config(['filesystems.disks.gcs.project_id' => '']);

        $this->setSettings([
            'gcs_service_account_json' => json_encode([
                'type'          => 'service_account',
                'project_id'    => 'stub-project',
                'private_key'   => '-----BEGIN PRIVATE KEY-----STUB-----END PRIVATE KEY-----',
                'client_email'  => 'stub@stub-project.iam.gserviceaccount.com',
            ]),
            'gcs_bucket'     => 'tocco-voice-chat-media-2026',
            'gcs_project_id' => 'stub-project',
        ]);

        $this->runSetupStorageCredentials();

        $this->assertSame(
            'gcs',
            config('filesystems.default'),
            'With GCS credentials resolved (DB settings), uploads must target the gcs disk.'
        );
        $this->assertSame(
            'tocco-voice-chat-media-2026',
            config('filesystems.disks.gcs.bucket'),
            'DB bucket override must be applied to the gcs disk.'
        );
        $this->assertSame(
            'https://storage.googleapis.com/tocco-voice-chat-media-2026',
            config('filesystems.disks.gcs.url'),
            'gcs disk url must carry the bucket name.'
        );
        $this->assertNotEmpty(
            config('filesystems.disks.gcs.key_file'),
            'DB SA JSON must be injected as the key_file credential.'
        );
    }

    /** @test */
    public function default_disk_honors_explicit_driver_pin(): void
    {
        // Explicit FILESYSTEM_DRIVER pin (e.g. a true S3 deploy) must win.
        config(['filesystems.default' => 's3']);
        config(['filesystems.disks.gcs.driver' => 'gcs']);
        config(['filesystems.disks.gcs.bucket' => 'some-bucket']);
        config(['filesystems.disks.gcs.key_file' => ['stub' => 'sa']]);
        config(['filesystems.disks.gcs.key_file_path' => null]);
        config(['filesystems.disks.gcs.project_id' => 'stub-project']);

        $this->setSettings([
            'gcs_service_account_json' => json_encode(['project_id' => 'stub-project']),
            'gcs_bucket'               => 'some-bucket',
            'gcs_project_id'           => 'stub-project',
        ]);

        $this->runSetupStorageCredentials();

        $this->assertSame(
            's3',
            config('filesystems.default'),
            'An explicit FILESYSTEM_DRIVER pin must never be overridden by the GCS routing.'
        );
    }
}
