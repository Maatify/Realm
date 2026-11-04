<?php

namespace Maatify\Realm\Tests\Support;

use Kreait\Firebase\Database\Reference;

/** A database reference whose push() fails with an exception that carries sensitive-looking text. */
final class FailingReference extends Reference
{
    public function __construct(private readonly string $message)
    {
        // The parent needs a real API client; this stand-in never reaches it.
    }

    public function push($value = null): Reference
    {
        throw new TestDatabaseException($this->message, 4242);
    }
}
