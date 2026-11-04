<?php

namespace Maatify\Realm\Tests;

use Maatify\Realm\Tests\Support\CapturesLogs;
use Maatify\Realm\Tests\Support\FailingDatabase;
use Maatify\Realm\Tests\Support\TestRealmSms;
use PHPUnit\Framework\TestCase;

/**
 * A failure must be logged by exception class, code and line only: never the exception message or
 * trace (Firebase exceptions can carry database URLs, credential file paths and request data),
 * whatever maatify/logger version is installed.
 */
final class SensitiveLoggingTest extends TestCase
{
    use CapturesLogs;

    private const CANARIES = ['CANARY-SECRET-TOKEN', 'canary-project', 'canary-credentials-dir', 'CANARY-OTP-123456'];

    private string $dir = '';

    protected function setUp(): void
    {
        $this->startLogCapture();
        $this->dir = sys_get_temp_dir() . '/canary-credentials-dir-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        @rmdir($this->dir);
    }

    public function testAFailedSmsSendLogsOnlyTheExceptionClassCodeAndLine(): void
    {
        $sms = new TestRealmSms();
        $sms->useDatabase(new FailingDatabase('CANARY-SECRET-TOKEN failed at https://canary-project.firebaseio.com/sms'));

        $this->assertFalse($sms->sendTestSms());

        $logged = $this->loggedText();
        $this->assertStringContainsString('TestDatabaseException', $logged);
        $this->assertStringContainsString('4242', $logged);
        $this->assertNoCanary($logged);
    }

    public function testAConnectionSetupFailureIsLoggedByClassOnlyAndEndsWithAnInternalError(): void
    {
        $output = (string)shell_exec(sprintf(
            '%s -d display_errors=0 %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/Support/db_failure_runner.php'),
            escapeshellarg($this->dir . '/missing-service-account.json')
        ));

        $this->assertStringContainsString('Internal Server Error', $output, 'The request must end with the library\'s internal error response.');
        $this->assertStringNotContainsString('NOT-ENDED', $output);
        $logged = $this->loggedText();
        $this->assertStringContainsString('Exception', $logged);
        $this->assertNoCanary($logged);
    }

    public function testNoLogCallPassesTheExceptionOrItsMessage(): void
    {
        $calls = 0;
        foreach (glob(__DIR__ . '/../realm/*.php') ?: [] as $file) {
            $code = (string)preg_replace('~//[^\n]*|/\*.*?\*/~s', '', (string)file_get_contents($file));
            preg_match_all('/Logger::RecordLog\((.*?)\);/s', $code, $matches);
            foreach ($matches[1] as $arguments) {
                $calls++;
                foreach (['getMessage', 'getTraceAsString', 'getFile', 'json_file_credentials', 'api_key'] as $forbidden) {
                    $this->assertStringNotContainsString($forbidden, $arguments, basename($file));
                }
                $this->assertDoesNotMatchRegularExpression('/message:\s*\$(e|exception)\b/', $arguments, basename($file) . ': a raw throwable is logged with its message and trace');
                $this->assertDoesNotMatchRegularExpression('/RecordLog\(\s*\$(e|exception)\b/', $arguments, basename($file) . ': a raw throwable is logged with its message and trace');
            }
        }
        $this->assertGreaterThan(0, $calls, 'No Logger::RecordLog() call was found; the check would be vacuous.');
    }

    private function assertNoCanary(string $logged): void
    {
        foreach (self::CANARIES as $canary) {
            $this->assertStringNotContainsString($canary, $logged);
        }
    }
}
