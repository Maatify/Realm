<?php

namespace Maatify\Realm\Tests;

use Kreait\Firebase\Factory;
use PHPUnit\Framework\TestCase;

/**
 * RealmDB builds its connection with `new Factory()`. Some combinations of kreait/firebase-php 6.x and
 * its clock dependency cannot even construct it (beste/clock 2.3.0 gave a TypeError), so this runs on
 * every resolved dependency set, the lowest ones included.
 */
final class FactoryConstructionTest extends TestCase
{
    public function testTheFirebaseFactoryCanBeConstructed(): void
    {
        $this->assertInstanceOf(Factory::class, new Factory());
    }
}
