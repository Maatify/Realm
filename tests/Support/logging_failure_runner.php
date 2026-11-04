<?php

// Runs the failing-logger scenario in its own process, against a private copy of the logger whose
// log directory lives in a temporary project root, and prints the result as JSON.
// Usage: php logging_failure_runner.php <temporary-project-root> <logger-file-in-package>

use Maatify\Realm\Tests\Support\FailingDatabase;
use Maatify\Realm\Tests\Support\TestRealmSms;

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) !== 3 || !is_string($arguments[1]) || !is_string($arguments[2])) {
    fwrite(STDERR, "Usage: php logging_failure_runner.php <temporary-project-root> <logger-file-in-package>\n");
    exit(2);
}
[$script, $root, $loggerFileInPackage] = $arguments;

require __DIR__ . '/../../vendor/autoload.php';
// The private copy of the logger is loaded before anything references the class, so the autoloader
// never loads the original: the logger resolves its log directory from its own location, which is
// now the temporary project root.
require $root . '/vendor/maatify/logger/' . $loggerFileInPackage;

$loggerThrows = false;
try {
    Maatify\Logger\Logger::RecordLog(message: 'probe', logFile: 'probe');
} catch (Throwable) {
    $loggerThrows = true;
}

$sendSms = 'not-run';
if ($loggerThrows) {
    $sms = new TestRealmSms();
    $sms->useDatabase(new FailingDatabase('write failed'));
    try {
        $sendSms = $sms->sendTestSms() ? 'true' : 'false';
    } catch (Throwable $e) {
        $sendSms = 'threw ' . get_class($e);
    }
}

echo json_encode(['loggerThrows' => $loggerThrows, 'sendSms' => $sendSms], JSON_THROW_ON_ERROR);
