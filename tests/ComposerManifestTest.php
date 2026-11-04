<?php

namespace Maatify\Realm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The manifest keeps the Json release that allows maatify/logger 7 and Composer's fail-closed policy,
 * so the lowest and the latest dependency sets are resolved and audited under that policy in CI.
 * The logger constraint itself is not asserted here: scripts/verify-logger-matrix.sh pins it to each
 * tested release in its throwaway copy.
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

    public function testTheJsonFloorIsTheReleaseThatAllowsLogger7(): void
    {
        $require = $this->manifest()['require'] ?? null;
        $this->assertIsArray($require);
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
