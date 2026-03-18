<?php

declare(strict_types=1);

namespace Tests\Architecture\DDD;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DomainTest
{
    #[TestRule]
    public function testDomainIndependence(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpPgAdmin\DDD'))
            ->canOnly()->dependOn()
            ->classes(Selector::inNamespace('PhpPgAdmin\DDD'))
            ->because('Domain layer should only depend on itself and PHP standard classes.');
    }
}
