<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\Server;

use PhpPgAdmin\DDD\ValueObjects\Server\Host;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Host::class)]
final class HostTest extends TestCase
{
    public function testZeroIsAHost(): void
    {
        // PHP's empty() takes "0" for an empty string.
        self::assertSame('0', (string)new Host('0'));
    }

    public function testABlankHostIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Host(" \n");
    }
}
