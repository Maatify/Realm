<?php

namespace Maatify\Realm\Tests\Support;

use Kreait\Firebase\Contract\Database;
use Maatify\Realm\RealmSms;

/** A concrete RealmSms for tests: an injectable database and a public way to send. */
final class TestRealmSms extends RealmSms
{
    public function useDatabase(Database $database): void
    {
        $this->database = $database;
    }

    public function sendTestSms(): bool
    {
        return $this->Set('01000000000', 'CANARY-OTP-123456')->DefaultSender();
    }
}
