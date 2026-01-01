<?php

declare(strict_types=1);

namespace Tests\Architecture\DDD;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ValueObjectsTest
{
    #[TestRule]
    public function testValueObjectsAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('PhpPgAdmin\DDD\ValueObjects'),
                    Selector::Not(Selector::isEnum()),
                )
            )
            ->shouldBeReadonly()
            ->because('ValueObjects are immutable by definition.');
    }
}
