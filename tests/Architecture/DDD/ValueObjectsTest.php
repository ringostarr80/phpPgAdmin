<?php

declare(strict_types=1);

namespace Tests\Architecture\DDD;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ValueObjectsTest
{
    public function testValueObjectsAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpPgAdmin\DDD\ValueObjects'))
            ->shouldBeReadonly()
            ->because('ValueObjects are immutable by definition.');
    }
}
