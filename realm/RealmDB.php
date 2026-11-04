<?php
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2023-03-21
 * Time: 10:20 AM
 */

namespace Maatify\Realm;

use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Factory;
use Maatify\Json\Json;
use Maatify\Logger\Logger;
use Throwable;

abstract class RealmDB
{
    protected Database $database;
    protected string $json_file_credentials;
    protected string $url = '';

    public function __construct()
    {
        if(!empty($this->json_file_credentials)) {
            try {
                $this->database = (new Factory())
                    ->withDatabaseUri($this->url)
                    ->withServiceAccount($this->json_file_credentials)
                    ->createDatabase();
            } catch (\Exception $e) {
                self::reportFailure($e, 'exception_realm');
                Json::DbError();
            }
        }

    }

    /**
     * Logs the exception class, code and line only (the message and trace can carry database URLs,
     * credential file paths or request data). Without a log file name the logger uses the default
     * of its own release. A failure of the logger itself must not replace the outcome of the
     * operation that failed, so it is contained here.
     */
    protected static function reportFailure(Throwable $exception, ?string $logFile = null): void
    {
        $entry = [
            'exception' => get_class($exception),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
        ];
        try {
            if ($logFile === null) {
                Logger::RecordLog(message: $entry);
            } else {
                Logger::RecordLog(message: $entry, logFile: $logFile);
            }
        } catch (Throwable) {
            // Intentionally ignored.
        }
    }
}