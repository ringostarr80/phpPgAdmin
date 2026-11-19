<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Tablespace;

use PhpPgAdmin\DDD\ValueObjects\Tablespace\Owner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Owner::class)]
final class OwnerTest extends TestCase
{
    public function testTheBlanksAroundAnOwnerArePartOfIt(): void
    {
        // PostgreSQL keeps them in a quoted role name, so without them the owner is another role.
        self::assertSame(' admin ', (string)new Owner(' admin '));
    }
}
