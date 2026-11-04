<?php

namespace Maatify\Realm\Tests;

use Maatify\Logger\Logger;
use Maatify\Realm\Tests\Support\CapturesLogs;
use Maatify\Realm\Tests\Support\FailingDatabase;
use Maatify\Realm\Tests\Support\TestRealmSms;
use PHPUnit\Framework\TestCase;

/**
 * RealmSms never named a log file: its failures go to whatever default the installed maatify/logger
 * release uses (admin_logs on 5.0.1, app from 5.1). Reporting through the failure-safe helper must not
 * change that destination.
 */
final class SmsLogDestinationTest extends TestCase
{
    use CapturesLogs;

    public function testASmsFailureIsLoggedToTheLoggersDefaultDestination(): void
    {
        $this->startLogCapture();
        Logger::RecordLog(message: ['probe' => 'the logger default destination']);
        $default = $this->normalized($this->changedLogFiles());
        $this->assertNotSame([], $default, 'The logger wrote nothing for the probe; the check would be vacuous.');

        $this->startLogCapture();
        $sms = new TestRealmSms();
        $sms->useDatabase(new FailingDatabase('write failed'));
        $this->assertFalse($sms->sendTestSms());
        $written = $this->normalized($this->changedLogFiles());

        $this->assertSame($default, $written, 'The SMS failure must be logged to the same file as a call without a log file name.');
    }

    /**
     * File names carry the date and hour; digits are masked so a probe and a failure in different
     * hours still compare equal.
     *
     * @param list<string> $paths
     * @return list<string>
     */
    private function normalized(array $paths): array
    {
        return array_values(array_unique(array_map(static fn (string $path): string => (string)preg_replace('/\d+/', '#', $path), $paths)));
    }
}
