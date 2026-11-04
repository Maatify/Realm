<?php

namespace Maatify\Realm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The manifest keeps the logger releases the call sites were fixed for, the Json release that allows
 * them, and Composer's fail-closed policy, so the lowest and the latest dependency sets are resolved
 * and audited under that policy in CI.
 */
final class ComposerManifestTest extends TestCase
{
    /** @return array<string, mixed> */
    private function manifest(): array
    {
        $manifest = json_decode((string)file_get_contents(__DIR__ . '/../composer.json'), true);
        $this->assertIsArray($manifest);

        /** @var array<string, mixed> $manifest */
        return $manifest;
    }

    public function testTheLoggerAndJsonConstraintsAllowTheFixedLoggerReleases(): void
    {
        $require = $this->manifest()['require'] ?? null;
        $this->assertIsArray($require);
        $this->assertSame('^5.0 || ^7.0', $require['maatify/logger'] ?? null);
        $json = $require['maatify/json'] ?? null;
        $this->assertIsString($json);
        $this->assertSame(1, preg_match('/^\^(5\.\d+\.\d+)$/', $json, $match), 'Unexpected constraint: ' . $json);
        $this->assertTrue(version_compare($match[1], '5.0.4', '>='), 'maatify/json before 5.0.4 does not allow maatify/logger 7.');
    }

    public function testTheFailClosedComposerPolicyIsKept(): void
    {
        $config = $this->manifest()['config'] ?? null;
        $this->assertIsArray($config);
        $policy = $config['policy'] ?? null;
        $this->assertIsArray($policy);
        $this->assertSame(['block' => true, 'audit' => 'fail'], $policy['advisories'] ?? null);
        $this->assertSame(['block' => true, 'block-scope' => 'all', 'audit' => 'fail'], $policy['malware'] ?? null);
        $this->assertSame(['audit' => 'fail'], $policy['abandoned'] ?? null);
    }
}
