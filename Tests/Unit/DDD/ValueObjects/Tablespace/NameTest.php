<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Tablespace;

use PhpPgAdmin\DDD\ValueObjects\Tablespace\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Name::class)]
final class NameTest extends TestCase
{
    public function testZeroIsAName(): void
    {
        // PostgreSQL takes "0" as a name, although PHP's empty() takes it for an empty string.
        self::assertSame('0', (string)new Name('0'));
    }

    public function testABlankNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Name(" \n");
    }
}
