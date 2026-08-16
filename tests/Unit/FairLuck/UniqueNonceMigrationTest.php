<?php

namespace Tests\Unit\FairLuck;

use PHPUnit\Framework\TestCase;

/**
 * G4 / B-CON-2 (pure, no DB boot): the whole lucky-gift idempotency contract rests
 * on UNIQUE(user_id, nonce) on lucky_request_nonces — without it insertOrIgnore is
 * a silent no-op and a same-nonce retry DOUBLE-CHARGES.
 *
 * The Feature test (UniqueUserNonceConstraintTest) checks the LIVE schema via
 * SHOW INDEX but needs a booted DB (cannot run in the pure FairLuck suite). This
 * pure guard statically asserts the constraint is DECLARED in the migrations, so a
 * developer dropping/renaming it in code is caught even without a database.
 */
class UniqueNonceMigrationTest extends TestCase
{
    private function migrationsDir(): string
    {
        return dirname(__DIR__, 3) . '/database/migrations';
    }

    public function test_creating_migration_declares_unique_user_nonce(): void
    {
        $file = $this->migrationsDir() . '/2026_06_12_000003_create_lucky_request_nonces.php';
        $this->assertFileExists($file, 'lucky_request_nonces creating migration is missing');

        $src = file_get_contents($file);
        $normalized = preg_replace('/\s+/', '', $src);

        // $table->unique(['user_id', 'nonce'])  (whitespace/quote tolerant)
        $this->assertMatchesRegularExpression(
            "/->unique\(\[['\"]user_id['\"],['\"]nonce['\"]\]/",
            $normalized,
            'UNIQUE(user_id, nonce) not declared in the creating migration'
        );
    }

    public function test_confirm_guard_migration_exists_and_targets_the_constraint(): void
    {
        $file = $this->migrationsDir() . '/2026_06_13_000003_ensure_unique_user_nonce_on_lucky_request_nonces.php';
        $this->assertFileExists($file, 'confirm-only UNIQUE guard migration is missing');

        $src = file_get_contents($file);
        $this->assertStringContainsString('lucky_request_nonces', $src);
        $this->assertStringContainsString("unique(['user_id', 'nonce'])", $src,
            'confirm guard does not add the UNIQUE(user_id, nonce) index when absent');
        // It must never DROP the guard on rollback.
        $this->assertStringNotContainsString('dropUnique', $src, 'confirm guard must not drop the constraint');
    }

    public function test_ci_feature_guard_exists(): void
    {
        // The live-schema CI guard must exist so the build fails if the constraint
        // is ever dropped from the real database.
        $file = dirname(__DIR__, 2) . '/Feature/UniqueUserNonceConstraintTest.php';
        $this->assertFileExists($file, 'CI live-schema constraint guard is missing');
        $src = file_get_contents($file);
        $this->assertStringContainsString('SHOW INDEX FROM lucky_request_nonces', $src);
    }
}
