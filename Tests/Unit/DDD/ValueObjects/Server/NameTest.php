<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Server;

use PhpPgAdmin\DDD\ValueObjects\Server\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Name::class)]
final class NameTest extends TestCase
{
    public function testZeroIsAName(): void
    {
        // PHP's empty() takes "0" for an empty string.
        self::assertSame('0', (string)new Name('0'));
    }

    public function testABlankNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Name(" \n");
    }
}
