<?php

namespace Maatify\Realm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * beste/clock 2.3.0 changed the interface of its clock in a way that breaks kreait/firebase-php 6.x
 * (Factory::$clock needs StellaMaris\Clock\ClockInterface); 2.3.1 reverted it. kreait allows
 * `beste/clock ^2.1`, so the manifest excludes that one release instead of adding a direct requirement.
 */
final class BesteClockConflictTest extends TestCase
{
    public function testTheManifestExcludesOnlyTheBrokenRelease(): void
    {
        $manifest = json_decode((string)file_get_contents(__DIR__ . '/../composer.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame(['beste/clock' => '2.3.0'], $manifest['conflict'] ?? null);
        $require = $manifest['require'] ?? null;
        $this->assertIsArray($require);
        $this->assertArrayNotHasKey('beste/clock', $require, 'beste/clock is excluded, not required directly.');
    }

    public function testComposerRefusesToInstallTheBrokenRelease(): void
    {
        if (trim((string)shell_exec('command -v composer 2>/dev/null')) === '') {
            $this->markTestSkipped('Composer is not available.');
        }
        $dir = sys_get_temp_dir() . '/realm-clock-conflict-' . bin2hex(random_bytes(4));
        mkdir($dir);
        try {
            copy(__DIR__ . '/../composer.json', $dir . '/composer.json');
            $output = [];
            $status = 0;
            exec(sprintf(
                'cd %s && composer require --dry-run --no-interaction --no-progress --ignore-platform-req=php %s 2>&1',
                escapeshellarg($dir),
                escapeshellarg('beste/clock:=2.3.0')
            ), $output, $status);
            $text = implode("\n", $output);
        } finally {
            @unlink($dir . '/composer.json');
            @unlink($dir . '/composer.lock');
            @rmdir($dir);
        }

        if ($status !== 0 && preg_match('/could not (fetch|be fetched)|curl error|Could not resolve host|network/i', $text) === 1) {
            $this->markTestSkipped('Packagist is not reachable from here.');
        }
        $this->assertNotSame(0, $status, 'Composer must refuse beste/clock 2.3.0: ' . $text);
        $this->assertStringContainsString('conflict', strtolower($text));
        $this->assertStringContainsString('beste/clock', $text);
    }
}
