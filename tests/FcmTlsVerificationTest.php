<?php

namespace Maatify\Realm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The legacy FCM call carries the server key in a header, so it must only be sent over a TLS
 * connection whose certificate and host name were verified. A local HTTPS stand-in with a
 * self-signed certificate that nobody trusts shows it: the call fails before any request reaches the
 * server, so the key is never sent.
 *
 * The success side (a trusted certificate) is not exercised here: this PHP build does not apply the
 * `curl.cainfo` ini setting to curl handles, and a production hook to inject a CA bundle only for a
 * test would widen the API of a deprecated class. The success path itself is unchanged code.
 */
final class FcmTlsVerificationTest extends TestCase
{
    private string $dir = '';
    private string $pem = '';
    private string $events = '';
    private int $port = 0;
    private int $pid = 0;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/realm-fcm-tls-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->pem = $this->dir . '/server.pem';
        $this->events = $this->dir . '/events.log';
        touch($this->events);
        $this->generateCertificate($this->pem);

        $this->port = $this->freePort();
        $this->pid = (int)trim((string)shell_exec(sprintf(
            '%s %s %d %s %s >/dev/null 2>&1 & echo $!',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/Support/https_server.php'),
            $this->port,
            escapeshellarg($this->pem),
            escapeshellarg($this->events)
        )));
        for ($i = 0; $i < 50; $i++) {
            $probe = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.1);
            if ($probe) {
                fclose($probe);
                return;
            }
            usleep(100000);
        }
        $this->fail('The local HTTPS stand-in did not start.');
    }

    protected function tearDown(): void
    {
        if ($this->pid > 0) {
            shell_exec('kill ' . $this->pid . ' 2>/dev/null');
        }
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testAnUntrustedCertificateIsRejectedAndTheKeyIsNeverSent(): void
    {
        $result = $this->send();

        $this->assertIsString($result, 'The call must fail when the certificate is not trusted.');
        $this->assertStringStartsWith('Curl failed:', $result);
        $this->assertStringContainsString('SSL', $result);
        $this->assertSame([], $this->requests(), 'No request, and so no key, may reach a server whose certificate was not verified.');
    }

    public function testTheSourceNoLongerDisablesVerification(): void
    {
        $source = (string)preg_replace('~//[^\n]*|/\*.*?\*/~s', '', (string)file_get_contents(__DIR__ . '/../realm/FCMDeprecated.php'));
        $this->assertDoesNotMatchRegularExpression('/CURLOPT_SSL_VERIFYPEER\s*,\s*(false|0)/i', $source);
        $this->assertDoesNotMatchRegularExpression('/CURLOPT_SSL_VERIFYHOST\s*,\s*(false|0)/i', $source);
        $this->assertMatchesRegularExpression('/CURLOPT_SSL_VERIFYPEER\s*,\s*true/i', $source);
        $this->assertMatchesRegularExpression('/CURLOPT_SSL_VERIFYHOST\s*,\s*2/', $source);
    }

    /** @return mixed */
    private function send(): mixed
    {
        $output = (string)shell_exec(sprintf(
            '%s -d display_errors=0 %s %d 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/Support/fcm_runner.php'),
            $this->port
        ));

        return json_decode($output, true);
    }

    /** @return list<array<string, mixed>> */
    private function requests(): array
    {
        $requests = [];
        foreach (explode("\n", (string)file_get_contents($this->events)) as $line) {
            $decoded = $line === '' ? null : json_decode($line, true);
            if (is_array($decoded)) {
                $requests[] = $decoded;
            }
        }

        return $requests;
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $name = (string)stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($name, (int)strrpos($name, ':') + 1);
    }

    private function generateCertificate(string $pem): void
    {
        $config = $this->dir . '/openssl.cnf';
        file_put_contents($config, "[req]\ndistinguished_name=dn\nx509_extensions=v3\nprompt=no\n[dn]\nCN=127.0.0.1\n[v3]\nsubjectAltName=IP:127.0.0.1\nbasicConstraints=CA:TRUE\n");
        $options = ['config' => $config, 'x509_extensions' => 'v3', 'req_extensions' => 'v3', 'private_key_bits' => 2048];
        $key = openssl_pkey_new($options);
        $this->assertNotFalse($key);
        $requestKey = $key; // openssl_csr_new() takes the key by reference
        $csr = openssl_csr_new(['commonName' => '127.0.0.1'], $requestKey, $options);
        $this->assertInstanceOf(\OpenSSLCertificateSigningRequest::class, $csr);
        $cert = openssl_csr_sign($csr, null, $key, 1, $options);
        $this->assertNotFalse($cert);
        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem, null, $options);
        file_put_contents($pem, (is_string($certPem) ? $certPem : '') . (is_string($keyPem) ? $keyPem : ''));
    }
}
