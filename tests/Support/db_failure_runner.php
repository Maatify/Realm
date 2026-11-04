<?php

// Builds a RealmDB whose service-account file does not exist, in its own process: the failure is
// logged and the request ends with Json::DbError() (which exits), so nothing after it runs.
// Usage: php db_failure_runner.php <credentials-file-path>

use Maatify\Realm\Tests\Support\TestRealmDb;

require __DIR__ . '/../../vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) !== 2 || !is_string($arguments[1])) {
    fwrite(STDERR, "Usage: php db_failure_runner.php <credentials-file-path>\n");
    exit(2);
}

new TestRealmDb($arguments[1]);
echo "NOT-ENDED";
