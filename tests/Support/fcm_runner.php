<?php

// Sends one legacy FCM notification through FCMDeprecated to the local HTTPS stand-in, in its own
// process, and prints the result as JSON.
// Usage: php fcm_runner.php <https-port>

use Maatify\Realm\Tests\Support\TestFcm;

require __DIR__ . '/../../vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) !== 2 || !is_string($arguments[1])) {
    fwrite(STDERR, "Usage: php fcm_runner.php <https-port>\n");
    exit(2);
}

echo json_encode((new TestFcm('https://127.0.0.1:' . $arguments[1] . '/fcm/send'))->sendTestNotification(), JSON_THROW_ON_ERROR);
