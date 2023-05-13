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
                Logger::RecordLog($e, 'exception_realm');
                Json::DbError();
            }
        }

    }
}