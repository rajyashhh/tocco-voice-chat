<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Common;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the fail-closed contract of the emergency money kill-switches
 * (stop_charge / bd_stop_charge / stop_invite_code / close_open_gifts).
 *
 * Root cause being pinned: after the 2026-06-10 settings.json rebuild these
 * keys vanished and every enforcement site read `settings()->get(key)` (null)
 * as "switch off" — the emergency stops silently became no-ops (fail-open).
 * On top of that the stores are split: the admin stop_charge toggle writes the
 * settings DB table while the other toggles write storage/app/settings.json,
 * so DB-toggled stop_charge was invisible to the JSON readers.
 *
 * The fix is Common::stopSwitch(): reads BOTH stores, engaged if either says
 * '1', and a key missing from both stores reads as ENGAGED (fail-closed).
 *
 * Source-reflection style (no DB), matching the project's pure unit tests.
 */
class EmergencyStopSwitchTest extends TestCase
{
    private function methodSource(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = file($ref->getFileName());

        return implode('', array_slice(
            $file,
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        ));
    }

    private function fileSource(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 3) . '/' . $path);
    }

    public function test_stop_switch_is_fail_closed_and_reads_both_stores(): void
    {
        $src = $this->methodSource(Common::class, 'stopSwitch');

        // Both stores consulted.
        $this->assertStringContainsString('getSettingValue($key)', $src);
        $this->assertStringContainsString("settings()->get(\$key)", $src);

        // Missing from both stores => engaged (fail-closed).
        $this->assertStringContainsString('$db === null && $json === null', $src);
        $this->assertMatchesRegularExpression(
            '/\$db === null && \$json === null\) \{\s*return true;/s',
            $src,
            'A key missing from both stores must read as ENGAGED'
        );

        // Engaged if EITHER store says 1.
        $this->assertStringContainsString("(string) \$db === '1' || (string) \$json === '1'", $src);
    }

    public function test_charge_enforcement_sites_use_stop_switch(): void
    {
        $charge = $this->fileSource('app/Http/Controllers/Api/V1/ChargeController.php');
        $this->assertSame(
            3,
            substr_count($charge, "Common::stopSwitch('stop_charge')"),
            'ChargeController must gate all 3 charge paths through stopSwitch'
        );
        $this->assertStringNotContainsString("getSettingValue('stop_charge')", $charge);

        $bdWallet = $this->fileSource('app/Bd/Controllers/WalletController.php');
        $this->assertSame(2, substr_count($bdWallet, 'stopSwitch("bd_stop_charge")'));
        $this->assertSame(1, substr_count($bdWallet, 'stopSwitch("stop_charge")'));
        $this->assertStringNotContainsString('settings()->get("stop_charge"', $bdWallet);
        $this->assertStringNotContainsString('settings()->get("bd_stop_charge"', $bdWallet);

        $sysConfigs = $this->fileSource('Modules/UsersWallet/Services/CheckSystemConfigs.php');
        $this->assertStringContainsString("Common::stopSwitch('stop_charge')", $sysConfigs);
        $this->assertStringNotContainsString('settings()->get("stop_charge")', $sysConfigs);
    }

    public function test_invite_code_and_gift_enforcement_sites_use_stop_switch(): void
    {
        $userService = $this->fileSource('app/Services/UserService.php');
        $this->assertSame(
            2,
            substr_count($userService, "Common::stopSwitch('stop_invite_code')"),
            'UserService must use stopSwitch for both the payload flag and the claim kill switch'
        );
        $this->assertStringNotContainsString("settings()->get('stop_invite_code')", $userService);

        $userCommon = $this->fileSource('app/Helpers/UserCommon.php');
        $this->assertStringContainsString("Common::stopSwitch('stop_invite_code')", $userCommon);

        $giftLog = $this->fileSource('app/Http/Controllers/Api/V1/GiftLogController.php');
        $this->assertStringContainsString("Common::stopSwitch('close_open_gifts')", $giftLog);
        $this->assertStringNotContainsString("settings()->get('close_open_gifts')", $giftLog);
    }
}
