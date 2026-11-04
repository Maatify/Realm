<?php

namespace Maatify\Realm\Tests\Support;

use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Database\RuleSet;
use LogicException;

/** The Kreait database contract with only getReference() working, returning a failing reference. */
final class FailingDatabase implements Database
{
    public function __construct(private readonly string $message)
    {
    }

    public function getReference(?string $path = null): Reference
    {
        return new FailingReference($this->message);
    }

    public function getReferenceFromUrl($uri): Reference
    {
        throw new LogicException('Not used by the tests.');
    }

    public function getRuleSet(): RuleSet
    {
        throw new LogicException('Not used by the tests.');
    }

    public function updateRules(RuleSet $ruleSet): void
    {
        throw new LogicException('Not used by the tests.');
    }

    public function runTransaction(callable $callable)
    {
        throw new LogicException('Not used by the tests.');
    }
}
