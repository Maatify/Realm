<?php

// Sends one legacy FCM notification through FCMDeprecated to the local HTTPS stand-in, in its own
// process, and prints the result as JSON.
// With a second argument `http` the endpoint uses the http:// scheme instead of https://.
// Usage: php fcm_runner.php <port> [http]

use Maatify\Realm\Tests\Support\TestFcm;

require __DIR__ . '/../../vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) < 2 || count($arguments) > 3 || !is_string($arguments[1])) {
    fwrite(STDERR, "Usage: php fcm_runner.php <port> [http]\n");
    exit(2);
}

$scheme = ($arguments[2] ?? '') === 'http' ? 'http' : 'https';

echo json_encode((new TestFcm($scheme . '://127.0.0.1:' . $arguments[1] . '/fcm/send'))->sendTestNotification(), JSON_THROW_ON_ERROR);
