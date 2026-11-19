<?php

declare(strict_types=1);

namespace Tests\Unit\DDD\ValueObjects\ServerSession;

use PhpPgAdmin\DDD\ValueObjects\ServerSession\Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Platform::class)]
final class PlatformTest extends TestCase
{
    public function testZeroIsAPlatform(): void
    {
        // PHP's empty() takes "0" for an empty string.
        self::assertSame('0', (string)new Platform('0'));
    }

    public function testABlankPlatformIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Platform(" \n");
    }
}
