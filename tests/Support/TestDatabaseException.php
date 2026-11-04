<?php

namespace Maatify\Realm\Tests\Support;

use Kreait\Firebase\Exception\DatabaseException;
use RuntimeException;

/** A concrete Kreait database exception for tests. */
final class TestDatabaseException extends RuntimeException implements DatabaseException
{
}
