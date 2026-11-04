<?php

namespace Maatify\Realm\Tests\Support;

use Maatify\Realm\RealmDB;

/** A concrete RealmDB whose service-account file does not exist, so the connection setup fails. */
final class TestRealmDb extends RealmDB
{
    public function __construct(string $credentialsFile)
    {
        $this->json_file_credentials = $credentialsFile;
        $this->url = 'https://canary-project.firebaseio.com';
        parent::__construct();
    }
}
