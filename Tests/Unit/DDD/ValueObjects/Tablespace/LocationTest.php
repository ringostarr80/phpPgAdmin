<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Tablespace;

use PhpPgAdmin\DDD\ValueObjects\Tablespace\Location;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Location::class)]
final class LocationTest extends TestCase
{
    public function testLocationIsTrimmed(): void
    {
        self::assertSame('/srv/archive', (string)new Location(" /srv/archive\n"));
    }

    public function testEmptyLocationIsKept(): void
    {
        // pg_tablespace_location() gives an empty location for pg_default and pg_global.
        self::assertSame('', (string)new Location(''));
    }
}
