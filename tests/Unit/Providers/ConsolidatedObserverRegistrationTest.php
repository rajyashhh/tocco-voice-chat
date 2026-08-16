<?php

namespace Tests\Unit\Providers;

use PHPUnit\Framework\TestCase;

/**
 * Guards the consolidate-lane wiring:
 *
 *   1) CoinGameUserObserver and PersonalAccessTokenObserver are actually
 *      registered in AppServiceProvider::registerModelObservers(). Both observer
 *      classes already existed but were never attached, so their cache logic
 *      (ranking invalidation / latest-token sync) never ran. This test pins the
 *      registration against silent removal of either line.
 *
 *   2) PersonalAccessToken is observed on the exact class the app uses at runtime
 *      (Laravel\Sanctum\PersonalAccessToken — the project never overrides it via
 *      Sanctum::usePersonalAccessTokenModel), otherwise the deleted/created
 *      events would fire on a model nobody persists and the cache would drift.
 *
 *   3) The dead 'pool' => ['min','max'] key is gone from the mysql connection.
 *      The standard mysql driver ignores it entirely, so leaving it gave a false
 *      sense of a connection cap that never existed.
 *
 * Pure source/config inspection: no framework boot, no DB, no container.
 */
class ConsolidatedObserverRegistrationTest extends TestCase
{
    private string $providerSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerSource = file_get_contents(
            __DIR__ . '/../../../app/Providers/AppServiceProvider.php'
        );
    }

    public function test_coin_game_user_observer_is_registered(): void
    {
        $this->assertStringContainsString(
            'CoinGameUser::observe(CoinGameUserObserver::class)',
            $this->providerSource,
            'CoinGameUserObserver must be registered or ranking-cache invalidation never fires.'
        );

        $this->assertStringContainsString(
            'use App\\Observers\\CoinGameUserObserver;',
            $this->providerSource,
            'CoinGameUserObserver must be imported.'
        );

        $this->assertStringContainsString(
            'use App\\Models\\CoinGameUser;',
            $this->providerSource,
            'CoinGameUser model must be imported.'
        );
    }

    public function test_personal_access_token_observer_is_registered_on_sanctum_model(): void
    {
        $this->assertStringContainsString(
            'PersonalAccessToken::observe(PersonalAccessTokenObserver::class)',
            $this->providerSource,
            'PersonalAccessTokenObserver must be registered or latest_token_id cache drifts.'
        );

        $this->assertStringContainsString(
            'use App\\Observers\\PersonalAccessTokenObserver;',
            $this->providerSource,
            'PersonalAccessTokenObserver must be imported.'
        );

        // Must be the Sanctum model the app actually persists (no custom override exists).
        $this->assertStringContainsString(
            'use Laravel\\Sanctum\\PersonalAccessToken;',
            $this->providerSource,
            'The observed PersonalAccessToken must be Laravel\\Sanctum\\PersonalAccessToken.'
        );
    }

    public function test_observer_target_models_match_observer_typehints(): void
    {
        // The PersonalAccessTokenObserver type-hints Laravel\Sanctum\PersonalAccessToken;
        // the registration must observe that same FQCN, otherwise Eloquent would never
        // pass a compatible instance to the observer callbacks.
        $observerSource = file_get_contents(
            __DIR__ . '/../../../app/Observers/PersonalAccessTokenObserver.php'
        );

        $this->assertStringContainsString(
            'use Laravel\\Sanctum\\PersonalAccessToken;',
            $observerSource,
            'Observer and registration must agree on the PersonalAccessToken class.'
        );
    }

    public function test_dead_mysql_pool_key_is_removed(): void
    {
        // config/database.php calls env() / Str, both resolvable via the Composer
        // autoloader (PHPUnit bootstrap) without booting the framework.
        $config = require __DIR__ . '/../../../config/database.php';
        $mysql = $config['connections']['mysql'];

        $this->assertArrayNotHasKey(
            'pool',
            $mysql,
            "The standard mysql driver ignores 'pool'; the dead key must be removed to avoid a false connection-cap impression."
        );
    }
}
